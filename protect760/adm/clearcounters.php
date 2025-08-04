<?php
// очистка таблицы счетчиков
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Delete all records');

if (isset($_POST['clearcounters_submit'])) {
$del = $antibot_db->exec("DELETE FROM counters;");
}

// удаление старых потерянных счетчиков:
$link = glob(__DIR__."/../data/counters/*");
foreach ($link as $line) {
unlink($line);
}

echo '<script>document.location.href="?'.$abw.$abp.'=counters";</script>';
