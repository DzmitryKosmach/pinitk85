<?php
// страница блокировки:
if (!isset($ab_version)) die('stop block');

if ($ab_config['antibot_log_block'] == 1) {
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
$sql = 'INSERT INTO hits (cid, date, ip, ptr, useragent, uid, country, city, referer, page, lang, generation, passed, recaptcha, js_w, js_h, js_cw, js_ch, js_co, js_pi, refhost, adblock, asnum, asname, result, http_accept, method, ym_uid, ga_uid, ip_short, hosting, hit) VALUES (\''.$ab_config['cid'].'\', \''.$ab_config['time'].'\', \''.$ab_config['ip'].'\', \''.$ab_config['ptr'].'\', \''.$ab_config['useragent'].'\', \''.$ab_config['uid'].'\', \''.$ab_config['country'].'\', \''.$ab_config['city'].'\', \''.$ab_config['save_referer'].'\', \''.$ab_config['save_page'].'\', \''.$ab_config['accept_lang'].'\', \''.$ab_exec_time.'\', \'6\', \'0\', \'\', \'\', \'\', \'\', \'\', \'\', \''.$ab_config['refhost'].'\', \'\', \''.$ab_config['asnum'].'\', \''.$ab_config['asname'].'\', \''.$ab_config['result'].'\', \''.$ab_config['http_accept'].'\', \''.$ab_config['request_method'].'\', \''.$ab_config['ym_uid'].'\', \''.$ab_config['ga_uid'].'\', \''.$ab_config['ip_short'].'\', \''.$ab_config['hosting'].'\', \''.$ab_config['antibot_hits'].'\');';

$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}

file_put_contents(__DIR__.'/../data/counters/block_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);

if ($ab_config['iframe_stop'] == 1) {header('X-Frame-Options: SAMEORIGIN');}

header('X-Robots-Tag: noindex');
header($ab_config['protocol'].' '.$ab_config['error_headers'][$ab_config['header_error_code']]);
header('Status: '.$ab_config['error_headers'][$ab_config['header_error_code']]);
$error_tpl = file_get_contents(__DIR__.'/../data/error.txt');
$error_tpl = str_replace('<!--error-->', $ab_config['ip'].' '.date('d.m.Y H:i:s', $ab_config['time']), $error_tpl);

if (isset($ab_ip_test['expires'])) {
if (is_numeric($ab_ip_test['expires']) AND $ab_ip_test['expires'] - $ab_config['time'] < 86401) {
// перевод заглушки на язык посетителя:
if ($ab_config['tpl_lang'] == '') {$ab_config['tpl_lang'] = $ab_config['lang'];}
if (file_exists(__DIR__.'/../lang/tpl/'.$ab_config['tpl_lang'].'.php')) {
require_once(__DIR__.'/../lang/tpl/'.$ab_config['tpl_lang'].'.php');
}
$secwait = $ab_ip_test['expires'] - $ab_config['time']+2;
$error_tpl = str_replace('<!--ip_ban_msg-->', '<center><h2>'.abTranslate('Your IP has been blocked.').'</h2>
<h3>'.abTranslate('Seconds left until the unlock:').' <span id="countdownTimer">'.$secwait.'</span></h3></center>
<script>
var count = '.$secwait.';
var countdown = setInterval(function() {
document.getElementById(\'countdownTimer\').innerText = count;
count--;
  if (count < 0) {
    clearInterval(countdown);
    location.reload();
  }
}, 1000);
</script>
<style>.main_content {display: none;}</style>
', $error_tpl);
}
}
$error_tpl = str_replace('<!--ip_ban_msg-->', '', $error_tpl);
echo $error_tpl;
unset($error_tpl);
