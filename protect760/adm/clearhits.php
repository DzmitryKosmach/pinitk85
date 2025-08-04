<?php
// очистка таблицы хитов
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Delete all records');

if (isset($_POST['clearhits_submit'])) {

$todate = isset($_POST['todate']) ? preg_replace("/[^a-z]/","",trim($_POST['todate'])) : '';

// искать до указанной даты
$tl['lastday'] = $ab_config['time'] - 86400; // сутки
$tl['lastweek'] = $ab_config['time'] - (86400*7); // неделя
$tl['lastmonth'] = $ab_config['time'] - (86400*30); // месяц
$tl['quarter'] = $ab_config['time'] - (86400*91); // квартал
$tl['lastyear'] = $ab_config['time'] - (86400*365); // год

if (isset($tl[$todate])) {
$datelimit = 'WHERE date < \''.$tl[$todate].'\'';
} else {
$datelimit = '';
}

$del = $antibot_db->exec("DELETE FROM hits ".$datelimit.";");
$vacuum = $antibot_db->exec("VACUUM;");
}

echo '<script>document.location.href="?'.$abw.$abp.'=hits";</script>';
