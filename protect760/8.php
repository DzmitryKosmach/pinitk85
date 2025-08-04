<?php
// JS счетчик уников и хитов
ignore_user_abort(true);
header('Content-Type: text/html; charset=UTF-8');
header('Expires: Thu, 18 Aug 1994 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('X-Robots-Tag: noindex');

require_once(__DIR__.'/code/func.php');
require_once(__DIR__.'/data/conf.php');
@include(__DIR__.'/data/disable.php');
@include(__DIR__.'/data/subsalt.php');
$ab_config['salt'] = $ab_config['subsalt'].$ab_config['salt'];

if ($ab_config['disable'] == 1) abDie('disable');

$ab_config['time'] = time();
$date = date('Ymd', $ab_config['time']);
$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? trim(strip_tags($_SERVER['HTTP_USER_AGENT'])) : abDie('ua');
$ip = isset($_SERVER['REMOTE_ADDR']) ? trim(strip_tags($_SERVER['REMOTE_ADDR'])) : abDie('ip');
$referer = isset($_SERVER['HTTP_REFERER']) ? trim(strip_tags($_SERVER['HTTP_REFERER'])) : abDie('ref');

if ($referer == '' OR $useragent == '') abDie('null');

function isBot($useragent) {
return preg_match("/(apache|bot|cfnetwork|crawler|curl|facebookexternalhit|feed|google.com|headless|index|mediapartners|python|spider|yahoo)/i", $useragent);
}
if (isBot($useragent)) abDie('bot');

// юникс время:
$t = isset($_POST['t']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['t'])) : abDie('t');
// ширина монитора:
$w = isset($_POST['w']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['w'])) : abDie('w');
// высота монитора:
$h = isset($_POST['h']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['h'])) : abDie('h');
// ширина окна браузера:
$cw = isset($_POST['cw']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['cw'])) : abDie('cw');
// высота окна браузера:
$ch = isset($_POST['ch']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['ch'])) : abDie('ch');

// подсчет хитов:
file_put_contents(__DIR__.'/data/counters/husers_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);

// CloudFlare:
$ab_proxy['173.245.48.0/20'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['103.21.244.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['103.22.200.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['103.31.4.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['141.101.64.0/18'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['108.162.192.0/18'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['190.93.240.0/20'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['188.114.96.0/20'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['197.234.240.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['198.41.128.0/17'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['162.158.0.0/15'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['104.16.0.0/13'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['104.24.0.0/14'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['172.64.0.0/13'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['131.0.72.0/22'] = 'HTTP_CF_CONNECTING_IP';
include(__DIR__.'/data/proxy.php');

// проверка на использование cloudflare и прокси:
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
foreach ($ab_proxy as $proxy_mask => $proxy_attr) {
if (net_match($proxy_mask, $ip) == 1 AND isset($_SERVER[$proxy_attr])) {
$ip = $_SERVER[$proxy_attr];
break;
}
}
}

// коннект к базе для подсчета уников:
$unique_db = new SQLite3(__DIR__.'/data/unique.db'); 
$unique_db->busyTimeout(2000);
$unique_db->exec("PRAGMA journal_mode = WAL;");

$add = @$unique_db->exec("INSERT INTO uniqueip (date, line) VALUES ('".$date."', '".md5($date.$ip)."');");
if ($unique_db->lastErrorMsg() == 'no such table: uniqueip') {
$query = $unique_db->exec("CREATE TABLE IF NOT EXISTS uniqueip (date INTEGER NOT NULL default '', line TEXT UNIQUE NOT NULL default '');");
}

if ($unique_db->changes() == 1) {
file_put_contents(__DIR__.'/data/counters/uusers_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
}

$cron_update_time = (int) trim(@file_get_contents(__DIR__.'/data/counters_unique')) + 0;
if ($ab_config['time'] - $cron_update_time > 86400) {
file_put_contents(__DIR__.'/data/counters_unique', $ab_config['time'], LOCK_EX);
$del = @$unique_db->exec("DELETE FROM uniqueip WHERE date < '".$date."';");
$vacuum = @$unique_db->exec("VACUUM;");
}
