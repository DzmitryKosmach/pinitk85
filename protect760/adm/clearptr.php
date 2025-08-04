<?php
// очистка таблицы кэша ptr
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Delete all records');

if (isset($_POST['clearptr_submit'])) {

$ptrcache_time = isset($_POST['time']) ? preg_replace("/[^a-z]/","",trim($_POST['time'])) : '';

if ($ptrcache_time == 'all') {
$del = $antibot_db->exec("DELETE FROM ptrcache;");
$vacuum = $antibot_db->exec("VACUUM;");
} else {
$ab_config['ptrcache_time'] = $ab_config['time'] - (86400*$ab_config['ptrcache_time']); // 
$del = $antibot_db->exec("DELETE FROM ptrcache WHERE date < ".$ab_config['ptrcache_time'].";");
}
}

echo '<script>document.location.href="?'.$abw.$abp.'=index";</script>';
