<?php
// Author: Mik Foxi admin@mikfoxi.com
// License: GNU GPL v3 - https://www.gnu.org/licenses/gpl-3.0.en.html
// Website: https://antibot.cloud/

// admin panel
set_time_limit(1200);
ignore_user_abort(true);
header('Content-Type: text/html; charset=UTF-8');
define('ANTIBOT', 1);
define('ANTIBOT_ADMIN', 1);

$start_time = microtime(true);
$ab_config['cms'] = 'antibot';
$abp = 'abp';
$abp_get = array(); // добавочные гет переменные
$abw = '';
$error_msg = '';
foreach ($abp_get as $k => $v) {
$abw .= $k.'='.$v.'&'; // подставлять в урл
}

if (file_exists(__DIR__.'/data/beta')) {$beta = 1;} else {$beta = 0;}

if (!defined('ANTIBOT_INCLUDE')) {
require_once(__DIR__.'/code/include.php');
}

// коннект к базе:
$antibot_db = new SQLite3(__DIR__.'/data/sqlite.db'); 
$antibot_db->busyTimeout(2000);
$antibot_db->exec("PRAGMA journal_mode = WAL;");

@include(__DIR__.'/data/disable.php');

$ab_webdir = dirname($ab_config['uri']); // веб путь до папки антибота (без закрывающего слэша)

$host = isset($_SERVER['HTTP_HOST']) ? preg_replace("/[^0-9a-z-.:]/","", $_SERVER['HTTP_HOST']) : '';

// язык админки:
$lang_code = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr(mb_strtolower(trim(preg_replace("/[^a-zA-Z]/","",$_SERVER['HTTP_ACCEPT_LANGUAGE'])), 'UTF-8'), 0, 2, 'utf-8') : 'en'; // 2 первых символа
$lang_code = isset($_COOKIE['antibot_lang_code']) ? mb_substr(mb_strtolower(trim(preg_replace("/[^a-zA-Z]/","",$_COOKIE['antibot_lang_code'])), 'UTF-8'), 0, 2, 'utf-8') : $lang_code;

// имя админской куки:
$auth_adm_token = 'a'.md5($ab_config['salt'].'auth_adm_token');

// перевод на язык посетителя:
if (file_exists(__DIR__.'/lang/adm/'.$lang_code.'.php')) {
require_once(__DIR__.'/lang/adm/'.$lang_code.'.php');
} else {
$lang_code = 'en';
}

if ($ab_config['email'] == '' OR $ab_config['pass'] == '') abDie('EMAIL or PASS not set in '.__DIR__.'/data/conf.php');

// пост запрос авторизации (установки cookie):
if (isset($_POST['auth_post'])) {
$auth_user = isset($_POST['auth_user']) ? trim($_POST['auth_user']) : ''; // email
$auth_user = mb_strtolower($auth_user, 'utf-8'); // переводим в нижний регистр
$auth_pass = isset($_POST['auth_pass']) ? trim($_POST['auth_pass']) : ''; // pass
$auth_second_pass = isset($_POST['auth_second_pass']) ? trim($_POST['auth_second_pass']) : ''; // second pass
if ($auth_second_pass != '') {$auth_second_pass = md5('antibot'.$auth_second_pass);}

$token = md5($auth_user.$ab_config['accept_lang'].$ab_config['useragent'].$ab_config['ip'].$auth_pass.$ab_config['host'].$ab_config['salt'].$auth_second_pass); // токен, основанный на post данных

if ($ab_config['secondpass'] != '' AND $ab_config['secondpass'] != $auth_second_pass) {
$token = 'badsecondpass';
$error_msg = abTranslate('Authorisation Error');
for ($x = 0; $x < 5; $x++) {
$add = @$antibot_db->exec("INSERT INTO auth_log (date, ip, country, result) VALUES ('".$ab_config['time']."', '".$ab_config['ip']."', '".$ab_config['country']."', '0');");
if ($antibot_db->lastErrorMsg() != 'database is locked') break;
}
} elseif ($token != md5($ab_config['email'].$ab_config['accept_lang'].$ab_config['useragent'].$ab_config['ip'].$ab_config['pass'].$ab_config['host'].$ab_config['salt'].$ab_config['secondpass'])) {
$token = 'badpass';
$error_msg = abTranslate('Authorisation Error');
for ($x = 0; $x < 5; $x++) {
$add = @$antibot_db->exec("INSERT INTO auth_log (date, ip, country, result) VALUES ('".$ab_config['time']."', '".$ab_config['ip']."', '".$ab_config['country']."', '0');");
if ($antibot_db->lastErrorMsg() != 'database is locked') break;
}
} else {
absetcookie($auth_adm_token, $token, $ab_config['time']+864000, false);
for ($x = 0; $x < 5; $x++) {
$add = @$antibot_db->exec("INSERT INTO auth_log (date, ip, country, result) VALUES ('".$ab_config['time']."', '".$ab_config['ip']."', '".$ab_config['country']."', '1');");
if ($antibot_db->lastErrorMsg() != 'database is locked') break;
}
}
} else {
$token = isset($_COOKIE[$auth_adm_token]) ? trim($_COOKIE[$auth_adm_token]) : ''; // token из cookie
}

// проверка авторизации:
if ($token != md5($ab_config['email'].$ab_config['accept_lang'].$ab_config['useragent'].$ab_config['ip'].$ab_config['pass'].$ab_config['host'].$ab_config['salt'].$ab_config['secondpass'])) {
require_once(__DIR__.'/code/loginform.php');
abDie();
}

