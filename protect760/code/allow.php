<?php
if (!isset($ab_version)) die('stop allow');
// для пропуска людей, с установкой cookie.

// куки чтоб далее пускать как LOCAL:
absetcookie($ab_config['uid'], md5($ab_config['salt'].$ab_config['pass'].$ab_config['host'].$ab_config['useragent'].$ab_config['ip'].$ab_config['time']).'-'.$ab_config['time'], $ab_config['time']+864000, false);

// куки в базу если включено:
if ($ab_config['auth'] == 'sqlite') {
$add = @$antibot_cookie_db->exec("INSERT INTO list (md5, date) VALUES ('".md5($ab_config['salt'].$ab_config['ip'].$ab_config['useragent'])."', '".$ab_config['time']."');");
}

//$ab_config['antibot_log_local'] = 0; // для исключения дублей в логе
$ab_config['whitebot'] = 0;
if ($ab_config['antibot_log_allow'] == 1) {
// записать в лог посещаемости, если включено логирование, с passed 4
$echo['search'] = $antibot_db->escapeString($echo['search']);
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
$sql = 'INSERT INTO hits (cid, date, ip, ptr, useragent, uid, country, city, referer, page, lang, generation, passed, recaptcha, js_w, js_h, js_cw, js_ch, js_co, js_pi, refhost, adblock, asnum, asname, result, http_accept, method, ym_uid, ga_uid, ip_short, hosting, hit) VALUES (\''.$ab_config['cid'].'\', \''.$ab_config['time'].'\', \''.$ab_config['ip'].'\', \''.$ab_config['ptr'].'\', \''.$ab_config['useragent'].'\', \''.$ab_config['uid'].'\', \''.$ab_config['country'].'\', \''.$ab_config['city'].'\', \''.$ab_config['save_referer'].'\', \''.$ab_config['save_page'].'\', \''.$ab_config['accept_lang'].'\', \''.$ab_exec_time.'\', \'4\', \'0\', \'\', \'\', \'\', \'\', \'\', \'\', \''.$ab_config['refhost'].'\', \'\', \''.$ab_config['asnum'].'\', \''.$ab_config['asname'].'\', \'ALLOW By rule: '.$echo['search'].'\', \''.$ab_config['http_accept'].'\', \''.$ab_config['request_method'].'\', \''.$ab_config['ym_uid'].'\', \''.$ab_config['ga_uid'].'\', \''.$ab_config['ip_short'].'\', \''.$ab_config['hosting'].'\', \''.$ab_config['antibot_hits'].'\');';
$add = @$antibot_db->exec($sql);
if ($antibot_db->lastErrorMsg() == 'database is locked') {
file_put_contents(__DIR__.'/../data/counters/sqlerror_'.date("Ymd", $ab_config['time']).'_'.$ab_config['time'], '1', FILE_APPEND | LOCK_EX);
}
}
file_put_contents(__DIR__.'/../data/counters/allow_'.date("Ymd_Hi", $ab_config['time']), '1', FILE_APPEND | LOCK_EX);
