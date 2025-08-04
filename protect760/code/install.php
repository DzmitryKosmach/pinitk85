<?php
// Author: Mik Foxi admin@mikfoxi.com
// License: GNU GPL v3 - https://www.gnu.org/licenses/gpl-3.0.en.html
// Website: https://antibot.cloud/

// инсталятор базы
if (!isset($ab_version)) die('stop install');

if (!isset($checkdb)) {
$antibot_db = new SQLite3(__DIR__.'/../data/sqlite.db');
} else {
// реинстал базы:
@unlink(__DIR__.'/../data/sqlite2.db');
$antibot_db = new SQLite3(__DIR__.'/../data/sqlite2.db');
}
$antibot_db->busyTimeout(5000);
$antibot_db->exec("PRAGMA journal_mode = WAL;");

/* ipv4 правила:
priority - приоритет (индекс)
search - строка которую добавили, ip или ip/mask (уник)
ip1 - числовое представление стартового ip (индекс)
ip2 - числовое представление конечного ip (индекс)
rule - правило: allow, block, gray, dark
comment - просто коммент, чтоб понятнее было
expires - юникс дата истечения (потом удаляется), если навсегда, то 9999999999
disable - 0 не выключен, 1 правило выключено
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS ipv4rules (
priority INTEGER NOT NULL default '100', 
search TEXT UNIQUE NOT NULL default '', 
ip1 INTEGER NOT NULL default '', 
ip2 INTEGER NOT NULL default '', 
rule TEXT NOT NULL default '', 
comment TEXT NOT NULL default '', 
expires INTEGER NOT NULL default '9999999999', 
disable INTEGER NOT NULL default '0'
);");
// индексы:
$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS ipv4range_disabled_index ON ipv4rules (disable, ip1, ip2);"); 

if (!isset($checkdb)) {
// дефолтные ip сервера:
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '127.0.0.1', '".AbIp2num('127.0.0.1')."', '".AbIp2num('127.0.0.1')."', 'allow', 'Local IP', '9999999999');");

if (filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '".$_SERVER['SERVER_ADDR']."', '".AbIp2num($_SERVER['SERVER_ADDR'])."', '".AbIp2num($_SERVER['SERVER_ADDR'])."', 'allow', 'Local IP from SERVER_ADDR', '9999999999');");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://ipv4.mikfoxi.com/');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$serverIPv4 = @trim(strip_tags(curl_exec($ch)));
curl_close($ch);
if (filter_var($serverIPv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '".$serverIPv4."', '".AbIp2num($serverIPv4)."', '".AbIp2num($serverIPv4)."', 'allow', 'Server IPv4', '9999999999');");
}
}

/* ipv6 правила:
priority - приоритет (индекс)
search - строка которую добавили, ip или ip/mask (уник)
ip1 - числовое представление стартового ip (индекс)
ip2 - числовое представление конечного ip (индекс)
rule - правило: allow, block, gray, dark
comment - просто коммент, чтоб понятнее было
expires - юникс дата истечения (потом удаляется), если навсегда, то 9999999999
disable - 0 не выключен, 1 правило выключено
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS ipv6rules (
priority INTEGER NOT NULL default '100', 
search TEXT UNIQUE NOT NULL default '', 
ip1 INTEGER NOT NULL default '', 
ip2 INTEGER NOT NULL default '', 
rule TEXT NOT NULL default '', 
comment TEXT NOT NULL default '', 
expires INTEGER NOT NULL default '9999999999', 
disable INTEGER NOT NULL default '0'
);");
// индексы:
//$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS priority6 ON ipv6rules (priority);"); 
//$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS ipv6index1 ON ipv6rules (ip1);"); 
//$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS ipv6index2 ON ipv6rules (ip2);"); 
$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS ipv6range_disabled_index ON ipv6rules (disable, ip1, ip2);"); 

if (!isset($checkdb)) {
// дефолтные ip сервера:
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '".abExpand('::1')."', '".AbIp2num(abExpand('::1'))."', '".AbIp2num(abExpand('::1'))."', 'allow', 'Local IP ::1', '9999999999');");

if (filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '".abExpand($_SERVER['SERVER_ADDR'])."', '".AbIp2num(abExpand($_SERVER['SERVER_ADDR']))."', '".AbIp2num(abExpand($_SERVER['SERVER_ADDR']))."', 'allow', 'Local IP from SERVER_ADDR', '9999999999');");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://ipv6.mikfoxi.com/');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$serverIPv6 = @trim(strip_tags(curl_exec($ch)));
curl_close($ch);
if (filter_var($serverIPv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '".abExpand($serverIPv6)."', '".AbIp2num(abExpand($serverIPv6))."', '".AbIp2num(abExpand($serverIPv6))."', 'allow', 'Server IPv6', '9999999999');");
}
}

/*
rules - все прочие правила:
* 
priority - приоритет (индекс)
type - тип данных: useragent, country, lang (2 зн код), referer (хост), ptr, asname, asnum, uri, scriptname, httpaccept
data - значение (может быть пустым)
search - строка которую ищем по точному совпадению (уник) вида: referer=iframe-toloka.com (индекс)
rule - правило: allow, block, gray, dark
comment - просто коммент, чтоб понятнее было
expires - юникс дата истечения (потом удаляется), если навсегда, то 9999999999
disable - 0 не выключен, 1 правило выключено
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS rules (
priority INTEGER NOT NULL default '1', 
type TEXT NOT NULL default '', 
data TEXT NOT NULL default '', 
search TEXT UNIQUE NOT NULL default '', 
rule TEXT NOT NULL default '', 
comment TEXT NOT NULL default '', 
expires INTEGER NOT NULL default '9999999999', 
disable INTEGER NOT NULL default '0'
);");

$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS i_priority ON rules (priority);"); 
$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS i_search ON rules (search);");

if (!isset($checkdb)) {
$add = @$antibot_db->exec("INSERT INTO rules (priority, type, data, search, rule, comment) VALUES ('10', 'useragent', '', 'useragent=', 'block', 'Empty User-Agent');");
$add = @$antibot_db->exec("INSERT INTO rules (priority, type, data, search, rule, comment) VALUES ('10', 'lang', '', 'lang=', 'block', 'Empty Language');");
$add = @$antibot_db->exec("INSERT INTO rules (priority, type, data, search, rule, comment) VALUES ('10', 'asname', 'Biterika', 'asname=Biterika', 'block', 'Spam ASN');");
$add = @$antibot_db->exec("INSERT INTO rules (priority, type, data, search, rule, comment) VALUES ('11', 'referer', '', 'referer=', 'dark', 'Empty Referer');");
}

// ---------------------------------------------------------------------

// основной лог, таблица hits:
/*
cid - id клика, из микротайм, уник, PRIMARY KEY, вида 1698917229.3233
date - unix дата
ip - полный адрес 127.0.0.1 + индекс по этому полю
ipv4 - если ip = ipv6 то тут писать ipv4 из ipdb
ptr - userhost.com. (для заглушки из пхп)
useragent - полностью
uid - хэш из cookie, хэг от ипа и юзерагента, для слежения за юзером
country - страна (код страны типа: RU)
city - город, на латинице, если определен
referer - реферер полностью
page - текущая страница полностью
lang - язык полностью
generation - время генерации скрипта антибота (include.php)
generation2 - время генерации ab.php
passed - INTEGER + индекс по этому полю
* получил заглушку и остался на ней (0 - STOP)
* прошел проверку автоматически в облаке или ab.php (1 - AUTO)
* прошел кликом (2 - CLICK)
* если это хит мимо заглушки имеющих куки (3 - LOCAL)
* если прошли по разрешающему правилу (4 - ALLOW)
* белый бот (по ip и из конфига) (5 - GOODIP)
* заблокированные по правилам или из конфига (6 - BLOCK)
* фейк боты (маскирующиеся белыми ботами) (7 - FAKE)
* кликнули с ошибкой (8 - MISS)
recaptcha - рейтинг score, 
js_w - ширина монитора (из JS)
js_h - высота монитора (из JS)
js_cw - ширина окна браузера (из JS)
js_ch - высота окна браузера (из JS)
js_co - colordepth (из JS)
js_pi - pixeldepth (из JS)
refhost - хост реферера
adblock - включен блокировщик рекламы (1) или нет (0)  (из JS)
asnum - номер блока ip провайдера
asname - название провайдера
result - причина блокировки из облака (из JS) или слово GRAY
http_accept - типа text/html,application/xhtml+xml,application/xml;q=0.9
method - GET, POST, PUT, HEAD, DELETE, TRACE, OPTIONS, CONNECT
ym_uid - ClientID яндекс метрики, из cookie $_COOKIE['_ym_uid'] вида: 1620997730486452159 (число)
ga_uid - идентификатор клиента в Google Analytics из cookie $_COOKIE['_ga'] вида "GA1.2.1709431122.1631721710"
ip_short - укороченный ipv4/24 или ipv6/64
hosting - 1 хостинговый или хреновый ип, 0 нет.
hit - номер текущего хита для счетчика хитов
cookie
timezone - таймзона вида Asia/Tbilisi
distance
*/
// основная таблица, добавить параметр UNINDEXED к ненужным полям по которым нету поиска:
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS hits (
cid TEXT NOT NULL PRIMARY KEY default '', 
date INTEGER NOT NULL default '', 
ip TEXT NOT NULL default '', 
ipv4 TEXT NOT NULL default '', 
ptr TEXT NOT NULL default '', 
useragent TEXT NOT NULL default '', 
uid TEXT NOT NULL default '', 
country TEXT NOT NULL default '', 
city TEXT NOT NULL default '', 
referer TEXT NOT NULL default '', 
page TEXT NOT NULL default '', 
lang TEXT NOT NULL default '', 
generation TEXT NOT NULL default '0', 
generation2 TEXT NOT NULL default '0', 
passed INTEGER NOT NULL default '0', 
recaptcha TEXT NOT NULL default '0', 
js_w INTEGER NOT NULL default '', 
js_h INTEGER NOT NULL default '', 
js_cw INTEGER NOT NULL default '', 
js_ch INTEGER NOT NULL default '', 
js_co INTEGER NOT NULL default '', 
js_pi INTEGER NOT NULL default '', 
refhost TEXT NOT NULL default '', 
adblock INTEGER NOT NULL default '', 
asnum TEXT NOT NULL default '', 
asname TEXT NOT NULL default '', 
result TEXT NOT NULL default '', 
http_accept TEXT NOT NULL default '', 
method TEXT NOT NULL default '', 
ym_uid TEXT NOT NULL default '', 
ga_uid TEXT NOT NULL default '', 
ip_short TEXT NOT NULL default '', 
hosting INTEGER NOT NULL default '0', 
hit INTEGER NOT NULL default '0',
timezone TEXT NOT NULL default '', 
cookie TEXT NOT NULL default '', 
distance TEXT NOT NULL default ''
);");

