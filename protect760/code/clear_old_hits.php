<?php
// запускается по крону. не чаще раза в 6 часов.
// очистка логов хитов от старых записей.
// очистка устаревшего PTR кэша.

require_once(__DIR__.'/../data/conf.php');
//@include(__DIR__.'/../data/subsalt.php');
//$ab_config['salt'] = $ab_config['subsalt'].$ab_config['salt'];

if (php_sapi_name() != 'cli') abDie('no cli');

$lastlog = 'Cron start time: '.date('d.m.Y H:i:s', time())."\n";
clearstatcache();

$antibot_db = new SQLite3(__DIR__.'/../data/sqlite.db'); 
$antibot_db->busyTimeout(2000);
$antibot_db->exec("PRAGMA journal_mode = WAL;");
$antibot_db->exec("PRAGMA cache_size = 100;");

$ab_config['time'] = time();

// подсчет адблоков за вчера:
$yesterday = date('Ymd', $ab_config['time'] - 86400); // вчерашний день
$date1 = (int)strtotime(date("Y-m-d", $ab_config['time'] - 86400).'T00:00');
$date2 = (int)strtotime(date("Y-m-d", $ab_config['time'] - 86400).'T23:59');
$result = $antibot_db->query("SELECT * FROM counters WHERE date = ".$yesterday." LIMIT 1");
$row = $result->fetchArray(SQLITE3_ASSOC);
if ($row) {
if ($row['adbpercent'] == '') {
$sql = "SELECT (SELECT COUNT(DISTINCT ip) FROM hits WHERE passed IN (1, 2) AND date > ".$date1." AND date < ".$date2.") AS total_unique_ips, (SELECT COUNT(DISTINCT ip) FROM hits WHERE passed IN (1, 2) AND adblock = 1 AND date > ".$date1." AND date < ".$date2.") AS adblock_unique_ips";
$result = $antibot_db->query($sql);
$row = $result->fetchArray(SQLITE3_ASSOC);
//echo $row['total_unique_ips']; // - всего уник IP
//echo $row['adblock_unique_ips']; // - уник IP с блокировщиками
if ($row['total_unique_ips'] != 0) {
$percentage = ($row['adblock_unique_ips'] / $row['total_unique_ips']) * 100;
$percentage = round($percentage, 2);
} else {
$percentage = 0;
}
$update = @$antibot_db->exec("UPDATE counters SET adbpercent = '".$percentage."' WHERE date = '".$yesterday."';");
}
}
// ---

$ab_config['period_cleaning'] = isset($ab_config['period_cleaning']) ? $ab_config['period_cleaning'] : 'lastmonth';
$ab_config['periods_cleaning'] = array('lastday', 'lastweek', 'lastmonth', 'quarter', 'lastyear');
if (!in_array($ab_config['period_cleaning'], $ab_config['periods_cleaning'])) {$ab_config['period_cleaning'] = 'lastmonth';}
$ab_config['ptrcache_time'] = isset($ab_config['ptrcache_time']) ? $ab_config['ptrcache_time'] : 7;

// искать до указанной даты
$tl['lastday'] = $ab_config['time'] - 86400; // сутки
$tl['lastweek'] = $ab_config['time'] - (86400*7); // неделя
$tl['lastmonth'] = $ab_config['time'] - (86400*30); // месяц
$tl['quarter'] = $ab_config['time'] - (86400*91); // квартал
$tl['lastyear'] = $ab_config['time'] - (86400*365); // год

if (isset($tl[$ab_config['period_cleaning']])) {
$datelimit = 'WHERE date < \''.$tl[$ab_config['period_cleaning']].'\'';
} else {
$datelimit = '';
}

$del = @$antibot_db->exec("DELETE FROM hits ".$datelimit.";");
$deleted = (int) $antibot_db->changes(); // кол-во строк удалено

// удаление старого PTR кэша:
$ab_config['ptrcache_time'] = $ab_config['time'] - (86400*$ab_config['ptrcache_time']); // 
$del = @$antibot_db->exec("DELETE FROM ptrcache WHERE date < ".$ab_config['ptrcache_time'].";");
$deleted2 = (int) $antibot_db->changes(); // кол-во строк удалено

$antibot_db->close();
unset($antibot_db);

clearstatcache();

$link = glob(__DIR__."/../data/cookie/*.db");
foreach ($link as $line) {
$antibot_cookie_db = new SQLite3($line); 
$antibot_cookie_db->busyTimeout(1000);
$antibot_cookie_db->exec("PRAGMA journal_mode = WAL;");
$del = @$antibot_cookie_db->exec("DELETE FROM list WHERE date < ".($ab_config['time'] - 864000).";");
$antibot_cookie_db->close();
unset($antibot_cookie_db);
}

$lastlog .= 'Cron final time: '.date('d.m.Y H:i:s', time())."\n";
$lastlog .= 'Log records deleted: '.$deleted."\n";
$lastlog .= 'PTR records deleted: '.$deleted2."\n";
$lastlog .= 'Period: '.$ab_config['period_cleaning']."\n";
file_put_contents(__DIR__.'/../data/cronlog', $lastlog, LOCK_EX);
echo $lastlog;
