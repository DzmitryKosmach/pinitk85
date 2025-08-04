<?php
// Author: Mik Foxi admin@mikfoxi.com
// License: GNU GPL v3 - https://www.gnu.org/licenses/gpl-3.0.en.html
// Website: https://antibot.cloud/

if (!isset($ab_version)) die('stop');

header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('X-Robots-Tag: noindex');

// коннект к базе:
$antibot_db = new SQLite3(__DIR__.'/../data/sqlite.db'); 
$antibot_db->busyTimeout(1500);
$antibot_db->exec("PRAGMA journal_mode = WAL;");
$antibot_db->exec("PRAGMA cache_size = 100;");

$ab_config['start_time'] = microtime(true);

$ab_config['abv'] = 20250531; // версия скрипта ab.php
$ab_config['score'] = 0;
$ab_config['msg'] = '';

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
$ab_config['scheme'] = trim(strip_tags($_SERVER['HTTP_X_FORWARDED_PROTO']));
} elseif (isset($_SERVER['REQUEST_SCHEME'])) {
$ab_config['scheme'] = trim(strip_tags($_SERVER['REQUEST_SCHEME']));
} else {
$ab_config['scheme'] = 'https';
}

// Страна из Cloudflare:
$ab_config['cfcountry'] = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strip_tags(trim($_SERVER['HTTP_CF_IPCOUNTRY'])) : '';

$ab_config['uri'] = isset($_SERVER['REQUEST_URI']) ? trim(strip_tags($_SERVER['REQUEST_URI'])) : '/';

//abDie('{"error": "'.$ab_config['js_error_msg'].'"}'); // для тестов

if ($_SERVER['REQUEST_METHOD'] != 'POST') {abDie('{"error": "Error NoPost"}');}

function isBot($useragent) {
// i - регистронезависимо
return preg_match("/(apache|bot|cfnetwork|crawler|curl|facebookexternalhit|feed|google.com|headless|index|mediapartners|python|spider|yahoo)/i", $useragent);
}

