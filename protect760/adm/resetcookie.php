<?php
// сбросить cookie (изменить subsalt)
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Update subsalt');

if (isset($_POST['resetcookie_submit'])) {
$goback = isset($_POST['resetcookie_submit']) ? preg_replace("/[^a-z0-9]/","",trim($_POST['resetcookie_submit'])) : 'index';
file_put_contents(__DIR__.'/../data/subsalt.php', '<?php $ab_config[\'subsalt\'] = \''.abRandword(8).'\';', LOCK_EX);
} else {
$goback = 'index';
}

clearstatcache(true); // Clears file status cache
if(function_exists('opcache_reset')) {
@opcache_reset();
}

echo '<script>document.location.href="?'.$abw.$abp.'='.$goback.'";</script>';
