<?php
// удаление файла лога:
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Delete errorlog.txt');

if (isset($_POST['removephperrorlog_submit'])) {
@unlink(__DIR__.'/../data/errorlog.txt');
}

echo '<script>document.location.href="?'.$abw.$abp.'=index";</script>';
abDie();