// status: error or cookie
// msg: error message or cookie hash
function SaveResult($status, $msg, $cookie) {
global $ab_config, $antibot_db;

if ($status == 'cookie') {
$passed = 'passed=\'1\', ';
$result = $msg;
$return = '{"cookie":"'.$cookie.'"}';
if ($msg == 'ALLOW By rule: timezone='.$ab_config['tz']) {
$passed = 'passed=\'4\', ';
}
} else {
$passed = '';
$result = $antibot_db->escapeString($msg);
if ($msg == 'BLOCK By rule: timezone='.$ab_config['tz']) {
$msg = $ab_config['js_error_msg'];
$passed = 'passed=\'6\', ';
}
$return = '{"error":"'.$msg.'"}';
}

if ($ab_config['antibot_log_tests'] == 1) {
if ($result != 'gray') {
$save_result = ', result=\''.$result.'\'';
} else {
$save_result = ''; // описание gray и dark локальных правил не перезаписывать
}

$ab_config['tz'] = $antibot_db->escapeString($ab_config['tz']);
$ab_exec_time = round(microtime(true) - $ab_config['start_time'], 4);
$sql = 'UPDATE hits SET '.$passed.' ipv4=\''.$ab_config['ipv4'].'\', generation2=\''.$ab_exec_time.'\', recaptcha=\''.$ab_config['score'].'\', js_w=\''.$ab_config['w'].'\', js_h=\''.$ab_config['h'].'\', js_cw=\''.$ab_config['cw'].'\', js_ch=\''.$ab_config['ch'].'\', js_co=\''.$ab_config['co'].'\', js_pi=\''.$ab_config['pi'].'\', adblock=\''.$ab_config['adb'].'\', timezone=\''.$ab_config['tz'].'\''.$save_result.' WHERE cid=\''.$ab_config['cid'].'\';';
$update = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
return $return;
}

// из POST данных:
// юзерагент:
$ab_config['useragent'] = isset($_POST['useragent']) ? trim(strip_tags($_POST['useragent'])) : '';
// 1 cookie отключены, не пускать таких:
$ab_config['cookieoff'] = isset($_POST['cookieoff']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['cookieoff'])) : 0;
// 0 - не стопать, 1 - gray, 2 - dark, их стопать:
$ab_config['gray'] = isset($_POST['gray']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['gray'])) : 0;
// номер строки из 5 таблицы если сработал dark:
$ab_config['rowid'] = isset($_POST['rowid']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['rowid'])) : 0;
// дата отправки запроса:
$ab_config['date'] = isset($_POST['date']) ? trim(preg_replace("/[^0-9]/","", $_POST['date'])) : 0;
// полный хэш (sha256):
$h1 = isset($_POST['h1']) ? trim(preg_replace("/[^0-9a-z]/","", $_POST['h1'])) : 'xxx';
// еще один тест целостности данных:
$test = isset($_POST['test']) ? trim(preg_replace("/[^0-9a-z]/","", $_POST['test'])) : 'xxx';
// 1 - серверный (подозрительный) ip:
$ab_config['hdc'] = isset($_POST['hdc']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['hdc'])) : 0;
// адблок, 1 - есть, 0 - нету:
$ab_config['adb'] = isset($_POST['a']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['a'])) : 0;
// код страны из локальной базы:
$ab_config['country'] = isset($_POST['country']) ? trim(preg_replace("/[^A-Z]/","", $_POST['country'])) : 'XX';
// ip из php, может быть ipv6:
$ab_config['ip'] = isset($_POST['ip']) ? trim(preg_replace("/[^0-9a-zA-Z\.\:]/","", $_POST['ip'])) : '';
// версия антибота:
$ab_config['v'] = isset($_POST['v']) ? (float)trim(preg_replace("/[^0-9\.]/","", $_POST['v'])) : '';
// уник id клика:
$ab_config['cid'] = isset($_POST['cid']) ? trim(preg_replace("/[^0-9\.]/","", $_POST['cid'])) : abDie('{"error": "Empty CID"}');
// ptr:
$ab_config['ptr'] = isset($_POST['ptr']) ? trim(preg_replace("/[^0-9a-zA-Z\.\:\-]/","", $_POST['ptr'])) : '';
// ширина монитора:
$ab_config['w'] = isset($_POST['w']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['w'])) : 0;
// высота монитора:
$ab_config['h'] = isset($_POST['h']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['h'])) : 0;
// ширина окна браузера:
$ab_config['cw'] = isset($_POST['cw']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['cw'])) : 0;
// высота окна браузера:
$ab_config['ch'] = isset($_POST['ch']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['ch'])) : 0;
// colordepth:
$ab_config['co'] = isset($_POST['co']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['co'])) : 0;
// pixeldepth:
$ab_config['pi'] = isset($_POST['pi']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['pi'])) : 0;
// реферер полностью (с ним пришли на сайт клиента):
$ab_config['ref'] = isset($_POST['ref']) ? trim(strip_tags($_POST['ref'])) : '';
// таймзона из JS:
$ab_config['tz'] = isset($_POST['tz']) ? trim(preg_replace("/[^0-9a-zA-Z\-\/\_\+]/","", $_POST['tz'])) : '';
// код страны из ipdb.cloud, только ipv4, может не быть:
$ab_config['ipdbc'] = isset($_POST['ipdbc']) ? trim(preg_replace("/[^A-Z]/","", $_POST['ipdbc'])) : '';
// ipv4 из ipdb.cloud, только ipv4, может не быть:
$ab_config['ipv4'] = isset($_POST['ipv4']) ? trim(preg_replace("/[^0-9\.]/","", $_POST['ipv4'])) : '';
// токен рекапчи, если включена проверка по рекапче:
$ab_config['rct'] = isset($_POST['rct']) ? trim(strip_tags($_POST['rct'])) : '';
// какие-то данные вспомогательные (в облаке не используются):
$ab_config['xxx'] = isset($_POST['xxx']) ? trim(strip_tags($_POST['xxx'])) : '';
// HTTP_ACCEPT:
$ab_config['accept'] = isset($_POST['accept']) ? trim(strip_tags($_POST['accept'])) : '';
// домен, с которого запросили этот скрипт:
$ab_config['referer'] = isset($_SERVER['HTTP_REFERER']) ? strip_tags(trim($_SERVER['HTTP_REFERER'])) : '';
// язык у норм браузеров есть всегда:
$ab_config['accept_lang'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? trim(strip_tags($_SERVER['HTTP_ACCEPT_LANGUAGE'])) : '';

// домен (host) с которого вызвали скрипт:
$ab_config['refhost'] = parse_url($ab_config['referer'], PHP_URL_HOST);

if ($ab_config['cookieoff'] == 1) {echo SaveResult('error', 'Cookies disabled', ''); abDie();}

if ($ab_config['time'] - $ab_config['date'] > 3600) {echo SaveResult('error', 'Token Expired', ''); abDie();}

// проверка timezone:
$ab_config['tz'] = $antibot_db->escapeString($ab_config['tz']);
if ($ab_config['rowid'] > 0) {
$ab_all_test = $antibot_db->query("SELECT rowid, * FROM rules WHERE search='timezone=".$ab_config['tz']."' OR rowid='".$ab_config['rowid']."' ORDER by priority ASC;");
} else {
$ab_all_test = $antibot_db->query("SELECT * FROM rules WHERE search='timezone=".$ab_config['tz']."';");
}
while ($echo = $ab_all_test->fetchArray(SQLITE3_ASSOC)) {
// так проверим, что таймзона найдена до другого dark правила:
if ($echo['disable'] == '0') {
if ($echo['search'] == 'timezone='.$ab_config['tz']) {
if ($echo['rule'] == 'dark') {
echo SaveResult('error', 'DARK By rule: timezone='.$ab_config['tz'], ''); abDie();
} elseif ($echo['rule'] == 'block') {
echo SaveResult('error', 'BLOCK By rule: timezone='.$ab_config['tz'], ''); abDie();
} elseif ($echo['rule'] == 'allow') {
// разрешающие куки:
$ab_config['hash'] = md5($ab_config['salt'].$ab_config['pass'].$ab_config['refhost'].$ab_config['useragent'].$ab_config['ip'].$ab_config['time']).'-'.$ab_config['time']; // код для куки
if ($ab_config['auth'] == 'sqlite') {
srand(crc32($ab_config['salt'].$ab_config['ip'].$ab_config['useragent']));
$antibot_cookie_db = new SQLite3(__DIR__.'/../data/cookie/'.rand(1,100).'.db'); 
srand();
$antibot_cookie_db->busyTimeout(1500);
$antibot_cookie_db->exec("PRAGMA journal_mode = WAL;");
$add = @$antibot_cookie_db->exec("INSERT INTO list (md5, date) VALUES ('".md5($ab_config['salt'].$ab_config['ip'].$ab_config['useragent'])."', '".$ab_config['time']."');");
}
echo SaveResult('cookie', 'ALLOW By rule: timezone='.$ab_config['tz'], $ab_config['hash']);
file_put_contents(__DIR__.'/../data/counters/auto_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
abDie();
} elseif ($echo['rule'] == 'gray') {
$ab_config['gray'] = 1;
}
}
}
}

// особое условие:
if ($ab_config['gray'] == 1 OR $ab_config['gray'] == 2) {echo SaveResult('error', 'gray', ''); abDie();}

// все это не может быть пустым:
if ($ab_config['v'] == '') {echo SaveResult('error', 'v', ''); abDie();}
if ($ab_config['w'] < 300) {echo SaveResult('error', 'Monitor Width', ''); abDie();}
if ($ab_config['h'] < 300) {echo SaveResult('error', 'Monitor Height', ''); abDie();}
if ($ab_config['cw'] < 250) {echo SaveResult('error', 'Browser Window Width', ''); abDie();}
if ($ab_config['ch'] < 250) {echo SaveResult('error', 'Browser Window Height', ''); abDie();}
if ($ab_config['co'] < 24) {echo SaveResult('error', 'Color Depth', ''); abDie();}
if ($ab_config['pi'] < 24) {echo SaveResult('error', 'Pixel Depth', ''); abDie();}
if ($ab_config['referer'] == '') {echo SaveResult('error', 'Empty Referer', ''); abDie();}
if ($ab_config['useragent'] == '') {echo SaveResult('error', 'Empty User-agent', ''); abDie();}
if ($ab_config['accept_lang'] == '') {echo SaveResult('error', 'Empty Lang', ''); abDie();}

//if ($ab_config['w'] < $ab_config['cw'] AND $ab_config['cw'] != $ab_config['h']) {echo SaveResult('error', 'Monitor width < Browser width', ''); abDie();}
if ($ab_config['hdc'] == 1) {echo SaveResult('error', 'Hosting or Bad IP', ''); abDie();}
if (isBot($ab_config['useragent'])) {echo SaveResult('error', 'Bot', ''); abDie();}

if ($h1 != hash('sha256', $ab_config['email'].$ab_config['pass'].$ab_config['refhost'].$ab_config['useragent'].$ab_config['ip'].$ab_config['date'])) {echo SaveResult('error', 'H1 Hash Error', ''); abDie();}

if ($test != hash('sha256', $ab_config['useragent'].$ab_config['ip'].$ab_config['date'].$ab_config['hosting'].$ab_config['country'].$ab_config['ptr'].$ab_config['salt'])) {echo SaveResult('error', 'Test Hash Error', ''); abDie();}

if ($ab_config['time'] - $ab_config['date'] > 30) {echo SaveResult('error', 'Long Request Error', ''); abDie();}

// тут еще сделать провеки (и в post.php тоже):
/* 
 * если ipv4 есть значит чекать его по базе 1.
 * если страна ipv4 есть и отличается от исходной чекать по 5 базе.
*/ 

// проверка рекапчи если включена:
if ($ab_config['re_check'] == 1) {
$data = array(
    'secret' => $ab_config['recaptcha_secret'],
    'response' => $ab_config['rct'],
    'remoteip' => $ab_config['ip']
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($ch, CURLOPT_REFERER, $ab_config['referer']);
curl_setopt($ch, CURLOPT_USERAGENT, $ab_config['useragent']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$re = @json_decode(trim(curl_exec($ch)), true);
curl_close($ch);

if (isset($re['score'])) {
$ab_config['score'] = trim($re['score']);
} else {
$ab_config['score'] = 0;
echo SaveResult('error', 'Recaptcha Error', ''); abDie();
}
}

// start cloud check, if it is turned on:
if ($ab_config['check'] == 1) {
$data = array();
$data['email'] = $ab_config['email']; // авторизация
$data['pass'] = $ab_config['pass']; // авторизация
$data['cid'] = $ab_config['cid'];
if ($ab_config['score'] > 0) {$data['score'] = $ab_config['score'];}
if ($ab_config['cfcountry'] != '') {$data['cfcountry'] = $ab_config['cfcountry'];}
$data['country'] = $ab_config['country']; // код страны из локальной базы
$data['ip'] = $ab_config['ip']; // ip из php, может быть ipv6
$data['v'] = $ab_config['v']; // версия антибота
$data['abv'] = $ab_config['abv']; // версия скрипта ab.php
$data['ptr'] = $ab_config['ptr']; // ptr
$data['w'] = $ab_config['w']; // ширина монитора
$data['h'] = $ab_config['h']; // высота монитора
$data['cw'] = $ab_config['cw']; // ширина окна браузера
$data['ch'] = $ab_config['ch']; // высота окна браузера
$data['co'] = $ab_config['co']; // colordepth
$data['pi'] = $ab_config['pi']; // pixeldepth
$data['ref'] = $ab_config['ref']; // реферер полностью (с ним пришли на сайт клиента)
$data['tz'] = $ab_config['tz']; // таймзона из JS
$data['adb'] = $ab_config['adb']; // 1 есть адблок
// код страны из ipdb.cloud если есть:
if ($ab_config['ipdbc'] != '') {$data['ipdbc'] = $ab_config['ipdbc'];}
// ipv4 из ipdb.cloud если есть:
if ($ab_config['ipv4'] != '') {$data['ipv4'] = $ab_config['ipv4'];}
$data['accept'] = $ab_config['accept']; // HTTP_ACCEPT
$data['referer'] = $ab_config['referer']; // домен, с которого запросили этот скрипт
$data['useragent'] = $ab_config['useragent']; // пустой юзерагент
$data['accept_lang'] = $ab_config['accept_lang']; // язык у норм браузеров есть всегда

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$ab_config['cloud_url'].'/9.php');
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($ch, CURLOPT_REFERER, $ab_config['scheme'].'://'.$ab_config['host'].$ab_config['uri']);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$resp = curl_exec($ch);
//file_put_contents(__DIR__.'/../cloud.txt', $resp, LOCK_EX);
$cloud = json_decode(trim(curl_exec($ch)), true);

curl_close($ch);
// disable cloud check:
if (isset($cloud['disable']) AND $cloud['disable'] == '1') {
file_put_contents(__DIR__.'/../data/disable.php', '<?php $ab_config[\'disable\'] = 1; // '.$ab_config['time'], LOCK_EX);
clearstatcache(true); // Clears file status cache
if(function_exists('opcache_reset')) {
@opcache_reset();
}
}
// не прошел облачную проверку:
if (isset($cloud['error'])) {echo SaveResult('error', $cloud['error'], ''); abDie();}

// Отобразить кнопки входа:
if (!isset($cloud['ok']) AND $ab_config['unresponsive'] == 1) {echo SaveResult('error', 'Cloud API unresponsive', ''); abDie();}
// Пропускать посетителя автоматически:
if (!isset($cloud['ok']) AND $ab_config['unresponsive'] == 0) {$ab_config['msg'] = 'Cloud API unresponsive';}

}
// end cloud check

if ($ab_config['score'] == 0.1 OR $ab_config['score'] == 0.3 OR $ab_config['score'] == 0.7) {
echo SaveResult('error', 'Recaptcha', ''); abDie();
}

// дальше успешный ответ:
$ab_config['hash'] = md5($ab_config['salt'].$ab_config['pass'].$ab_config['refhost'].$ab_config['useragent'].$ab_config['ip'].$ab_config['time']).'-'.$ab_config['time']; // код для куки


if ($ab_config['auth'] == 'sqlite') {
srand(crc32($ab_config['salt'].$ab_config['ip'].$ab_config['useragent']));
$antibot_cookie_db = new SQLite3(__DIR__.'/../data/cookie/'.rand(1,100).'.db'); 
srand();
$antibot_cookie_db->busyTimeout(1500);
$antibot_cookie_db->exec("PRAGMA journal_mode = WAL;");
$add = @$antibot_cookie_db->exec("INSERT INTO list (md5, date) VALUES ('".md5($ab_config['salt'].$ab_config['ip'].$ab_config['useragent'])."', '".$ab_config['time']."');");
}

echo SaveResult('cookie', $ab_config['msg'], $ab_config['hash']);
file_put_contents(__DIR__.'/../data/counters/auto_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
