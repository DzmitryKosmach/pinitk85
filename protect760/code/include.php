<?php
// Author: Mik Foxi admin@mikfoxi.com
// License: GNU GPL v3 - https://www.gnu.org/licenses/gpl-3.0.en.html
// Website: https://antibot.cloud/

define('ANTIBOT_INCLUDE', 1);

$ab_version = '9.064';
$ab_start_time = microtime(true);

$ab_se = array();
$ab_proxy = array();
$ab_rule = array();
$ab_path = array();
$ab_config = array();
$ab_config['main_url'] = 'antibot.cloud'; // for links
$ab_config['cloud_url'] = 'antibotcloudapi.com'; // cloud api
$ab_config['files_url'] = 'antibot.cloud'; // for updates
$ab_config['cloud_rus'] = 0;
$ab_config['colors'] = array('BLACK', 'GRAY', 'RED', 'YELLOW', 'GREEN', 'BLUE');
$ab_config['country'] = 'XX';
$ab_config['cidr'] = '';
$ab_config['asname'] = '';
$ab_config['asnum'] = '';
$ab_config['hosting'] = 0;
$ab_config['time'] = time();
$ab_config['result'] = '';
$ab_config['x-robots-tag'] = array();
$ab_config['is_gray'] = 0; // 1 - gray, 2 - dark
$ab_config['rowid'] = 0; // rowid dark in 5 tab

// default conf:
$ab_config['demo'] = 0;
$ab_config['phperror'] = 1;
$ab_config['disable'] = 0;
$ab_config['timezone'] = '';
$ab_config['webdir'] = '/antibot9/';
$ab_config['email'] = '';
$ab_config['pass'] = '';
$ab_config['secondpass'] = '';
$ab_config['salt'] = 'antibot';
$ab_config['subsalt'] = '';
$ab_config['timesalt'] = 'Y';
$ab_config['is_bitrix'] = 0;
$ab_config['hits_per_user'] = 1000;
$ab_config['input_button'] = 0;
$ab_config['tpl_lang'] = '';
$ab_config['buttons'] = 1;
$ab_config['time_ban'] = '0'; // string
$ab_config['time_ban_2'] = '1'; // string
$ab_config['re_check'] = 0;
$ab_config['recaptcha_key2'] = '';
$ab_config['recaptcha_secret2'] = '';
$ab_config['recaptcha_key'] = '';
$ab_config['recaptcha_secret'] = '';
$ab_config['utm_referrer'] = 1; 
$ab_config['utm_noindex'] = 1;
$ab_config['check_get_ref'] = 0;
$ab_config['bad_get_ref'] = '';
$ab_config['secret_allow_get'] = '';
$ab_config['antibot_log_tests'] = 1;
$ab_config['antibot_log_local'] = 0;
$ab_config['antibot_log_allow'] = 1;
$ab_config['antibot_log_fake'] = 1;
$ab_config['antibot_log_goodip'] = 0;
$ab_config['antibot_log_block'] = 1;
$ab_config['header_test_code'] = 200;
$ab_config['header_error_code'] = 200;
$ab_config['period_cleaning'] = 'lastmonth';
$ab_config['ptrcache_time'] = 15;
$ab_config['noarchive'] = 0;
$ab_config['del_ref_query_string'] = 0;
$ab_config['del_page_query_string'] = 0;
$ab_config['last_rule'] = '';
$ab_config['check'] = 1; // 1 cloud, 0 local
$ab_config['cookie'] = 'antibot';
$ab_config['js_error_msg'] = 'Your request has been denied';
$ab_config['unresponsive'] = 1; // 1 - stop, 0 - skip
$ab_config['wh'] = array('00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23');
$ab_config['block_fake_ref'] = 1; // 1 - block, 0 - do not check
$ab_config['samesite'] = 'Lax'; // Lax, Strict, None
$ab_config['iframe_stop'] = 0; // 1 - block, 0 - no check
$ab_config['hosting_block'] = 0; // 1 - block, 0 - no check
$ab_config['php_handler'] = '';
$ab_config['ab_url'] = '';
$ab_config['auth'] = 'cookie'; // sqlite or cookie
$ab_config['local_null_ref_stop'] = 0; // 1 - check local and null ref
$ab_config['privacy_policy'] = 'on';

// CloudFlare https://www.cloudflare.com/ips-v4
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

// server response code for blocking pages:
$ab_config['error_headers'] = array(
200 => '200 OK', 
400 => '400 Bad Request', 
401 => '401 Unauthorized', 
403 => '403 Forbidden', 
404 => '404 Not Found', 
410 => '410 Gone', 
429 => '429 Too Many Requests', 
451 => '451 Unavailable For Legal Reasons', 
500 => '500 Internal Server Error', 
502 => '502 Bad Gateway', 
503 => '503 Service Unavailable', 
504 => '504 Gateway Time-out', 
511 => '511 Network Authentication Required'
);

header('Pragma: no-cache');
header('Expires: Thu, 18 Aug 1994 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Service-Worker-Allowed: /');

include(__DIR__.'/../data/conf.php'); // главный конфиг

// случайная капча:
if ($ab_config['buttons'] == 5) {$ab_config['buttons'] = rand(0,2);}

if ($ab_config['cloud_rus'] == 1) {
$ab_config['cloud_url'] = 'antibotcloud.ru'; // cloud api
$ab_config['files_url'] = 'antibotcloud.ru'; // for updates
}

