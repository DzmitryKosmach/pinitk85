<?php
// удаление всех правил табилцы
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Delete all rules');

if (isset($_POST['removeallrules_submit']) AND isset($_POST['table'])) {
$_POST['table'] = isset($_POST['table']) ? trim(preg_replace("/[^a-z0-9_]/","", $_POST['table'])) : abDie('table');

$tables = array('ipv4rules' => 1, 'ipv6rules' => 2, 'ab_se' => 3, 'ab_path' => 4, 'rules' => 5);
if (!isset($tables[$_POST['table']])) abDie('table not allowed');
	
$del = $antibot_db->exec("DELETE FROM ".$_POST['table'].";");

if ($_POST['table'] == 'ab_se') {
// обновление кэша se.php
$save = '<?php
';
file_put_contents(__DIR__.'/../data/se.php', $save, LOCK_EX);
} elseif ($_POST['table'] == 'ab_path') {
// обновление кэша path.php
$save = '<?php
';
file_put_contents(__DIR__.'/../data/path.php', $save, LOCK_EX);
}
echo '<script>document.location.href="?'.$abw.$abp.'=rules#'.$_POST['table'].'_0";</script>';
abDie();
}
