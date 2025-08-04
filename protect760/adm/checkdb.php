<?php
// восстановление базы, если повезет...
if(!defined('ANTIBOT')) die('access denied');

$title =  abTranslate('Restore corrupted SQLite3 database.');

if (isset($_POST['update_submit']) AND isset($_POST['restore_db'])) {
// закрываем текущую базу:
$antibot_db->close();

$checkdb = 1;
require_once(__DIR__.'/../code/install.php');

$antibot_db->exec("ATTACH DATABASE '".__DIR__."/../data/sqlite.db' AS old"); // подключение текущей базы к новой
$antibot_db->exec("INSERT INTO main.ipv4rules SELECT * FROM old.ipv4rules;");
$antibot_db->exec("INSERT INTO main.ipv6rules SELECT * FROM old.ipv6rules;");
$antibot_db->exec("INSERT INTO main.rules SELECT * FROM old.rules;");
$antibot_db->exec("INSERT INTO main.hits SELECT * FROM old.hits;");
$antibot_db->exec("INSERT INTO main.counters SELECT * FROM old.counters;");
$antibot_db->exec("INSERT INTO main.ab_se SELECT * FROM old.ab_se;");
$antibot_db->exec("INSERT INTO main.ab_path SELECT * FROM old.ab_path;");
$antibot_db->exec("INSERT INTO main.ab_proxy SELECT * FROM old.ab_proxy;");
$antibot_db->exec("INSERT INTO main.auth_log SELECT * FROM old.auth_log;");
$antibot_db->exec("INSERT INTO main.ptrcache SELECT * FROM old.ptrcache;");

$antibot_db->close();

rename(__DIR__.'/../data/sqlite2.db', __DIR__.'/../data/sqlite.db');

}

$antibot_db = new SQLite3(__DIR__.'/../data/sqlite.db');
$antibot_db->busyTimeout(5000);
$antibot_db->exec("PRAGMA journal_mode = WAL;");

$list = $antibot_db->query("PRAGMA integrity_check;");

$content .= '
<ol style="color:red;">
<li>'.abTranslate('Before initiating recovery, it is recommended to disable the AntiBot script in the config').'</li>
<li>'.abTranslate('Before starting the recovery process, it is recommended to create a backup of the database.').'</li>
<li>'.abTranslate('Some data may be lost (from corrupted or outdated tables).').'</li>
</ol>
<h5>'.abTranslate('Database Errors (if any):').'</h5>
<ol>';
while ($echo = @$list->fetchArray()) {
$content .= '<li>'.$echo[0].'</li>';
}
$content .= '</ol>';

$content .= '<p><form class="form-inline" action="?'.$abw.$abp.'=checkdb" method="post">
<input name="restore_db" type="hidden" value="1">
<input style="cursor:pointer;" class="btn btn-sm btn-success" type="submit" name="update_submit" value="'.abTranslate('Restore corrupted SQLite3 database.').'">
</form></p>';
