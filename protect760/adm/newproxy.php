<?php
// создание нового правила
if(!defined('ANTIBOT')) die('access denied');

$bad_symbol = array('\'', '"', '\\');

$title = abTranslate('Create rule');

if (isset($_POST['newproxy_submit'])) {
$_POST['v'] = isset($_POST['v']) ? trim(preg_replace("/[^a-zA-Z0-9_\-]/","", $_POST['v'])) : '';
$_POST['k'] = isset($_POST['k']) ? trim(strip_tags($_POST['k'])) : '';

$testip = explode('/', $_POST['k']);
if (filter_var($testip[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) AND isset($testip[1]) AND is_numeric($testip[1]) AND $_POST['v'] != '') {

$_POST['k'] = $antibot_db->escapeString($_POST['k']);
$_POST['v'] = $antibot_db->escapeString($_POST['v']);
$add = @$antibot_db->exec("INSERT INTO ab_proxy (k, v) VALUES ('".$_POST['k']."', '".$_POST['v']."');");

$ab_se_proxy = $antibot_db->query("SELECT * FROM ab_proxy;"); 
$save = '<?php
';
while ($echo = $ab_se_proxy->fetchArray(SQLITE3_ASSOC)) {
$save .= '$ab_proxy[\''.$echo['k'].'\'] = \''.$echo['v'].'\';
';
}
file_put_contents(__DIR__.'/../data/proxy.php', $save, LOCK_EX);

}
}
echo '<script>document.location.href="?'.$abw.$abp.'=proxy";</script>';
abDie();


