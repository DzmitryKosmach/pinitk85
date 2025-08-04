<?php
// обновление базы и т.п.
if(!defined('ANTIBOT')) die('access denied');

clearstatcache(true); // Clears file status cache
if(function_exists('opcache_reset')) {
@opcache_reset();
}

$title = abTranslate('Update');

if (file_exists(__DIR__.'/../data/update.zip')) {
unlink(__DIR__.'/../data/update.zip');
}

@unlink(__DIR__.'/../data/beta');

//$add = @$antibot_db->exec("ALTER TABLE ipv4rules ADD disable INTEGER NOT NULL default '0';");

// ---------------------------------------------------------------------
//  обновление баз и структуры файлов:
// v. 9.011:
//@unlink(__DIR__.'/../ab.php');
@unlink(__DIR__.'/../post.php');
// v. 9.022:
$add = @$antibot_db->exec("ALTER TABLE counters ADD adbpercent INTEGER NOT NULL default '';");
// v. 9.024:
$add = @$antibot_db->exec("ALTER TABLE counters ADD sqlerror INTEGER NOT NULL default '0';");
// v. 9.037:
if (!file_exists(__DIR__.'/../data/cookie')) { mkdir(__DIR__.'/../data/cookie');}
// v. 9.049:
$add = @$antibot_db->exec("ALTER TABLE hits ADD city TEXT NOT NULL default '';");
// v. 9.061:
$dir = __DIR__.'/../code/default_img';
if (is_dir($dir)) {
foreach (glob($dir . '/*') as $file) {
@unlink($file);
}
@rmdir($dir);
}
@unlink(__DIR__.'/setdefaultimg.php');
// ---------------------------------------------------------------------

echo '<script>document.location.href="?'.$abw.$abp.'=update";</script>';
