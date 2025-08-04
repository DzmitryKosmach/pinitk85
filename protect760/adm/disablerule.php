<?php
// обновление приоритета правил
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Update rule');

if (isset($_POST['disablerule_submit']) AND isset($_POST['id'])) {
$_POST['id'] = isset($_POST['id']) ? trim(preg_replace("/[^0-9]/","", $_POST['id'])) : abDie('id');
$_POST['disable'] = isset($_POST['disable']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['disable'])) : abDie('disable');
$_POST['table'] = isset($_POST['table']) ? trim(preg_replace("/[^a-z0-9_]/","", $_POST['table'])) : abDie('table');

$del = $antibot_db->exec("UPDATE ".$_POST['table']." SET disable = '".$_POST['disable']."' WHERE rowid=".$_POST['id'].";");

if ($_POST['table'] == 'ab_se') {
// обновление кэша se.php
$ab_se_rules = $antibot_db->query("SELECT rowid, * FROM ab_se WHERE disable = '0' ORDER BY priority ASC;"); 
$save = '<?php
';
while ($echo = $ab_se_rules->fetchArray(SQLITE3_ASSOC)) {
$save .= '$ab_rule[\''.$echo['search'].'\'] = \''.$echo['rule'].'\';
';
if ($echo['data'] == '') {
$echo['data'] = '\'.\'';
} else {
$echo['data'] = '\''.str_replace(' ', '\', \'', $echo['data']).'\'';
}
$save .= '$ab_se[\''.$echo['search'].'\'] = array('.$echo['data'].');
';
}
file_put_contents(__DIR__.'/../data/se.php', $save, LOCK_EX);
// кэш обновили
} elseif ($_POST['table'] == 'ab_path') {
// обновление кэша path.php
$ab_se_path = $antibot_db->query("SELECT rowid, * FROM ab_path WHERE disable = '0' ORDER BY priority ASC;"); 
$save = '<?php
';
while ($echo = $ab_se_path->fetchArray(SQLITE3_ASSOC)) {
$save .= '$ab_path[\''.$echo['search'].'\'] = \''.$echo['rule'].'\';
';
}
file_put_contents(__DIR__.'/../data/path.php', $save, LOCK_EX);
// кэш обновили
}
} else {
$_POST['table'] = '';
$_POST['id'] = '';
}

echo '<script>document.location.href="?'.$abw.$abp.'=rules#'.$_POST['table'].'_'.$_POST['id'].'";</script>';