// если включен демо режим, то ничего нельзя изменять:
if ($ab_config['demo'] == 1 AND $_SERVER['REQUEST_METHOD'] == 'POST') {
echo '<script>document.location.href="?'.$abw.$abp.'=index";</script>';
abDie();
}

$content = '';
// страница админки
$page = isset($_GET[$abp]) ? preg_replace("/[^0-9a-z]/","",trim($_GET[$abp])) : 'index';
if (!file_exists(__DIR__.'/adm/'.$page.'.php')) {$page = 'index';}
require_once(__DIR__.'/adm/'.$page.'.php');

// metrika.yandex.ru
file_put_contents(__DIR__.'/data/ip.php', '<?php // '.$ab_config['ip'], LOCK_EX);

echo '<!DOCTYPE html>
<html lang="'.abTranslate('en').'">
<head>
<title>'.$title.' - '.$host.'</title>
<meta charset="utf-8">
<meta name="referrer" content="unsafe-url" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="'.$ab_config['webdir'].'static/bootstrap4.min.css">
<link rel="stylesheet" href="'.$ab_config['webdir'].'static/bootstrap-icons.min.css">
<link rel="stylesheet" href="'.$ab_config['webdir'].'static/freakflags.css">
<link rel="icon" href="'.((file_exists('../favicon.ico')) ? '/favicon.ico' : 'data:,').'">
<style>
body {overflow-y: scroll;}
.pngflag {height: 16px; border: 1px solid #C0C0C0;}
</style>
</head>
<body class="bg-light">
<main role="main" class="container-fluid" style="max-width:1350px;">
<nav class="my-3 navbar navbar-dark bg-dark rounded shadow-sm">
  <a class="navbar-brand" href="/">'.$host.' <sup><small>'.$ab_version.' '.(($beta == 1) ? '<span style="color:red;">BETA</span>' : '').'</small></sup></a>
<span class="navbar-text"> 
<a href="?'.$abw.$abp.'=lang&lang=ru&rand='.$start_time.'" title="на Русском"><div class="fflag fflag-RU ff-lg"></div></a> 
<a href="?'.$abw.$abp.'=lang&lang=en&rand='.$start_time.'" title="in English"><div class="fflag fflag-US ff-lg"></div></a> 
</span>
</nav>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=index" '.(($page == 'index') ? 'class="text-secondary"' : '').'>'.abTranslate('Home').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=checklist" '.(($page == 'checklist') ? 'class="text-secondary"' : '').'>'.abTranslate('Check List').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=top" '.(($page == 'top') ? 'class="text-secondary"' : '').'>'.abTranslate('Top').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=counters" '.(($page == 'counters') ? 'class="text-secondary"' : '').'>'.abTranslate('Statistics').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=hits" '.(($page == 'hits') ? 'class="text-secondary"' : '').'>'.abTranslate('Access Log').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=rules" '.(($page == 'rules') ? 'class="text-secondary"' : '').'>'.abTranslate('Rules').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=conf" '.(($page == 'conf') ? 'class="text-secondary"' : '').'>'.abTranslate('Config').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=tpl" '.(($page == 'tpl') ? 'class="text-secondary"' : '').'>tpl.txt</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=error" '.(($page == 'error') ? 'class="text-secondary"' : '').'>error.txt</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=update" '.(($page == 'update') ? 'class="text-secondary"' : '').'>'.abTranslate('Update').'</a></li>
<li class="breadcrumb-item"><a href="?'.$abw.$abp.'=exit&rand='.$start_time.'">'.abTranslate('Log out').'</a></li>
</ol>
</nav>';

echo '<div class="my-3 p-3 bg-white rounded shadow-sm">
<span id="auth_msg"></span>
';

if ($ab_config['demo'] == 1) {
echo '<div class="alert alert-warning" role="alert">
<i class="bi bi-exclamation-triangle-fill"></i> '.abTranslate('The admin panel is in demo mode. The ability to make changes (delete data, change settings) is disabled.').'
</div>';
}
echo $content;
$exec_time = microtime(true) - $start_time;
$exec_time = round($exec_time, 3);
echo '</div></main>
<br />
<footer class="container border-top text-center text-muted">
        <div class="row">
          <div class="col-12">
<small>
<a href="https://'.$ab_config['main_url'].'/FAQ/" target="_blank" rel="noopener">'.abTranslate('Frequently Asked Questions').'</a> | '.abTranslate('Server Time:').' '.date('d.m.Y H:i:s', $ab_config['time']).' | 
'.abTranslate('Execution Time:').' '.$exec_time.' '.abTranslate('sec.').'<br />
</small>
</div>
</div>
      </footer>
<br />
<script>function check() { return confirm("'.abTranslate('Are you sure you want to delete these records?').'") }</script>
<script>var current_version = '.$ab_version.';</script>
<script src="https://'.$ab_config['main_url'].'/v.php?h1='.hash('sha256', $ab_config['email'].$ab_config['pass'].$ab_config['useragent'].$ab_config['accept_lang'].$ab_config['time']).'&h2='.md5('Antibot:'.$ab_config['email']).'&date='.$ab_config['time'].'&v='.$ab_version.'&lang='.$lang_code.'" async></script>
</body>
</html>';
abDie();
