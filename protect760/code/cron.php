<?php
// cron для переноса статистики из файлов в базу
if (!isset($ab_version)) die('stop cron');
$ab_time_cron = $ab_config['time'];
$save_rows = array('test', 'auto', 'click', 'miss', 'allow', 'goodip', 'local', 'block', 'fakes', 'husers', 'uusers', 'google', 'yandex', 'bing', 'sqlerror');
$link = glob(__DIR__."/../data/counters/*");
$result = array();
//print_r($link);
foreach ($link as $line) {
$pattern = "/counters\/(\w+)_(\d{8})_/";
if (preg_match($pattern, $line, $matches)) {
$filemtime = (int)@filemtime($line);
if ($filemtime < $ab_config['time'] - 70) {
$name = $matches[1]; // stop
$date = $matches[2]; // 20231103
if (in_array($name, $save_rows)) {
if (!isset($result[$date])) {$result[$date] = array();}
if (!isset($result[$date][$name])) {$result[$date][$name] = 0;}
$result[$date][$name] = $result[$date][$name] + (int) @filesize($line);
@unlink($line); // 
} else {
@unlink($line); // No valid format
}
}
} else {
@unlink($line); // No valid format
}
}

foreach ($result as $k => $v) {
$upd = array();
$columns = implode("', '", array_keys($v));
$values = implode("', '", array_values($v));
$insert = 'INSERT INTO counters (\''.$columns.'\', \'date\') VALUES (\''.$values.'\', \''.$k.'\');';
//echo $insert.'<br />';
foreach ($v as $key => $value) {
$upd[] = $key.' = '.$key.' + '.$value;
}
$sql = 'UPDATE counters SET '.implode(', ', $upd).' WHERE date = \''.$k.'\';';
//echo $sql.'<br/>';
$update = @$antibot_db->exec($sql);
// если возникла sql ошибка:
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
foreach ($v as $key => $value) {
//$upd[] = $key.' = '.$key.' + '.$value;
file_put_contents(__DIR__.'/../data/counters/'.$key.'_'.$k.'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
// sql ошибку обработали.
if ($antibot_db->changes() == 0) {
$update = @$antibot_db->exec($insert);
// если возникла sql ошибка:
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
foreach ($v as $key => $value) {
//$upd[] = $key.' = '.$key.' + '.$value;
file_put_contents(__DIR__.'/../data/counters/'.$key.'_'.$k.'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
// sql ошибку обработали.
}
}

// удаление истекших правил:
$del = @$antibot_db->exec("DELETE FROM ipv4rules WHERE expires < ".$ab_config['time'].";");

$del = @$antibot_db->exec("DELETE FROM ipv6rules WHERE expires < ".$ab_config['time'].";");

$del = @$antibot_db->exec("DELETE FROM rules WHERE expires < ".$ab_config['time'].";");

clearstatcache();