@include(__DIR__.'/../data/disable.php');
// в админке не выключать (исправляет конфликт с litespeed):
if (isset($_SERVER['SCRIPT_NAME']) AND $_SERVER['SCRIPT_NAME'] == $ab_config['webdir'].'admin.php') {
$ab_config['disable'] = 0;
}

if (file_exists(__DIR__.'/../data/subsalt.php')) {
include(__DIR__.'/../data/subsalt.php');
} else {
$ab_config['subsalt'] = $ab_config['time'];
file_put_contents(__DIR__.'/../data/subsalt.php', '<?php $ab_config[\'subsalt\'] = \''.$ab_config['subsalt'].'\';', LOCK_EX);
}

$ab_config['salt'] = $ab_config['subsalt'].$ab_config['salt'];

include(__DIR__.'/../data/se.php');
include(__DIR__.'/../data/proxy.php');
include(__DIR__.'/../data/path.php');
require_once(__DIR__.'/func.php');

// битрикс это боль:
//if ($ab_config['is_bitrix'] == 1) {
//$ab_config['host'] = isset($_SERVER['HTTP_HOST']) ? preg_replace("/[^0-9a-z-.:]/","", strstr($_SERVER['HTTP_HOST'], ':', true)) : 'errorhost.local';
//} else {
//$ab_config['host'] = isset($_SERVER['HTTP_HOST']) ? preg_replace("/[^0-9a-z-.:]/","", $_SERVER['HTTP_HOST']) : 'errorhost.local';
//}
$ab_config['host'] = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', strtolower($_SERVER['HTTP_HOST'])) : 'errorhost.local';
$ab_config['host'] = preg_replace('/[^0-9a-z.-]/', '', $ab_config['host']);
$ab_config['host'] = rtrim($ab_config['host'], ".");

$ab_config['request_method'] = isset($_SERVER['REQUEST_METHOD']) ? (string)trim(preg_replace("/[^a-zA-Z]/","",$_SERVER['REQUEST_METHOD'])) : '';

// проверка скрытых скриптов: ab.php post.php
$ab_config['post_md'] = 'x'.md5($ab_config['email'].'antibot');
if ($ab_config['request_method'] == 'POST' AND isset($_POST[$ab_config['post_md']])) {
if ($_POST[$ab_config['post_md']] == 'ab') {
	require_once(__DIR__.'/ab.php');
} elseif ($_POST[$ab_config['post_md']] == 'post') {
	require_once(__DIR__.'/post.php');
}
abDie();
}
	
// отключение антибота при наличии секретного параметра:
if ($ab_config['secret_allow_get'] != '') {
if (isset($_GET[$ab_config['secret_allow_get']]) OR isset($_COOKIE[$ab_config['secret_allow_get']])) {
//header('X-Robots-Tag: noindex');
$ab_config['x-robots-tag']['noindex'] = 'noindex';
absetcookie($ab_config['secret_allow_get'], 1, $ab_config['time']+2592000, true); // for a month
$ab_config['disable'] = 1;
}
}

// если в этот час Антибот выключен:
if (!in_array(str_pad(date('H', $ab_config['time']), 2, '0', STR_PAD_LEFT), $ab_config['wh'])) {
$ab_config['disable'] = 1;
}

// не мешать доступу в админ панель:
if (defined('ANTIBOT_ADMIN')) {
$ab_config['disable'] = 0;
$ab_config['hits_per_user'] = 10000;
$ab_config['x-robots-tag']['noindex'] = 'noindex';
$ab_config['antibot_log_local'] = 0;
$ab_config['input_button'] = 0; // always on
}

if (php_sapi_name() != 'cli' AND $ab_config['disable'] != 1) {

// add header X-Robots-Tag: noarchive
if ($ab_config['noarchive'] == 1) {
$ab_config['x-robots-tag']['noarchive'] = 'noarchive';
}

if (isset($_GET['utm_referrer']) AND $ab_config['utm_noindex'] == 1) {
//header('X-Robots-Tag: noindex');
$ab_config['x-robots-tag']['noindex'] = 'noindex';
}

$ab_config['date'] = date('Y.m.d', $ab_config['time']);

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
$ab_config['scheme'] = trim(strip_tags($_SERVER['HTTP_X_FORWARDED_PROTO']));
} elseif (isset($_SERVER['REQUEST_SCHEME'])) {
$ab_config['scheme'] = trim(strip_tags($_SERVER['REQUEST_SCHEME']));
} else {
$ab_config['scheme'] = 'https';
}

