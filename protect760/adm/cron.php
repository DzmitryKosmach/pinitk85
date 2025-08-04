<?php
// ручной запуск cron:
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Cron');

$step = isset($_POST['step']) ? preg_replace("/[^0-9]/","",trim($_POST['step'])) : 0;

if (isset($_POST['cron_submit'])) {
// удаление старых записей из лога:
if (file_exists(__DIR__ . '/../data/crontimefile')) {unlink(__DIR__ . '/../data/crontimefile');}
if (file_exists(__DIR__ . '/../data/cronlog')) {unlink(__DIR__ . '/../data/cronlog');}
file_put_contents(__DIR__ . '/../data/crontimefile', $ab_config['time']);
if (is_shell_exec_available()) {
if ($ab_config['php_handler'] == '') {$ab_config['php_handler'] = PHP_BINDIR.'/php';}
shell_exec($ab_config['php_handler'].' -q '.dirname(dirname(__FILE__)).'/code/clear_old_hits.php > /dev/null 2>&1 &');
}
clearstatcache();
echo '<script>document.location.href="?'.$abw.$abp.'=cron&step=1";</script>';
abDie();
}

clearstatcache();
if (file_exists(__DIR__ . '/../data/cronlog') OR $step > 10) {
if ($antibot_db->lastErrorMsg() != 'database is locked') {
$vacuum = @$antibot_db->exec("VACUUM;");
}
echo '<script>document.location.href="?'.$abw.$abp.'=index";</script>';
abDie();
} else {
echo '<script>
setTimeout(function() {
  document.location.href = "?'.$abw.$abp.'=cron&step='.($step+1).'";
}, 2000);
</script>
';
abDie(abTranslate('In progress...'));
}
