<?php
// Author: Mik Foxi admin@mikfoxi.com
// License: GNU GPL v3 - https://www.gnu.org/licenses/gpl-3.0.en.html
// Website: https://antibot.cloud/

if (!isset($ab_version)) die('stop');

// локальная страница проверки через click
ignore_user_abort(true);
header('Content-Type: text/html; charset=UTF-8');
//header('Expires: Thu, 18 Aug 1994 05:00:00 GMT');
//header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('X-Robots-Tag: noindex');


if ($ab_config['input_button'] == 1) abDie('{"error": "Input Button Disabled"}');
if ($_SERVER['REQUEST_METHOD'] != 'POST') {abDie('{"error": "Error NoPost"}');}

// коннект к базе:
$antibot_db = new SQLite3(__DIR__.'/../data/sqlite.db'); 
$antibot_db->busyTimeout(1500);
$antibot_db->exec("PRAGMA journal_mode = WAL;");

$_POST['cid'] = isset($_POST['cid']) ? trim(preg_replace("/[^0-9\.]/","", $_POST['cid'])) : abDie('{"error": "CID not set"}');

$ab_config['ban'] = 0;

// юзерагент:
$ab_config['useragent'] = isset($_POST['useragent']) ? trim(strip_tags($_POST['useragent'])) : '';

$_POST['ip'] = isset($_POST['ip']) ? trim(preg_replace("/[^0-9a-zA-Z\.\:]/","", $_POST['ip'])) : abDie('{"error": "IP not set"}');
// проверка валидности ip:
if (filter_var($_POST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
$ab_config['ipv'] = 4;
} elseif (filter_var($_POST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
$ab_config['ipv'] = 6;
} else {
abDie('{"error": "Bad IP"}');
}

$_POST['xxx'] = isset($_POST['xxx']) ? trim(strip_tags($_POST['xxx'])) : abDie('{"error": "XXX not set"}');
$_POST['date'] = isset($_POST['date']) ? (int)trim(strip_tags($_POST['date'])) : abDie('{"error": "DATE not set"}');

$_POST['country'] = isset($_POST['country']) ? trim(preg_replace("/[^A-Z]/","", $_POST['country'])) : 'XX';

$referer = isset($_SERVER['HTTP_REFERER']) ? strip_tags(trim($_SERVER['HTTP_REFERER'])) : '';
if ($referer == '') {abDie('{"error": "Referer not set"}');}
// домен (host) с которого вызвали скрипт:
$refhost = parse_url($referer, PHP_URL_HOST);

if ($ab_config['time'] - $_POST['date'] > 180) abDie('{"cookie":"000"}');

if ($ab_config['buttons'] == 3 OR $ab_config['buttons'] == 4) {
// ReCAPTCHA v2 + кнопка "Я не робот"
$g_recaptcha_response = isset($_POST['g-recaptcha-response']) ? strip_tags(trim($_POST['g-recaptcha-response'])) : '';
$data = array(
    'secret' => $ab_config['recaptcha_secret2'],
    'response' => $g_recaptcha_response,
    'remoteip' => $_POST['ip']
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_REFERER, '');
curl_setopt($ch, CURLOPT_USERAGENT, $ab_config['useragent']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$re = @json_decode(trim(curl_exec($ch)), true);
curl_close($ch);
if (isset($re['success']) AND $re['success'] != 1) {
$ab_config['ban'] = 1;
}
}

// пароль шифрования:
$tmppassword = hash('sha256', $ab_config['salt'].$_POST['date'].$ab_config['pass'].$_POST['ip']);
// временная запись лога:
//file_put_contents(__DIR__.'/../data/decrypterror.txt', '2 '.$ab_config['salt'].' '.$_POST['date'].' '.$ab_config['pass'].' '.$_POST['ip']."\n", FILE_APPEND | LOCK_EX);

$ab_config['decrypted'] = ab_decrypt($_POST['xxx'], $tmppassword);
if ($ab_config['decrypted'] === false) {
abDie('{"error": "Decrypt Error"}');
}

$ab_config['xxx'] = explode('|', $ab_config['decrypted']);

if (!isset($ab_config['xxx'][1]) OR $ab_config['xxx'][1] != $ab_config['xxx'][0]) {
$ab_config['ban'] = 1;
}

if ($ab_config['ban'] == 1) {
// проверка ip по логу: $ab_config['time']
$fromdate = $ab_config['time']  - 86401;
$miss_count = $antibot_db->querySingle("SELECT count(rowid) FROM hits WHERE date >= '".$fromdate."' AND ip = '".$_POST['ip']."' AND passed='8';");
$miss_count = (int)$miss_count;

if ($miss_count > 0) {$ab_config['time_ban'] = $ab_config['time_ban_2'];}

// перевод времени в минуты:
$ab_config['time_ban'] = explode('.', $ab_config['time_ban']);
if (isset($ab_config['time_ban'][1])) {
// есть минуты
$ab_config['time_ban'] = $ab_config['time_ban'][0]*60*60+$ab_config['time_ban'][1]*60; // итого в сек
} else {
// только часы
$ab_config['time_ban'] = $ab_config['time_ban'][0]*60*60; // итого в сек
}

if ($ab_config['time_ban'] == 0) {$ab_config['time_ban'] = 8;}

// время для бана теперь в секундах.

$sql = 'INSERT INTO ipv'.$ab_config['ipv'].'rules (priority, search, ip1, ip2, rule, comment, expires) VALUES (\'1\', \''.$_POST['ip'].'\', \''.AbIp2num($_POST['ip']).'\', \''.AbIp2num($_POST['ip']).'\', \'block\', \'Wrong Click '.$_POST['country'].'\', \''.($ab_config['time_ban']+$ab_config['time']).'\');';
$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}


// обновление лога miss:
if ($ab_config['antibot_log_tests'] == 1) {
$sql = 'UPDATE hits SET passed=\'8\' WHERE passed=\'0\' AND cid=\''.$_POST['cid'].'\';';
$update = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
// счетчик криво кликнувших:
file_put_contents(__DIR__.'/../data/counters/miss_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
abDie('{"error": "Wrong Click"}');
} else {
// обновление лога о проходе по клику:
if ($ab_config['antibot_log_tests'] == 1) {
$sql = 'UPDATE hits SET passed=\'2\' WHERE passed=\'0\' AND cid=\''.$_POST['cid'].'\';';
$update = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
//if ($antibot_db->changes() != 1) {
//$ok = 0;
//}
}
// счетчик прошедших заглушку по клику:
file_put_contents(__DIR__.'/../data/counters/click_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);

$hash = md5($ab_config['salt'].$ab_config['pass'].$refhost.$ab_config['useragent'].$_POST['ip'].$ab_config['time']).'-'.$ab_config['time']; // код для куки

if ($ab_config['auth'] == 'sqlite') {
srand(crc32($ab_config['salt'].$_POST['ip'].$ab_config['useragent']));
$antibot_cookie_db = new SQLite3(__DIR__.'/../data/cookie/'.rand(1,100).'.db'); 
srand();
$antibot_cookie_db->busyTimeout(1500);
$antibot_cookie_db->exec("PRAGMA journal_mode = WAL;");
$add = @$antibot_cookie_db->exec("INSERT INTO list (md5, date) VALUES ('".md5($ab_config['salt'].$_POST['ip'].$ab_config['useragent'])."', '".$ab_config['time']."');");
}

echo '{"cookie":"'.$hash.'"}';
}
