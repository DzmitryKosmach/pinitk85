<?php
// удаление прокси ip
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Delete rule');

if (isset($_POST['removeproxy_submit']) AND isset($_POST['id'])) {
$_POST['id'] = isset($_POST['id']) ? trim(preg_replace("/[^0-9]/","", $_POST['id'])) : abDie('id');

$del = $antibot_db->exec("DELETE FROM ab_proxy WHERE rowid=".$_POST['id'].";");

$ab_se_proxy = $antibot_db->query("SELECT * FROM ab_proxy;"); 
$save = '<?php
';
while ($echo = $ab_se_proxy->fetchArray(SQLITE3_ASSOC)) {
$save .= '$ab_proxy[\''.$echo['k'].'\'] = \''.$echo['v'].'\';
';
}
file_put_contents(__DIR__.'/../data/proxy.php', $save, LOCK_EX);

}

echo '<script>document.location.href="?'.$abw.$abp.'=proxy";</script>';
abDie();
