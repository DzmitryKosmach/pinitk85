<?php
// создание бекапа базы
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Database Backup');

if (isset($_POST['create_backup_submit'])) {
$dbname = 'backup_'.date("Ymd_His", $ab_config['time']).'_'.$ab_version.'_'.abRandword(5).'.db';
$backup_db = new SQLite3(__DIR__.'/../data/'.$dbname); 
$backup_db->busyTimeout(2000);
$backup_db->exec("PRAGMA journal_mode = WAL;");
$antibot_db->backup($backup_db);
echo '<script>document.location.href="?'.$abw.$abp.'=backup";</script>';
abDie();
}

if (isset($_POST['restore_backup_submit'])) {
$dbname = isset($_POST['dbname']) ? preg_replace("/[^a-zA-Z0-9\.\_\:\-]/","",trim($_POST['dbname'])) : '';
$confirm = isset($_POST['confirm']) ? preg_replace("/[^0-9]/","",trim($_POST['confirm'])) : '';
if (file_exists(__DIR__.'/../data/'.$dbname) AND $confirm == 1 AND $dbname != 'sqlite.db') {
$restore_db = new SQLite3(__DIR__.'/../data/'.$dbname); 
$restore_db->busyTimeout(2000);
$restore_db->exec("PRAGMA journal_mode = WAL;");
$restore_db->backup($antibot_db);
$content .= '<div class="alert alert-success" role="alert">
  '.abTranslate('The database has been successfully restored from a backup.').'
</div>
<script>document.location.href="?'.$abw.$abp.'=backup";</script>';
} else {
$content .= '<div class="alert alert-danger" role="alert">
  '.abTranslate('Restore error. Please try again.').'
</div>';
}
}

if (isset($_POST['remove_backup_submit'])) {
$dbname = isset($_POST['dbname']) ? preg_replace("/[^a-zA-Z0-9\.\_\:\-]/","",trim($_POST['dbname'])) : '';
if ($dbname != 'sqlite.db') {
@unlink(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$dbname);
}
echo '<script>document.location.href="?'.$abw.$abp.'=backup";</script>';
abDie();
}

clearstatcache(true); // Clears file status cache
//if(function_exists('opcache_reset')) {
//@opcache_reset();
//}

if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
$content .= '<p>'.abTranslate('Main database:').' sqlite.db. '.abTranslate('Size').': '.round(filesize(__DIR__.'/../data/sqlite.db') / 1024 / 1024, 2).' MiB.</p>
<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th>'.abTranslate('Database file').'</th>
<th>'.abTranslate('Size').'</th>
<th>'.abTranslate('Restore').'</th>
<th>'.abTranslate('Delete').'</th>
</tr>
</thead>
<tbody>
';

$link = glob(__DIR__."/../data/backup_*.db");
foreach ($link as $line) {
$line = realpath($line);
$dbfile = trim(basename($line));
$content .= '<tr>
<td>'.$dbfile.'<br /><small class="text-muted">'.$line.'</small></td>
<td>'.round(filesize($line) / 1024 / 1024, 2).' MiB</td>
<td><form action="" method="post" class="form-inline float-right">
<input name="dbname" type="hidden" value="'.$dbfile.'">
  <div class="form-group form-check form-check-inline">
    <input type="checkbox" name="confirm" value="1" class="form-check-input" id="'.$dbfile.'">
    <label class="form-check-label" for="'.$dbfile.'">'.abTranslate('I understand that this will overwrite the main database.').'</label>
  </div>
<button type="submit" name="restore_backup_submit" class="btn btn-success btn-sm" title="'.abTranslate('Restore').'">
'.abTranslate('Restore').'
</button>
</form></td>
<td><form action="" method="post" onsubmit="return check()">
<input name="dbname" type="hidden" value="'.$dbfile.'">
<button type="submit" name="remove_backup_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete').'">
'.abTranslate('Delete').'
</button>
</form></td>
</tr>';
}

$content .= '</tbody>
</table>
</div>

<form action="" method="post">
 <div class="form-group">
 <p><button name="create_backup_submit" type="submit" class="btn btn-primary">'.abTranslate('Create backup').'</button></p>
 </div>
</form>';
} else {
$content .= '<div class="alert alert-danger" role="alert">
  '.abTranslate('This feature is only available for PHP version 7.4 and above. Your website\'s PHP version:').' '.PHP_VERSION.'
</div>';
}
