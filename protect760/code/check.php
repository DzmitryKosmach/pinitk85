<?php
// страница проверки:
if (!isset($ab_version)) die('stop check');

// если post запрос к странице проверки, то все равно он нормально не будет обработан, редиректим на get:
if ($ab_config['request_method'] == 'POST') {
header('Location: '.$ab_config['uri']);
abDie();
}

// перевод заглушки на язык посетителя:
if ($ab_config['tpl_lang'] == '') {$ab_config['tpl_lang'] = $ab_config['lang'];}
if (file_exists(__DIR__.'/../lang/tpl/'.$ab_config['tpl_lang'].'.php')) {
require_once(__DIR__.'/../lang/tpl/'.$ab_config['tpl_lang'].'.php');
}

if ($ab_config['iframe_stop'] == 1) {header('X-Frame-Options: SAMEORIGIN');}

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex');
header($ab_config['protocol'].' '.$ab_config['error_headers'][$ab_config['header_test_code']]);
header('Status: '.$ab_config['error_headers'][$ab_config['header_test_code']]);

// подключение своего php кода:
require_once(__DIR__.'/../data/code.php');

// перенос статистики из файлов в базу:
$cron_update_time = (int) trim(@file_get_contents(__DIR__.'/../data/counters_update')) + 0;
if ($ab_config['time'] - $cron_update_time > 599) {
file_put_contents(__DIR__.'/../data/counters_update', $ab_config['time'], LOCK_EX);
clearstatcache(); // Clears file status cache
//if(function_exists('opcache_reset')) {
//@opcache_reset();
//}
require_once(__DIR__.'/cron.php');
}

// удаление старых записей из лога:
$crontimefile = __DIR__ . '/../data/crontimefile';
clearstatcache(true, $crontimefile); // очищаем кеш именно для этого файла
$lastRun = (int)@file_get_contents($crontimefile);
if (($ab_config['time'] - $lastRun) >= 21600) {
file_put_contents($crontimefile, $ab_config['time']);
if (is_shell_exec_available()) {
if ($ab_config['php_handler'] == '') {$ab_config['php_handler'] = PHP_BINDIR.'/php';}
shell_exec($ab_config['php_handler'].' -q '.dirname(dirname(__FILE__)).'/code/clear_old_hits.php > /dev/null 2>&1 &');
}
}

// счетчик показов страницы проверки:
file_put_contents(__DIR__.'/../data/counters/test_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);

// показываем заглушку:
$tpl = file_get_contents(__DIR__.'/../data/tpl.txt');
ob_start();
require_once(__DIR__.'/js.php');
$tpl_js = ob_get_clean();
$tpl = str_ireplace('<body>', '<body>', $tpl);
$tpl = str_ireplace('</body>', $tpl_js.'</body>', $tpl);
$tpl = str_ireplace('antibot-btn-success', 's'.md5('antibot-btn-success'.$ab_config['time']), $tpl);
$tpl = str_ireplace('antibot-btn-color', 's'.md5('antibot-btn-color'.$ab_config['time']), $tpl);
echo $tpl;
unset($tpl);

if ($ab_config['antibot_log_tests'] == 1) {
//запись в лог попавших на заглушку:
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
$sql = 'INSERT INTO hits (cid, date, ip, ptr, useragent, uid, country, city, referer, page, lang, generation, passed, recaptcha, js_w, js_h, js_cw, js_ch, js_co, js_pi, refhost, adblock, asnum, asname, result, http_accept, method, ym_uid, ga_uid, ip_short, hosting, hit) VALUES (\''.$ab_config['cid'].'\', \''.$ab_config['time'].'\', \''.$ab_config['ip'].'\', \''.$ab_config['ptr'].'\', \''.$ab_config['useragent'].'\', \''.$ab_config['uid'].'\', \''.$ab_config['country'].'\', \''.$ab_config['city'].'\', \''.$ab_config['save_referer'].'\', \''.$ab_config['save_page'].'\', \''.$ab_config['accept_lang'].'\', \''.$ab_exec_time.'\', \'0\', \'0\', \'\', \'\', \'\', \'\', \'\', \'\', \''.$ab_config['refhost'].'\', \'\', \''.$ab_config['asnum'].'\', \''.$ab_config['asname'].'\', \''.$ab_config['result'].'\', \''.$ab_config['http_accept'].'\', \''.$ab_config['request_method'].'\', \''.$ab_config['ym_uid'].'\', \''.$ab_config['ga_uid'].'\', \''.$ab_config['ip_short'].'\', \''.$ab_config['hosting'].'\', \''.$ab_config['antibot_hits'].'\');';

$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
$ab_exec_time = microtime(true) - $ab_start_time;
$ab_exec_time = round($ab_exec_time, 3);
echo '<!-- Time: '.$ab_exec_time.' Sec. -->';
//echo '<!-- '.memory_get_usage() .' '.memory_get_peak_usage().'-->';
