<?php
// сохранение конфига
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Edit Config');

$bad_symbol = array('\'', '"', '\\');

if (isset($_POST['save_conf'])) {

include(__DIR__.'/../data/conf.php');

if (!is_dir(__DIR__.'/../data/counters')) {
mkdir(__DIR__.'/../data/counters');
}

$_POST['disable'] = isset($_POST['disable']) ? trim($_POST['disable']) : 0;
if ($_POST['disable'] != 1) {$_POST['disable'] = 0;}

$_POST['wh'] = isset($_POST['wh']) ? $_POST['wh'] : array();
$ab_config['allow_wh'] = array('00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'); // разрешенные варианты
foreach ($_POST['wh'] as $k => $v) {
if (!in_array($v, $ab_config['allow_wh'])) {
unset($_POST['wh'][$k]);
}
}
if (count($_POST['wh']) > 0) {
$_POST['wh'] = '\''.implode('\', \'', $_POST['wh']).'\'';
} else {
$_POST['wh'] = '';
}

$_POST['unresponsive'] = isset($_POST['unresponsive']) ? trim($_POST['unresponsive']) : 0;
if ($_POST['unresponsive'] != 1) {$_POST['unresponsive'] = 0;}

$_POST['check'] = isset($_POST['check']) ? trim($_POST['check']) : 0;
if ($_POST['check'] != 1) {$_POST['check'] = 0;}

$_POST['cloud_rus'] = isset($_POST['cloud_rus']) ? trim($_POST['cloud_rus']) : 0;
if ($_POST['cloud_rus'] != 1) {$_POST['cloud_rus'] = 0;}

$_POST['phperror'] = isset($_POST['phperror']) ? preg_replace("/[^0-9]/","", $_POST['phperror']) : 1;
$_POST['phperror'] = $_POST['phperror'] + 0;

if ($_POST['phperror'] == 3) {
$phperrorsave = '// System error log settings will not be changed.';
} elseif ($_POST['phperror'] == 2) {
$phperrorsave = 'error_reporting(E_ALL);
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);
ini_set(\'html_errors\', 1);
ini_set(\'error_log\', __DIR__.\'/errorlog.txt\');';
} elseif ($_POST['phperror'] == 1) {
$phperrorsave = 'error_reporting(E_ALL);
ini_set(\'display_errors\', 0);
ini_set(\'error_log\', __DIR__.\'/errorlog.txt\');';
} else {
$phperrorsave = 'error_reporting(0);
ini_set(\'display_errors\', 0); // off or on';
}

$_POST['webdir'] = isset($_POST['webdir']) ? trim(strip_tags($_POST['webdir'])) : $ab_webdir.'/';
$_POST['webdir'] = str_ireplace($bad_symbol, '', $_POST['webdir']);

$_POST['ab_url'] = isset($_POST['ab_url']) ? trim(strip_tags($_POST['ab_url'])) : '';
$_POST['ab_url'] = str_ireplace($bad_symbol, '', $_POST['ab_url']);

$_POST['timezone'] = isset($_POST['timezone']) ? trim(preg_replace("/[^a-zA-Z\-\_\/]/","", $_POST['timezone'])) : '';
if ($_POST['timezone'] != '') {
$timezonesave = 'date_default_timezone_set(\''.$_POST['timezone'].'\');';
} else {
$timezonesave = '';
}

$_POST['email'] = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';
$_POST['email'] = mb_strtolower($_POST['email'], 'utf-8'); // в нижний регистр
$_POST['email'] = str_ireplace($bad_symbol, '', $_POST['email']);

$_POST['pass'] = isset($_POST['pass']) ? trim(strip_tags($_POST['pass'])) : '';
$_POST['pass'] = str_ireplace($bad_symbol, '', $_POST['pass']);

$_POST['secondpass'] = isset($_POST['secondpass']) ? trim($_POST['secondpass']) : '';
if ($_POST['secondpass'] == '**********') {
$_POST['secondpass'] = $ab_config['secondpass']; // не менять
} elseif ($_POST['secondpass'] != '') {
$_POST['secondpass'] = md5('antibot'.$_POST['secondpass']);
} else {
$_POST['secondpass'] = '';
}

$_POST['samesite'] = isset($_POST['samesite']) ? trim(strip_tags($_POST['samesite'])) : 'Lax';
$_POST['samesites'] = array('Lax', 'Strict', 'None');
if (!in_array($_POST['samesite'], $_POST['samesites'])) {$_POST['samesite'] = 'Lax';}

if (isset($_POST['newsalt']) OR $_POST['samesite'] != $ab_config['samesite']) {
file_put_contents(__DIR__.'/../data/subsalt.php', '<?php $ab_config[\'subsalt\'] = \''.abRandword(9).'\';', LOCK_EX);
}

$_POST['old_salt'] = explode('_', $ab_config['salt']);
$_POST['newsalt'] = isset($_POST['newsalt']) ? abRandword(4) : trim($_POST['old_salt'][0]);

$_POST['timesalt'] = isset($_POST['timesalt']) ? trim(strip_tags($_POST['timesalt'])) : '';
$_POST['timesalts'] = array('YW', 'Yz', 'Y');
if (!in_array($_POST['timesalt'], $_POST['timesalts'])) {$_POST['timesalt'] = 'Y';}

$_POST['is_bitrix'] = isset($_POST['is_bitrix']) ? trim($_POST['is_bitrix']) : 0;
if ($_POST['is_bitrix'] != 1) {$_POST['is_bitrix'] = 0;}

$_POST['hits_per_user'] = isset($_POST['hits_per_user']) ? preg_replace("/[^0-9]/","", $_POST['hits_per_user']) : 1000;
$_POST['hits_per_user'] = $_POST['hits_per_user'] + 0;

$_POST['input_button'] = isset($_POST['input_button']) ? trim($_POST['input_button']) : 0;
if ($_POST['input_button'] != 1) {$_POST['input_button'] = 0;}

$_POST['buttons'] = isset($_POST['buttons']) ? (int)trim($_POST['buttons']) : 0;
$_POST['allbuttons'] = array(0, 1, 2, 3, 4, 5);
if (!in_array($_POST['buttons'], $_POST['allbuttons'])) {$_POST['buttons'] = 2;}
// если нету функции обработки картинок:
if ($ab_config['buttons'] == 1 OR $ab_config['buttons'] == 2) {
if (!function_exists('imagecreatetruecolor')) {
$ab_config['buttons'] = 0;
}
}

$_POST['time_ban'] = isset($_POST['time_ban']) ? trim(preg_replace("/[^0-9\.]/","", $_POST['time_ban'])) : 0;
if ($_POST['time_ban'] == '') {$_POST['time_ban'] = 0;}
$_POST['time_ban'] = explode('.', $_POST['time_ban']);
$_POST['time_ban'][0] = (int)$_POST['time_ban'][0] + 0;
if (isset($_POST['time_ban'][1])) {
$_POST['time_ban'][1] = (int)$_POST['time_ban'][1] + 0;
$_POST['time_ban'] = $_POST['time_ban'][0].'.'.$_POST['time_ban'][1];
} else {
$_POST['time_ban'] = $_POST['time_ban'][0];
}

$_POST['time_ban_2'] = isset($_POST['time_ban_2']) ? trim(preg_replace("/[^0-9\.]/","", $_POST['time_ban_2'])) : 0;
if ($_POST['time_ban_2'] == '') {$_POST['time_ban_2'] = 0;}
$_POST['time_ban_2'] = explode('.', $_POST['time_ban_2']);
$_POST['time_ban_2'][0] = (int)$_POST['time_ban_2'][0] + 0;
if (isset($_POST['time_ban_2'][1])) {
$_POST['time_ban_2'][1] = (int)$_POST['time_ban_2'][1] + 0;
$_POST['time_ban_2'] = $_POST['time_ban_2'][0].'.'.$_POST['time_ban_2'][1];
} else {
$_POST['time_ban_2'] = $_POST['time_ban_2'][0];
}

$_POST['tpl_lang'] = isset($_POST['tpl_lang']) ? trim(preg_replace("/[^a-z]/","", $_POST['tpl_lang'])) : '';
if ($_POST['tpl_lang'] != '' AND !file_exists(__DIR__.'/../lang/tpl/'.$_POST['tpl_lang'].'.php')) {
$_POST['tpl_lang'] = '';
}

$_POST['cookie'] = isset($_POST['cookie']) ? trim(preg_replace("/[^a-zA-Z]/","", $_POST['cookie'])) : '';
if ($_POST['cookie'] == '') {$_POST['cookie'] = abRandword(9);}

$_POST['auth'] = isset($_POST['auth']) ? trim(strip_tags($_POST['auth'])) : 'cookie';
$_POST['auths'] = array('sqlite', 'cookie');
if (!in_array($_POST['auth'], $_POST['auths'])) {$_POST['auth'] = 'cookie';}

$_POST['js_error_msg'] = isset($_POST['js_error_msg']) ? trim(strip_tags($_POST['js_error_msg'])) : '';
$_POST['js_error_msg'] = trim(str_ireplace($bad_symbol, '', $_POST['js_error_msg']));
if ($_POST['js_error_msg'] == '') {$_POST['js_error_msg'] = 'Your request has been denied.';}

$_POST['recaptcha_key2'] = isset($_POST['recaptcha_key2']) ? trim(strip_tags($_POST['recaptcha_key2'])) : '';
$_POST['recaptcha_key2'] = str_ireplace($bad_symbol, '', $_POST['recaptcha_key2']);

$_POST['recaptcha_secret2'] = isset($_POST['recaptcha_secret2']) ? trim(strip_tags($_POST['recaptcha_secret2'])) : '';
$_POST['recaptcha_secret2'] = str_ireplace($bad_symbol, '', $_POST['recaptcha_secret2']);

$_POST['recaptcha_key'] = isset($_POST['recaptcha_key']) ? trim(strip_tags($_POST['recaptcha_key'])) : '';
$_POST['recaptcha_key'] = str_ireplace($bad_symbol, '', $_POST['recaptcha_key']);

$_POST['recaptcha_secret'] = isset($_POST['recaptcha_secret']) ? trim(strip_tags($_POST['recaptcha_secret'])) : '';
$_POST['recaptcha_secret'] = str_ireplace($bad_symbol, '', $_POST['recaptcha_secret']);

$_POST['re_check'] = isset($_POST['re_check']) ? trim($_POST['re_check']) : 0;
if ($_POST['re_check'] != 1 OR $_POST['recaptcha_key'] == '' OR $_POST['recaptcha_secret'] == '') {$_POST['re_check'] = 0;}

$_POST['utm_referrer'] = isset($_POST['utm_referrer']) ? trim($_POST['utm_referrer']) : 0;
if ($_POST['utm_referrer'] != 1) {$_POST['utm_referrer'] = 0;}

$_POST['utm_noindex'] = isset($_POST['utm_noindex']) ? trim($_POST['utm_noindex']) : 0;
if ($_POST['utm_noindex'] != 1) {$_POST['utm_noindex'] = 0;}

$_POST['check_get_ref'] = isset($_POST['check_get_ref']) ? trim($_POST['check_get_ref']) : 0;
if ($_POST['check_get_ref'] != 1) {$_POST['check_get_ref'] = 0;}

$_POST['bad_get_ref'] = isset($_POST['bad_get_ref']) ? trim(strip_tags($_POST['bad_get_ref'])) : '';
$_POST['bad_get_ref'] = str_ireplace($bad_symbol, '', $_POST['bad_get_ref']);

$_POST['secret_allow_get'] = isset($_POST['secret_allow_get']) ? trim(strip_tags($_POST['secret_allow_get'])) : '';
$_POST['secret_allow_get'] = str_ireplace($bad_symbol, '', $_POST['secret_allow_get']);

$_POST['ptrcache_time'] = isset($_POST['ptrcache_time']) ? preg_replace("/[^0-9]/","", $_POST['ptrcache_time']) : 7;
$_POST['ptrcache_time'] = $_POST['ptrcache_time'] + 0;
if ($_POST['ptrcache_time'] < 7) {$_POST['ptrcache_time'] = 7;}
if ($_POST['ptrcache_time'] > 30) {$_POST['ptrcache_time'] = 30;}

$_POST['antibot_log_tests'] = isset($_POST['antibot_log_tests']) ? trim($_POST['antibot_log_tests']) : 0;
if ($_POST['antibot_log_tests'] != 1) {$_POST['antibot_log_tests'] = 0;}

$_POST['antibot_log_local'] = isset($_POST['antibot_log_local']) ? trim($_POST['antibot_log_local']) : 0;
if ($_POST['antibot_log_local'] != 1) {$_POST['antibot_log_local'] = 0;}

$_POST['antibot_log_allow'] = isset($_POST['antibot_log_allow']) ? trim($_POST['antibot_log_allow']) : 0;
if ($_POST['antibot_log_allow'] != 1) {$_POST['antibot_log_allow'] = 0;}

$_POST['antibot_log_fake'] = isset($_POST['antibot_log_fake']) ? trim($_POST['antibot_log_fake']) : 0;
if ($_POST['antibot_log_fake'] != 1) {$_POST['antibot_log_fake'] = 0;}

$_POST['antibot_log_goodip'] = isset($_POST['antibot_log_goodip']) ? trim($_POST['antibot_log_goodip']) : 0;
if ($_POST['antibot_log_goodip'] != 1) {$_POST['antibot_log_goodip'] = 0;}

$_POST['antibot_log_block'] = isset($_POST['antibot_log_block']) ? trim($_POST['antibot_log_block']) : 0;
if ($_POST['antibot_log_block'] != 1) {$_POST['antibot_log_block'] = 0;}

$_POST['header_test_code'] = isset($_POST['header_test_code']) ? preg_replace("/[^0-9]/","", $_POST['header_test_code']) : 200;
if (!isset($ab_config['error_headers'][$_POST['header_test_code']])) {$_POST['header_test_code'] = 200;}

$_POST['header_error_code'] = isset($_POST['header_error_code']) ? preg_replace("/[^0-9]/","", $_POST['header_error_code']) : 403;
if (!isset($ab_config['error_headers'][$_POST['header_error_code']])) {$_POST['header_error_code'] = 200;}

$_POST['period_cleaning'] = isset($_POST['period_cleaning']) ? trim(preg_replace("/[^a-z]/","", $_POST['period_cleaning'])) : 'lastmonth';
$_POST['periods_cleaning'] = array('lastday', 'lastweek', 'lastmonth', 'quarter', 'lastyear');
if (!in_array($_POST['period_cleaning'], $_POST['periods_cleaning'])) {$_POST['period_cleaning'] = 'lastmonth';}

$_POST['php_handler'] = isset($_POST['php_handler']) ? trim(preg_replace("/[^a-zA-Z0-9\.\-\_\/\\\:]/","", $_POST['php_handler'])) : '';
$_POST['php_handler'] = str_replace("\\", "/", $_POST['php_handler']);

$_POST['noarchive'] = isset($_POST['noarchive']) ? trim($_POST['noarchive']) : 0;
if ($_POST['noarchive'] != 1) {$_POST['noarchive'] = 0;}

$_POST['del_ref_query_string'] = isset($_POST['del_ref_query_string']) ? trim($_POST['del_ref_query_string']) : 0;
if ($_POST['del_ref_query_string'] != 1) {$_POST['del_ref_query_string'] = 0;}

$_POST['del_page_query_string'] = isset($_POST['del_page_query_string']) ? trim($_POST['del_page_query_string']) : 0;
if ($_POST['del_page_query_string'] != 1) {$_POST['del_page_query_string'] = 0;}

$_POST['block_fake_ref'] = isset($_POST['block_fake_ref']) ? trim($_POST['block_fake_ref']) : 0;
if ($_POST['block_fake_ref'] != 1) {$_POST['block_fake_ref'] = 0;}

$_POST['local_null_ref_stop'] = isset($_POST['local_null_ref_stop']) ? trim($_POST['local_null_ref_stop']) : 0;
if ($_POST['local_null_ref_stop'] != 1) {$_POST['local_null_ref_stop'] = 0;}

$_POST['iframe_stop'] = isset($_POST['iframe_stop']) ? trim($_POST['iframe_stop']) : 0;
if ($_POST['iframe_stop'] != 1) {$_POST['iframe_stop'] = 0;}

$_POST['hosting_block'] = isset($_POST['hosting_block']) ? trim($_POST['hosting_block']) : 0;
if ($_POST['hosting_block'] != 1) {$_POST['hosting_block'] = 0;}

$_POST['last_rule'] = isset($_POST['last_rule']) ? trim(strip_tags($_POST['last_rule'])) : '';
$_POST['last_rules'] = array('allow', 'block', 'dark', 'gray', '');
if (!in_array($_POST['last_rule'], $_POST['last_rules'])) {$_POST['last_rule'] = '';}

$_POST['privacy_policy'] = isset($_POST['privacy_policy']) ? trim(strip_tags($_POST['privacy_policy'])) : 'on';
$_POST['privacy_policy_array'] = array('on', 'off');
if (!in_array($_POST['privacy_policy'], $_POST['privacy_policy_array'])) {$_POST['privacy_policy'] = 'on';}

$_POST['conf'] = '<?php
// '.date("F d, Y - H:i:s", $ab_config['time']).'
'.$phperrorsave.'
'.$timezonesave.'
$ab_config[\'check\'] = '.$_POST['check'].'; // 1 - cloud, 0 - local
$ab_config[\'wh\'] = array('.$_POST['wh'].'); // array
$ab_config[\'unresponsive\'] = '.$_POST['unresponsive'].'; // 1 - stop, 0 - skip
$ab_config[\'phperror\'] = '.$_POST['phperror'].';
$ab_config[\'timezone\'] = \''.$_POST['timezone'].'\';
$ab_config[\'webdir\'] = \''.$_POST['webdir'].'\'; // change if renamed script directory
$ab_config[\'ab_url\'] = \''.$_POST['ab_url'].'\';
$ab_config[\'cookie\'] = \''.$_POST['cookie'].'\';
$ab_config[\'auth\'] = \''.$_POST['auth'].'\'; // sqlite or cookie
$ab_config[\'js_error_msg\'] = \''.$_POST['js_error_msg'].'\';
$ab_config[\'email\'] = \''.$_POST['email'].'\'; // change to your email
$ab_config[\'pass\'] = \''.$_POST['pass'].'\'; // change to your password
$ab_config[\'secondpass\'] = \''.$_POST['secondpass'].'\'; // delete this line if you forgot second password
$ab_config[\'salt\'] = \''.$_POST['newsalt'].'_\'.date(\''.$_POST['timesalt'].'\', time());
$ab_config[\'timesalt\'] = \''.$_POST['timesalt'].'\';
$ab_config[\'samesite\'] = \''.$_POST['samesite'].'\'; // Lax, Strict, None
$ab_config[\'is_bitrix\'] = '.$_POST['is_bitrix'].';
$ab_config[\'hits_per_user\'] = '.$_POST['hits_per_user'].';
$ab_config[\'input_button\'] = '.$_POST['input_button'].'; // 1 - off, 0 - on
$ab_config[\'tpl_lang\'] = \''.$_POST['tpl_lang'].'\';
$ab_config[\'buttons\'] = '.$_POST['buttons'].'; // buttons type
$ab_config[\'time_ban\'] = \''.$_POST['time_ban'].'\'; // string
$ab_config[\'time_ban_2\'] = \''.$_POST['time_ban_2'].'\'; // string
$ab_config[\'re_check\'] = '.$_POST['re_check'].';
$ab_config[\'recaptcha_key2\'] = \''.$_POST['recaptcha_key2'].'\';
$ab_config[\'recaptcha_secret2\'] = \''.$_POST['recaptcha_secret2'].'\';
$ab_config[\'recaptcha_key\'] = \''.$_POST['recaptcha_key'].'\';
$ab_config[\'recaptcha_secret\'] = \''.$_POST['recaptcha_secret'].'\';
$ab_config[\'utm_referrer\'] = '.$_POST['utm_referrer'].'; 
$ab_config[\'utm_noindex\'] = '.$_POST['utm_noindex'].';
$ab_config[\'check_get_ref\'] = '.$_POST['check_get_ref'].';
$ab_config[\'bad_get_ref\'] = \''.$_POST['bad_get_ref'].'\';
$ab_config[\'secret_allow_get\'] = \''.$_POST['secret_allow_get'].'\';
$ab_config[\'ptrcache_time\'] = '.$_POST['ptrcache_time'].';
$ab_config[\'antibot_log_tests\'] = '.$_POST['antibot_log_tests'].';
$ab_config[\'antibot_log_local\'] = '.$_POST['antibot_log_local'].';
$ab_config[\'antibot_log_allow\'] = '.$_POST['antibot_log_allow'].';
$ab_config[\'antibot_log_fake\'] = '.$_POST['antibot_log_fake'].';
$ab_config[\'antibot_log_goodip\'] = '.$_POST['antibot_log_goodip'].';
$ab_config[\'antibot_log_block\'] = '.$_POST['antibot_log_block'].';
$ab_config[\'header_test_code\'] = '.$_POST['header_test_code'].';
$ab_config[\'header_error_code\'] = '.$_POST['header_error_code'].';
$ab_config[\'period_cleaning\'] = \''.$_POST['period_cleaning'].'\';
$ab_config[\'php_handler\'] = \''.$_POST['php_handler'].'\';
$ab_config[\'noarchive\'] = '.$_POST['noarchive'].';
$ab_config[\'del_ref_query_string\'] = '.$_POST['del_ref_query_string'].';
$ab_config[\'del_page_query_string\'] = '.$_POST['del_page_query_string'].';
$ab_config[\'hosting_block\'] = '.$_POST['hosting_block'].';
$ab_config[\'block_fake_ref\'] = '.$_POST['block_fake_ref'].'; // 1 - block, 0 - do not check
$ab_config[\'local_null_ref_stop\'] = '.$_POST['local_null_ref_stop'].'; // 0 - no check
$ab_config[\'iframe_stop\'] = '.$_POST['iframe_stop'].'; // 1 - block, 0 - no check
$ab_config[\'last_rule\'] = \''.$_POST['last_rule'].'\';
$ab_config[\'cloud_rus\'] = '.$_POST['cloud_rus'].'; // 1 - Russian server
$ab_config[\'privacy_policy\'] = \''.$_POST['privacy_policy'].'\';
';

file_put_contents(__DIR__.'/../data/conf.php', $_POST['conf'], LOCK_EX);
file_put_contents(__DIR__.'/../data/disable.php', '<?php $ab_config[\'disable\'] = '.$_POST['disable'].';', LOCK_EX);

clearstatcache(true); // Clears file status cache
if(function_exists('opcache_reset')) {
@opcache_reset();
}

}
echo '<script>document.location.href="?'.$abw.$abp.'=conf";</script>';