$ab_config['ym_uid'] = isset($_COOKIE['_ym_uid']) ? trim(preg_replace("/[^0-9]/","",$_COOKIE['_ym_uid'])) : '';
$ab_config['ga_uid'] = isset($_COOKIE['_ga']) ? trim(preg_replace("/[^a-zA-Z0-9\.]/","",$_COOKIE['_ga'])) : '';
$ab_config['useragent'] = isset($_SERVER['HTTP_USER_AGENT']) ? trim(strip_tags($_SERVER['HTTP_USER_AGENT'])) : '';
$ab_config['uri'] = isset($_SERVER['REQUEST_URI']) ? trim(strip_tags($_SERVER['REQUEST_URI'])) : '/';
$ab_config['uri'] = preg_replace('/\/+/', '/', $ab_config['uri']); // убираем задвоение //
$ab_config['ip'] = isset($_SERVER['REMOTE_ADDR']) ? trim(strip_tags($_SERVER['REMOTE_ADDR'])) : abDie('Remote Addr Error');
$ab_config['referer'] = isset($_SERVER['HTTP_REFERER']) ? trim(strip_tags($_SERVER['HTTP_REFERER'])) : '';
$ab_config['refhost'] = preg_replace("/[^0-9a-z-.:]/","", (string)parse_url($ab_config['referer'], PHP_URL_HOST));
if ($ab_config['referer'] != '' AND $ab_config['refhost'] == '') {
$ab_config['refhost'] = preg_replace("/[^0-9a-z-.]/","", $ab_config['referer']);
}
$ab_config['refhost_scheme'] = preg_replace("/[^a-z]/","", (string)parse_url($ab_config['referer'], PHP_URL_SCHEME));
$ab_config['accept_lang'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? trim(strip_tags($_SERVER['HTTP_ACCEPT_LANGUAGE'])) : '';
$ab_config['lang'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr(mb_strtolower(trim(preg_replace("/[^a-zA-Z]/","",$_SERVER['HTTP_ACCEPT_LANGUAGE'])), 'UTF-8'), 0, 2, 'utf-8') : ''; // 2 первых символа

$ab_config['protocol'] = (isset($_SERVER['SERVER_PROTOCOL']) ? trim(strip_tags($_SERVER['SERVER_PROTOCOL'])) : 'HTTP/1.0');
$ab_config['http_accept'] = isset($_SERVER['HTTP_ACCEPT']) ? trim(strip_tags($_SERVER['HTTP_ACCEPT'])) : '';

$ab_config['page'] = $ab_config['scheme'].'://'.$ab_config['host'].$ab_config['uri'];

$ab_config['antibot_hits'] = isset($_COOKIE[$ab_config['cookie'].'_hits']) ? (int)trim(preg_replace("/[^0-9]/","",$_COOKIE[$ab_config['cookie'].'_hits']))+1 : 1;

$ab_config['cid'] = $ab_config['time'].'.'.rand(1111,9999); // unique click id (hit)

// check for cloudflare and proxy:
if (filter_var($ab_config['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
foreach ($ab_proxy as $proxy_mask => $proxy_attr) {
if (net_match($proxy_mask, $ab_config['ip']) == 1 AND isset($_SERVER[$proxy_attr])) {
$ab_config['ip'] = $_SERVER[$proxy_attr];
break;
}
}
}

// ip validation check:
if (filter_var($ab_config['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
$ab_config['ipv'] = 4;
$ab_config_ip_array = explode('.', $ab_config['ip']);
$ab_config['ip_short'] = $ab_config_ip_array[0].'.'.$ab_config_ip_array[1].'.'.$ab_config_ip_array[2].'.0/24';
include(__DIR__.'/SxGeo.php'); // гео база городов
$ab_config['SxGeo'] = new SxGeoAb(__DIR__.'/SxGeoCity.dat');
$ab_config['sx'] = $ab_config['SxGeo']->get($ab_config['ip']);
$ab_config['city'] = isset($ab_config['sx']['city']['name_en']) ? trim($ab_config['sx']['city']['name_en']) : '';
} elseif (filter_var($ab_config['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
$ab_config['ip'] = abExpand($ab_config['ip']);
$ab_config['ipv'] = 6;
$ab_config_ip_array = explode(':', $ab_config['ip']);
$ab_config['ip_short'] = $ab_config_ip_array[0].':'.$ab_config_ip_array[1].':'.$ab_config_ip_array[2].':'.$ab_config_ip_array[3].':0000:0000:0000:0000/64';
$ab_config['city'] = '';
} else {
abDie('Bad IP');
}
$ab_config['ipnum'] = AbIp2num($ab_config['ip']);

// уникальный user id в главной cookie:
if (isset($_COOKIE[$ab_config['cookie']])) {
$ab_config['uid'] = preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE[$ab_config['cookie']]);
} else {
$ab_config['uid'] = abRandword(30);
absetcookie($ab_config['cookie'], $ab_config['uid'], $ab_config['time']+31536000, false); // на год
}

// контент главной cookie:
if ($ab_config['auth'] == 'sqlite') {
srand(crc32($ab_config['salt'].$ab_config['ip'].$ab_config['useragent']));
$antibot_cookie_db = new SQLite3(__DIR__.'/../data/cookie/'.rand(1,100).'.db'); 
srand();
$antibot_cookie_db->busyTimeout(1500);
$antibot_cookie_db->exec("PRAGMA journal_mode = WAL;");
$ab_cookie = @$antibot_cookie_db->querySingle("SELECT date FROM list WHERE md5 = '".md5($ab_config['salt'].$ab_config['ip'].$ab_config['useragent'])."';", true);
// create table if not present:
if ($antibot_cookie_db->lastErrorMsg() == 'no such table: list') {
$query = $antibot_cookie_db->exec("CREATE TABLE IF NOT EXISTS list (
md5 TEXT UNIQUE NOT NULL default '', 
date INTEGER NOT NULL default '0'
);");
}
if (isset($ab_cookie['date']) AND $ab_config['time'] - $ab_cookie['date'] < 864000) {
$ab_config['antibot_v'] = md5($ab_config['salt'].$ab_config['pass'].$ab_config['host'].$ab_config['useragent'].$ab_config['ip'].$ab_config['time']).'-'.$ab_config['time']; // всегда валидный
} else {
$ab_config['antibot_v'] = 'xxx-0';
}
} else {
$ab_config['antibot_v'] = isset($_COOKIE[$ab_config['uid']]) ? trim(strip_tags($_COOKIE[$ab_config['uid']])) : '';
}

$ab_cookie = explode('-', $ab_config['antibot_v']);
// дата истечения cookie:
$ab_config['cookie_date'] = isset($ab_cookie[1]) ? (int)trim($ab_cookie[1]) : $ab_config['time']-864100;
// хэш который должен быть равен antibot_ok:
$ab_config['antibot'] = isset($ab_cookie[0]) ? trim($ab_cookie[0]) : 0;

// хэш cookie (значение) должен быть таким:
$ab_config['antibot_ok'] = md5($ab_config['salt'].$ab_config['pass'].$ab_config['host'].$ab_config['useragent'].$ab_config['ip'].$ab_config['cookie_date']);

// если cookie старше 10 дней:
if ($ab_config['time'] - $ab_config['cookie_date'] > 864000) {
$ab_config['antibot'] = 'delete';
if (isset($ab_cookie['date']) AND $ab_config['auth'] == 'sqlite') {
$del = $antibot_cookie_db->exec("DELETE FROM list WHERE md5 = '".md5($ab_config['salt'].$ab_config['ip'].$ab_config['useragent'])."';");
}
}

// connection to the geo base:
$antibot_geo_db = new SQLite3(__DIR__.'/ipv'.$ab_config['ipv'].'.db'); 
$antibot_geo_db->busyTimeout(1000);
$antibot_geo_db->exec("PRAGMA journal_mode = MEMORY;");
$antibot_geo_db->exec("PRAGMA synchronous = OFF;");
$antibot_geo_db->exec("PRAGMA cache_size = 100;");

$ab_ip_info = @$antibot_geo_db->querySingle("SELECT * FROM list WHERE ip1 <= ".$ab_config['ipnum']." AND ip2 >= ".$ab_config['ipnum'].";", true);

// ip parameters from geo base:
if (isset($ab_ip_info['country'])) {
$ab_config['country'] = $ab_ip_info['country'];
$ab_config['cidr'] = $ab_ip_info['cidr'];
$ab_config['asname'] = $antibot_geo_db->escapeString($ab_ip_info['asname']);
$ab_config['asnum'] = $antibot_geo_db->escapeString($ab_ip_info['asnum']);
$ab_config['hosting'] = $ab_ip_info['hosting'];
} else {
$ab_config['country'] = 'XX';
$ab_config['cidr'] = '';
$ab_config['asname'] = '';
$ab_config['asnum'] = '';
}

$antibot_geo_db->close();
unset($antibot_geo_db);

// коннект к базе:
$antibot_db = new SQLite3(__DIR__.'/../data/sqlite.db'); 
$antibot_db->busyTimeout(2500);
$antibot_db->exec("PRAGMA journal_mode = WAL;");
$antibot_db->exec("PRAGMA cache_size = 100;");

// полная PTR запись:
$ab_config['ptr'] = GetPTR($ab_config['ip'], $antibot_db, $ab_config);

// урл реферера для сохранения:
if ($ab_config['del_ref_query_string'] == 1) {
$ab_config['save_referer'] = explode('?', $ab_config['referer']);
$ab_config['save_referer'] = $ab_config['save_referer'][0];
} else {
$ab_config['save_referer'] = $ab_config['referer'];
}
$ab_config['save_referer'] = $antibot_db->escapeString($ab_config['save_referer']);

// урл страницы для сохранения:
if ($ab_config['del_page_query_string'] == 1) {
$ab_config['save_page'] = explode('?', $ab_config['page']);
$ab_config['save_page'] = $ab_config['save_page'][0];
} else {
$ab_config['save_page'] = $ab_config['page'];
}
$ab_config['save_page'] = $antibot_db->escapeString($ab_config['save_page']);

$ab_config['city'] = $antibot_db->escapeString($ab_config['city']);
$ab_config['useragent'] = $antibot_db->escapeString($ab_config['useragent']);
$ab_config['referer'] = $antibot_db->escapeString($ab_config['referer']);
$ab_config['accept_lang'] = $antibot_db->escapeString($ab_config['accept_lang']);
$ab_config['page'] = $antibot_db->escapeString($ab_config['page']);
$ab_config['http_accept'] = $antibot_db->escapeString($ab_config['http_accept']);

if ($ab_config['local_null_ref_stop'] == 1 AND $ab_config['referer'] == '') {
$ab_config['antibot_ok'] = 'xxx';
//$ab_config['uid'] = abRandword(30);
//absetcookie($ab_config['cookie'], $ab_config['uid'], $ab_config['time']+31536000, false); // на год
}

// если cookie валидны (человек ранее проходил проверку):
if ($ab_config['antibot_ok'] == $ab_config['antibot']) {
// счетчик хитов в куках:
if ($ab_config['antibot_hits'] > $ab_config['hits_per_user']) {
absetcookie($ab_config['cookie'].'_hits', 0, $ab_config['time']-100, false);
absetcookie($ab_config['uid'], 0, $ab_config['time']-100, false);
} else {
absetcookie($ab_config['cookie'].'_hits', $ab_config['antibot_hits'], $ab_config['time']+86400, false);
}
// счетчик LOCAL:
file_put_contents(__DIR__.'/../data/counters/local_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
if ($ab_config['antibot_log_local'] == 1) {
//запись в лог имеющих разрешающие cookie (LOCAL):
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
$sql = 'INSERT INTO hits (cid, date, ip, ptr, useragent, uid, country, city, referer, page, lang, generation, passed, recaptcha, js_w, js_h, js_cw, js_ch, js_co, js_pi, refhost, adblock, asnum, asname, result, http_accept, method, ym_uid, ga_uid, ip_short, hosting, hit) VALUES (\''.$ab_config['cid'].'\', \''.$ab_config['time'].'\', \''.$ab_config['ip'].'\', \''.$ab_config['ptr'].'\', \''.$ab_config['useragent'].'\', \''.$ab_config['uid'].'\', \''.$ab_config['country'].'\', \''.$ab_config['city'].'\', \''.$ab_config['save_referer'].'\', \''.$ab_config['save_page'].'\', \''.$ab_config['accept_lang'].'\', \''.$ab_exec_time.'\', \'3\', \'0\', \'\', \'\', \'\', \'\', \'\', \'\', \''.$ab_config['refhost'].'\', \'\', \''.$ab_config['asnum'].'\', \''.$ab_config['asname'].'\', \'\', \''.$ab_config['http_accept'].'\', \''.$ab_config['request_method'].'\', \''.$ab_config['ym_uid'].'\', \''.$ab_config['ga_uid'].'\', \''.$ab_config['ip_short'].'\', \''.$ab_config['hosting'].'\', \''.$ab_config['antibot_hits'].'\');';
$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}

$ab_config['whitebot'] = 0;
} else {
// иначе запускаем цикл всех проверок:

// for China, disable the recaptcha check:
if ($ab_config['country'] == 'CN') {
$ab_config['re_check'] = 0;
}

// check ip by ipv rules:
if (!isset($ab_config['whitebot'])) {
$ab_ip_test = @$antibot_db->querySingle("SELECT rowid, * FROM ipv".$ab_config['ipv']."rules WHERE disable = '0' AND ip1 <= ".$ab_config['ipnum']." AND ip2 >= ".$ab_config['ipnum']." ORDER by priority ASC;", true);

// create table if not present:
if ($antibot_db->lastErrorMsg() == 'no such table: ipv'.$ab_config['ipv'].'rules') {
require_once(__DIR__.'/install.php');
abDie();
}

// если основная база залочена:
if (!$ab_ip_test) {
$error = $antibot_db->lastErrorMsg();
if ($error != 'not an error') {
// Действия в случае ошибки, например:
file_put_contents(__DIR__.'/../data/errorsql.txt', 'Ошибка проверки IP по 1/2 базе: '.$error."\n", FILE_APPEND | LOCK_EX);
header("Refresh:3");
echo "Страница будет перезагружена через 3 секунды...";
abDie();
}
}

// if ip is found in rules:
if (isset($ab_ip_test['rule'])) {
// deleting an expired rule:
if ($ab_ip_test['expires'] < $ab_config['time']) {
$del = @$antibot_db->exec("DELETE FROM ipv".$ab_config['ipv']."rules WHERE rowid=".$ab_ip_test['rowid'].";");
$ab_ip_test['rule'] = 'gray';
}

if ($ab_ip_test['rule'] == 'allow') {
$ab_config['whitebot'] = 1;
$ab_config['result'] = $antibot_db->escapeString('GOODIP By rule: '.$ab_ip_test['search']);
// в счетчик записывать в конце скрипта.
} elseif ($ab_ip_test['rule'] == 'block') {
$ab_config['result'] = $antibot_db->escapeString('BLOCK By rule: '.$ab_ip_test['search']);
require_once(__DIR__.'/block.php');
abDie();
} elseif ($ab_ip_test['rule'] == 'dark') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 2;
$ab_config['result'] = $antibot_db->escapeString('DARK By rule: '.$ab_ip_test['search']);
require_once(__DIR__.'/check.php');
abDie();
} elseif ($ab_ip_test['rule'] == 'gray') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 1;
$ab_config['result'] = $antibot_db->escapeString('GRAY By rule: '.$ab_ip_test['search']);
}
}

}
// конец проверки ip по базе правил.

// проверка фрейма яндекс метрики:
$ab_config['metrika'] = array('webvisor.com', 'metrika.yandex.ru');
if (in_array($ab_config['refhost'], $ab_config['metrika'])) {
$ab_config['admin_ip'] = @file_get_contents(__DIR__.'/../data/ip.php');
if ($ab_config['admin_ip'] == '<?php // '.$ab_config['ip']) {
$ab_config['whitebot'] = 1;
}
}

// проверяем юзерагент на принадлежность к белым ботам по массиву ab_se и ab_rule:
if (!isset($ab_config['whitebot'])) {
foreach ($ab_se as $ab_line => $ab_sign) {
// если часть юзерагента в черном списке:
if (stripos($ab_config['useragent'], $ab_line, 0) !== false) {
if ($ab_rule[$ab_line] == 'block') {
$ab_config['result'] = $antibot_db->escapeString('BLOCK By rule (user-agent part): '.$ab_line);
require_once(__DIR__.'/block.php');
abDie();
} elseif ($ab_rule[$ab_line] == 'dark') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 2;
$ab_config['result'] = $antibot_db->escapeString('DARK By rule (user-agent part): '.$ab_line);
require_once(__DIR__.'/check.php');
abDie();
} elseif ($ab_rule[$ab_line] == 'gray') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 1;
$ab_config['result'] = $antibot_db->escapeString('GRAY By rule (user-agent part): '.$ab_line);
}
}
// если нашли совпадение в имени юзерагента:
if (stripos($ab_config['useragent'], $ab_line, 0) !== false AND $ab_rule[$ab_line] == 'allow') {
if (TestWhiteBot($ab_config['ip'], $ab_sign, $antibot_db, $ab_config) == 1) {
// если это в реале по ptr белый бот:
if (!in_array('.',$ab_se[$ab_line])) {
// сохраняем ip в белый список только тем у кого полноценный идентифицируемый ptr:
$ips = AbIpRange($ab_config['ip_short']);
$sql = 'INSERT INTO ipv'.$ab_config['ipv'].'rules (priority, search, ip1, ip2, rule, comment, expires) VALUES (\'10\', \''.$ab_config['ip_short'].'\', \''.AbIp2num($ips[0]).'\', \''.AbIp2num($ips[1]).'\', \'allow\', \''.$ab_config['useragent'].' (ip: '.$ab_config['ip'].')\', \''.($ab_config['time'] + 7776000).'\');';
$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
$ab_config['result'] = $antibot_db->escapeString('GOODIP By rule (user-agent part): '.$ab_line);
$ab_config['whitebot'] = 1; break;
} else {
// фейковый бот:
if ($ab_config['antibot_log_fake'] == 1) {
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
$ab_config['result'] = $antibot_db->escapeString('FAKE By rule (user-agent part): '.$ab_line);
$sql = 'INSERT INTO hits (cid, date, ip, ptr, useragent, uid, country, city, referer, page, lang, generation, passed, recaptcha, js_w, js_h, js_cw, js_ch, js_co, js_pi, refhost, adblock, asnum, asname, result, http_accept, method, ym_uid, ga_uid, ip_short, hosting, hit) VALUES (\''.$ab_config['cid'].'\', \''.$ab_config['time'].'\', \''.$ab_config['ip'].'\', \''.$ab_config['ptr'].'\', \''.$ab_config['useragent'].'\', \''.$ab_config['uid'].'\', \''.$ab_config['country'].'\', \''.$ab_config['city'].'\', \''.$ab_config['save_referer'].'\', \''.$ab_config['save_page'].'\', \''.$ab_config['accept_lang'].'\', \''.$ab_exec_time.'\', \'7\', \'0\', \'\', \'\', \'\', \'\', \'\', \'\', \''.$ab_config['refhost'].'\', \'\', \''.$ab_config['asnum'].'\', \''.$ab_config['asname'].'\', \''.$ab_config['result'].'\', \''.$ab_config['http_accept'].'\', \''.$ab_config['request_method'].'\', \''.$ab_config['ym_uid'].'\', \''.$ab_config['ga_uid'].'\', \''.$ab_config['ip_short'].'\', \''.$ab_config['hosting'].'\', \''.$ab_config['antibot_hits'].'\');';
$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
// счетчик фейк ботов:
file_put_contents(__DIR__.'/../data/counters/fakes_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
header('X-Robots-Tag: noindex, noarchive');
header($ab_config['protocol'].' '.$ab_config['error_headers'][$ab_config['header_error_code']]);
header('Status: '.$ab_config['error_headers'][$ab_config['header_error_code']]);
$error_tpl = file_get_contents(__DIR__.'/../data/error.txt');
$error_tpl = str_replace('ERROR', 'ERROR '.$ab_config['ip'].' '.date('d.m.Y H:i:s', $ab_config['time']), $error_tpl);
echo $error_tpl;
abDie();
}
break;
}
}
}
// конец проверки по массиву конфига.

// проверяем URL по вхождению в $ab_path
if (!isset($ab_config['whitebot'])) {

// $ab_path['/api'] = 'allow';
foreach ($ab_path as $ab_line => $ab_sign) {
// если нашли совпадение в имени юзерагента:
if (stripos($ab_config['uri'], $ab_line, 0) !== false) {
// нашли:
if ($ab_sign == 'block') {
$ab_config['result'] = $antibot_db->escapeString('BLOCK By rule (url part): '.$ab_line);
require_once(__DIR__.'/block.php');
abDie();
} elseif ($ab_sign == 'dark') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 2;
$ab_config['result'] = $antibot_db->escapeString('DARK By rule (url part): '.$ab_line);
require_once(__DIR__.'/check.php');
abDie();
} elseif ($ab_sign == 'gray') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 1;
$ab_config['result'] = $antibot_db->escapeString('GRAY By rule (url part): '.$ab_line);
} elseif ($ab_sign == 'allow') {
//$ab_config['antibot_log_local'] = 0; // для исключения дублей в логе
$ab_config['whitebot'] = 0;
if ($ab_config['antibot_log_allow'] == 1) {
// записать в лог посещаемости, если включено логирование, с passed 4
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
$sql = 'INSERT INTO hits (cid, date, ip, ptr, useragent, uid, country, city, referer, page, lang, generation, passed, recaptcha, js_w, js_h, js_cw, js_ch, js_co, js_pi, refhost, adblock, asnum, asname, result, http_accept, method, ym_uid, ga_uid, ip_short, hosting, hit) VALUES (\''.$ab_config['cid'].'\', \''.$ab_config['time'].'\', \''.$ab_config['ip'].'\', \''.$ab_config['ptr'].'\', \''.$ab_config['useragent'].'\', \''.$ab_config['uid'].'\', \''.$ab_config['country'].'\', \''.$ab_config['city'].'\', \''.$ab_config['save_referer'].'\', \''.$ab_config['save_page'].'\', \''.$ab_config['accept_lang'].'\', \''.$ab_exec_time.'\', \'4\', \'0\', \'\', \'\', \'\', \'\', \'\', \'\', \''.$ab_config['refhost'].'\', \'\', \''.$ab_config['asnum'].'\', \''.$ab_config['asname'].'\', \'ALLOW By rule (url part): '.$ab_line.'\', \''.$ab_config['http_accept'].'\', \''.$ab_config['request_method'].'\', \''.$ab_config['ym_uid'].'\', \''.$ab_config['ga_uid'].'\', \''.$ab_config['ip_short'].'\', \''.$ab_config['hosting'].'\', \''.$ab_config['antibot_hits'].'\');';
$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
file_put_contents(__DIR__.'/../data/counters/allow_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
break;
}
// конец найденного
}
}
}
// конец проверки по массиву  $ab_path

// проверка по всем остальным параметрам:
if (!isset($ab_config['whitebot'])) {

// проверка GET переменных реферера:
if ($ab_config['check_get_ref'] == 1) {
$ab_query = parse_url($ab_config['referer']);
if (isset($ab_query['query'])) {
mb_parse_str($ab_query['query'], $mb_parse_str);
$ab_config['bad_get_ref'] = explode(' ', $ab_config['bad_get_ref']);
foreach ($ab_config['bad_get_ref'] as $bad_get_ref) {
if (isset($mb_parse_str[$bad_get_ref])) {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 1;
$ab_config['result'] = $antibot_db->escapeString('GRAY By rule (from conf): bad_get_ref');
break;
}
}
}
}

// проверка по таблице правил № 5:
// формируем через OR кучу всего: useragent, country, lang, referer, ptr, asname, asnum, uri, scriptname, httpaccept
$ab_search = array();
if ($ab_config['city'] != '') {$ab_search[] = $antibot_db->escapeString('city='.$ab_config['city']);}
$ab_search[] = $antibot_db->escapeString('useragent='.$ab_config['useragent']);
$ab_search[] = 'country='.$ab_config['country'];
$ab_search[] = 'lang='.$ab_config['lang'];
$ab_search[] = 'referer='.$ab_config['refhost'];
if ($ab_config['ym_uid'] != '') {$ab_search[] = 'ym_uid='.$ab_config['ym_uid'];}
if ($ab_config['ga_uid'] != '') {$ab_search[] = 'ga_uid='.$ab_config['ga_uid'];}

// PTR 2 и 3 уровня если есть:
$ab_config['ptr_arr'] = explode('.', $ab_config['ptr']);
$ab_config['ptr_arr'] = array_reverse($ab_config['ptr_arr'], false);
$ab_config['search'] = array();
if (isset($ab_config['ptr_arr'][1])) {
$ab_search[] = $antibot_db->escapeString('ptr='.$ab_config['ptr_arr'][1].'.'.$ab_config['ptr_arr'][0]);
}
if (isset($ab_config['ptr_arr'][2])) {
$ab_search[] = $antibot_db->escapeString('ptr='.$ab_config['ptr_arr'][2].'.'.$ab_config['ptr_arr'][1].'.'.$ab_config['ptr_arr'][0]);
}
$ab_search[] = $antibot_db->escapeString('asname='.$ab_config['asname']);
$ab_search[] = 'asnum='.$ab_config['asnum'];
$ab_search[] = $antibot_db->escapeString('uri='.$ab_config['uri']);
$ab_search[] = $antibot_db->escapeString('scriptname='.trim(strip_tags($_SERVER['SCRIPT_NAME'])));
$ab_search[] = $antibot_db->escapeString('httpaccept='.trim(strip_tags($ab_config['http_accept'])));

$ab_all_test = $antibot_db->query("SELECT rowid, * FROM rules WHERE search='".implode('\' OR search=\'', $ab_search)."' ORDER by priority ASC;");
while ($echo = $ab_all_test->fetchArray(SQLITE3_ASSOC)) {
if ($echo['disable'] == '0') {
if ($echo['rule'] == 'allow') {
require_once(__DIR__.'/allow.php');
break;
} elseif ($echo['rule'] == 'block') {
$ab_config['result'] = $antibot_db->escapeString('BLOCK By rule: '.$echo['search']);
require_once(__DIR__.'/block.php');
abDie();
} elseif ($echo['rule'] == 'dark') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 2;
$ab_config['rowid'] = $echo['rowid'];
$ab_config['result'] = $antibot_db->escapeString('DARK By rule: '.$echo['search']);
require_once(__DIR__.'/check.php');
abDie();
} elseif ($echo['rule'] == 'gray') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 1;
//$ab_config['rowid'] = $echo['rowid'];
$ab_config['result'] = $antibot_db->escapeString('GRAY By rule: '.$echo['search']);
}
}
}
}
// конец проверки по остальным параметрам.

// Hosting or Bad IP:
if ($ab_config['hosting_block'] == 1 AND $ab_config['hosting'] == 1 AND !isset($ab_config['whitebot'])) {
$ab_config['result'] = 'BLOCK By rule: Hosting or Bad IP';
require_once(__DIR__.'/block.php');
abDie();
}

// проверка на фейк реферер, из конфига:
if ($ab_config['block_fake_ref'] == 1 AND $ab_config['referer'] != '' AND !isset($ab_config['whitebot'])) {
$parse_url = parse_url($ab_config['referer']);
if (!isset($parse_url['scheme']) OR !isset($parse_url['path'])) {
$ab_config['result'] = 'BLOCK By rule: FAKE REFERER';
require_once(__DIR__.'/block.php');
abDie();
}
}

// проверяем last rule:
if ($ab_config['last_rule'] != '' AND !isset($ab_config['whitebot'])) {
$echo = array();
$echo['search'] = 'LAST RULE';
if ($ab_config['last_rule'] == 'allow') {
require_once(__DIR__.'/allow.php');
} elseif ($ab_config['last_rule'] == 'block') {
$ab_config['result'] = $antibot_db->escapeString('BLOCK By rule: '.$echo['search']);
require_once(__DIR__.'/block.php');
abDie();
} elseif ($ab_config['last_rule'] == 'dark') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 2;
$ab_config['result'] = $antibot_db->escapeString('DARK By rule: '.$echo['search']);
require_once(__DIR__.'/check.php');
abDie();
} elseif ($ab_config['last_rule'] == 'gray') {
//$ab_config['check_url'] = $ab_config['webdir'].'gray.php';
$ab_config['is_gray'] = 1;
$ab_config['result'] = $antibot_db->escapeString('GRAY By rule: '.$echo['search']);
}
}
// конец проверки last rule.

// дальше проверяем только людей:
if (!isset($ab_config['whitebot'])) {
// отдаем юзеру заглушку для проверки:
if ($ab_config['antibot_ok'] != $ab_config['antibot']) {
//$ab_config['result'] = '';
require_once(__DIR__.'/check.php');
abDie();
}

}

if (isset($ab_config['whitebot']) AND $ab_config['whitebot'] == 1) {
// логирование белых ботов, если включено:
if ($ab_config['antibot_log_goodip'] == 1) {
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
//if (!isset($ab_config['ptr'])) {$ab_config['ptr'] = '';}
$sql = 'INSERT INTO hits (cid, date, ip, ptr, useragent, uid, country, city, referer, page, lang, generation, passed, recaptcha, js_w, js_h, js_cw, js_ch, js_co, js_pi, refhost, adblock, asnum, asname, result, http_accept, method, ym_uid, ga_uid, ip_short, hosting, hit) VALUES (\''.$ab_config['cid'].'\', \''.$ab_config['time'].'\', \''.$ab_config['ip'].'\', \''.$ab_config['ptr'].'\', \''.$ab_config['useragent'].'\', \''.$ab_config['uid'].'\', \''.$ab_config['country'].'\', \''.$ab_config['city'].'\', \''.$ab_config['save_referer'].'\', \''.$ab_config['save_page'].'\', \''.$ab_config['accept_lang'].'\', \''.$ab_exec_time.'\', \'5\', \'0\', \'\', \'\', \'\', \'\', \'\', \'\', \''.$ab_config['refhost'].'\', \'\', \''.$ab_config['asnum'].'\', \''.$ab_config['asname'].'\', \''.$ab_config['result'].'\', \''.$ab_config['http_accept'].'\', \''.$ab_config['request_method'].'\', \''.$ab_config['ym_uid'].'\', \''.$ab_config['ga_uid'].'\', \''.$ab_config['ip_short'].'\', \''.$ab_config['hosting'].'\', \''.$ab_config['antibot_hits'].'\');';
$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
// счетчик белых ботов:
file_put_contents(__DIR__.'/../data/counters/goodip_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
if (stripos($ab_config['useragent'], 'Googlebot', 0) !== false) {
file_put_contents(__DIR__.'/../data/counters/google_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
} elseif (stripos($ab_config['useragent'], 'yandex.com', 0) !== false) {
file_put_contents(__DIR__.'/../data/counters/yandex_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
} elseif (stripos($ab_config['useragent'], 'bingbot', 0) !== false) {
file_put_contents(__DIR__.'/../data/counters/bing_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
}
}

// конец проверок.
}

if (count($ab_config['x-robots-tag']) > 0) {
header('X-Robots-Tag: '.implode(', ', $ab_config['x-robots-tag']));
}
} else {
// антибот выключен:
$ab_config['whitebot'] = 0;
}

if (!isset($ab_config['whitebot'])) {$ab_config['whitebot'] = 0;}
define('ANTIBOT_WHITEBOT', $ab_config['whitebot']);

    if (isset($antibot_cookie_db) && $antibot_cookie_db instanceof SQLite3) {
        $antibot_cookie_db->close();
        unset($antibot_cookie_db);
    }
    if (isset($antibot_db) && $antibot_db instanceof SQLite3) {
        $antibot_db->close();
        unset($antibot_db);
    }