$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS i_ip ON hits (ip);"); 
$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS i_passed ON hits (passed);"); 
$index = $antibot_db->exec("CREATE INDEX IF NOT EXISTS i_date ON hits (date);"); 

/*
таблица счетчиков (в некотором роде кеш) по датам:
обновлять кроном
* 
date - дата вида 20191223
test - колво попавших на заглушку
auto - колво автоматически прошедших заглушку
click - колво успешно кликнувших на кнопку
miss - колво ошибочно кликнувших
allow - колво прошедших по общим белым правилам
goodip - колво белых ботов из конфига и по базе белых ip
local - колво прошедших имеющих cookie
uusers - колво уников (подсчет по ip: AUTO CLICK ALLOW LOCAL)
husers - колво хитов (подсчет по ip: AUTO CLICK ALLOW LOCAL)
block - колво хитов забаненных (страна, язык, реферер, ptr)
fakes - колво хитов фейк ботов
google - кол-во хитов гугл бота
yandex - кол-во хитов яндекс бота
bing - кол-во хитов бинг бота
adbpercent - процентов уникальных IP со старусом AUTO и CLICK с блокировщиком рекламы
sqlerror - кол-во sql ошибок антибота (в основном при вставке лог записей)
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS counters (
date INTEGER UNIQUE NOT NULL default '', 
test INTEGER NOT NULL default '0', 
auto INTEGER NOT NULL default '0', 
click INTEGER NOT NULL default '0', 
miss INTEGER NOT NULL default '0', 
allow INTEGER NOT NULL default '0', 
goodip INTEGER NOT NULL default '0', 
local INTEGER NOT NULL default '0', 
uusers INTEGER NOT NULL default '0', 
husers INTEGER NOT NULL default '0', 
block INTEGER NOT NULL default '0', 
fakes INTEGER NOT NULL default '0', 
google INTEGER NOT NULL default '0', 
yandex INTEGER NOT NULL default '0', 
bing INTEGER NOT NULL default '0', 
adbpercent TEXT NOT NULL default '', 
sqlerror INTEGER NOT NULL default '0'
);");

/*
priority - приоритет
search - часть юзерагента
data - список валидных ptr
rule - правило: allow, block, gray, dark
comment - просто коммент, чтоб понятнее было
disable - 0 не выключен, 1 правило выключено
* 
в php файл сохранять кэш в 2 массива ab_se как было в конфиге и массив ab_rule в котором search и rule
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS ab_se (
priority INTEGER NOT NULL default '100', 
search TEXT UNIQUE NOT NULL default '', 
data TEXT NOT NULL default '', 
rule TEXT NOT NULL default '', 
comment TEXT NOT NULL default '', 
disable INTEGER NOT NULL default '0'
);");

if (!isset($checkdb)) {
// дефолтные поисковики:
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'Googlebot', '.googlebot.com', 'allow', 'GoogleBot main indexer', '0');");
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'yandex.com', '.yandex.ru .yandex.net .yandex.com', 'allow', 'All Yandex bots', '0');");
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'bingbot', 'search.msn.com', 'allow', 'Bing.com indexer', '0');");
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'msnbot', 'search.msn.com', 'allow', 'Additional Indexer Bing.com', '0');");
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'Google-Site-Verification', '.googlebot.com .google.com', 'allow', 'Check for Google Search Console', '0');");

$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'Chrome-Lighthouse', '.google.com', 'allow', 'PageSpeed Insights: https://pagespeed.web.dev/', '1');");
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'Google-InspectionTool', '.googlebot.com', 'allow', 'Search Console', '1');");
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment, disable) VALUES ('10', 'Mediapartners', '.googlebot.com .google.com', 'allow', 'AdSense bot', '1');");
$se_save = '<?php
$ab_rule[\'Googlebot\'] = \'allow\';
$ab_se[\'Googlebot\'] = array(\'.googlebot.com\');
$ab_rule[\'yandex.com\'] = \'allow\';
$ab_se[\'yandex.com\'] = array(\'.yandex.ru\', \'.yandex.net\', \'.yandex.com\');
$ab_rule[\'bingbot\'] = \'allow\';
$ab_se[\'bingbot\'] = array(\'search.msn.com\');
$ab_rule[\'msnbot\'] = \'allow\';
$ab_se[\'msnbot\'] = array(\'search.msn.com\');
$ab_rule[\'Google-Site-Verification\'] = \'allow\';
$ab_se[\'Google-Site-Verification\'] = array(\'.googlebot.com\', \'.google.com\');
';
file_put_contents(__DIR__.'/../data/se.php', $se_save, LOCK_EX);
}

/*
priority - приоритет
search - часть урла вида /wp-content/uploads/
rule - правило: allow, block, gray, dark
comment - просто коммент, чтоб понятнее было
disable - 0 не выключен, 1 правило выключено
* 
в php файл сохранять кэш массива ab_path в котором search и rule
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS ab_path (
priority INTEGER NOT NULL default '100', 
search TEXT UNIQUE NOT NULL default '', 
rule TEXT NOT NULL default '', 
comment TEXT NOT NULL default '',
disable INTEGER NOT NULL default '0'
);");

if (!isset($checkdb)) {
file_put_contents(__DIR__.'/../data/path.php', '<?php'."\n", LOCK_EX);


if (file_exists(__DIR__.'/../../wp-config.php')) {
// это вордпресс:
$add = @$antibot_db->exec("INSERT INTO ab_path (priority, search, rule, comment, disable) VALUES ('10', '/wp-cron.php', 'allow', 'for WordPress', '0');");
$add = @$antibot_db->exec("INSERT INTO ab_path (priority, search, rule, comment, disable) VALUES ('10', '/wp-admin/admin-ajax.php', 'allow', 'for WordPress', '0');");
$add = @$antibot_db->exec("INSERT INTO ab_path (priority, search, rule, comment, disable) VALUES ('10', '/wp-admin/post.php', 'allow', 'for WordPress', '0');");
$for_wp = '<?php
$ab_path[\'/wp-cron.php\'] = \'allow\';
$ab_path[\'/wp-admin/admin-ajax.php\'] = \'allow\';
$ab_path[\'/wp-admin/post.php\'] = \'allow\';
';
if (is_dir(__DIR__.'/../../wp-content/plugins/woocommerce')) {
$add = @$antibot_db->exec("INSERT INTO ab_path (priority, search, rule, comment, disable) VALUES ('10', '/?wc-ajax=', 'allow', 'for WooCommerce', '0');");
$for_wp .= '$ab_path[\'/?wc-ajax=\'] = \'allow\';
';
}
file_put_contents(__DIR__.'/../data/path.php', $for_wp, LOCK_EX);
} elseif (is_dir(__DIR__.'/../../bitrix')) {
// это битрикс:
$add = @$antibot_db->exec("INSERT INTO ab_path (priority, search, rule, comment, disable) VALUES ('10', '/bitrix/admin/site_checker.php', 'allow', 'for Bitrix', '0');");
$for_bitrix = '<?php
$ab_path[\'/bitrix/admin/site_checker.php\'] = \'allow\';
';
file_put_contents(__DIR__.'/../data/path.php', $for_bitrix, LOCK_EX);
}
}

/*
k - 127.0.0.0/24
v - HTTP_X_REAL_IP
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS ab_proxy (
k TEXT UNIQUE NOT NULL default '', 
v TEXT NOT NULL default ''
);");

if (!isset($checkdb)) {
file_put_contents(__DIR__.'/../data/proxy.php', '<?php'."\n", LOCK_EX);
}

/* лог авторизаций:
date - юникс дата
ip - 
country - код страны
result - 1 успешная авторизация, 0 ошибка авторизации
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS auth_log (
date INTEGER NOT NULL default '', 
ip TEXT NOT NULL default '', 
country TEXT NOT NULL default '', 
result TEXT NOT NULL default ''
);");

/* база кэша ptr:
ip - v4 или v6
ptr - значение
date - дата в юникс
etime - время выполнения функции получения ptr
*/
$query = $antibot_db->exec("CREATE TABLE IF NOT EXISTS ptrcache (
ip TEXT UNIQUE NOT NULL default '', 
ptr TEXT NOT NULL default '', 
date INTEGER NOT NULL default '0', 
etime TEXT NOT NULL default '0'
);");

if (!isset($checkdb)) {
echo 'The database is installed.
<script>location.reload();</script>';
}
