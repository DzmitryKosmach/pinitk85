<?php
// главная страница админки
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Home');

// удаление лога php ошибок, если он неделю не изменялся:
$errorlog_filename = __DIR__.'/../data/errorlog.txt';
if (file_exists($errorlog_filename)) {
    $fileLastModified = filemtime($errorlog_filename);
    $weekInSeconds = 7 * 24 * 60 * 60;  // 7 дней в секундах
    if ($ab_config['time'] - $fileLastModified >= $weekInSeconds) {
        unlink($errorlog_filename);
    }
}

if ($ab_config['disable'] == 1) {
$content .= '<div class="alert alert-danger" role="alert"><h2>'.abTranslate('AntiBot is disabled in the config. Check of website visitors and bots is not carried out.').'</h2></div>';
}

$size = filesize(__DIR__.'/../data/sqlite.db');

$content .= '
<span id="warning"></span>
<div id="new_version_msg" class="alert alert-danger" role="alert" style="display:none"><h1>'.abTranslate('A new version of the AntiBot script is available.').'<br />'.abTranslate('To update, please visit the page:').' <a href="?'.$abw.$abp.'=update">'.abTranslate('Update').'</a></h1></div>

<div class="alert alert-primary" role="alert">'.abTranslate('Check the correct installation and configuration of the AntiBot script:').' <a href="?'.$abw.$abp.'=checklist">'.abTranslate('Check List').'</a></div>


<div class="row">
<div class="col-md-5">
<div class="card">
  <div class="card-header"><div class="fflag fflag-'.$ab_config['country'].' ff-md" title="'.$ab_config['country'].'"></div> <strong>'.$ab_config['ip'].' '.$ab_config['city'].'</strong> - '.abTranslate('Is this your IP?').'</div>
  <ul class="list-group list-group-flush">
    <li class="list-group-item"><a href="?'.$abw.$abp.'=proxy">'.abTranslate('If this is not your IP, then it needs to be configured.').'</a></li>
    <li class="list-group-item"><a href="?'.$abw.$abp.'=authlog">'.abTranslate('Authorization Log in the Admin Panel.').'</a></li>
  </ul>
</div>';

// кол-во записей в ptr логе:
$ptrcache_count = $antibot_db->querySingle("SELECT count(ip) FROM ptrcache;");
$ptrcache_count = (string)$ptrcache_count;
// средняя скорость определения PTR у последних 100 записей:
$ptr_speed = $antibot_db->query("SELECT etime FROM ptrcache ORDER BY rowid DESC limit 150;");
$etimes = 0;
$i = 0;
while ($echo = $ptr_speed->fetchArray(SQLITE3_ASSOC)) {
$etimes = $etimes + $echo['etime'];
$i++;
}
$etimes = round($etimes / $i, 4);

$content .= '<br /><div class="card">
  <div class="card-header"><strong>'.abTranslate('PTR cache').'</strong></div>
  <ul class="list-group list-group-flush">
    <li class="list-group-item">'.abTranslate('Number of records in the PTR cache:').' '.$ptrcache_count.'</li>
    <li class="list-group-item">Среднее время ответа: '.$etimes.' '.abTranslate('sec.').'</li>
    <li class="list-group-item"><form id="clear_ptr_all" action="?'.$abw.$abp.'=clearptr" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="time" type="hidden" value="all">
<input name="clearptr_submit" type="hidden" value="1">
<span style="cursor:pointer;" class="badge badge-danger" onclick="document.getElementById(\'clear_ptr_all\').submit();">'.abTranslate('Delete all records').'</span>
</form>
<form id="clear_ptr_conf" action="?'.$abw.$abp.'=clearptr" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="time" type="hidden" value="conf">
<input name="clearptr_submit" type="hidden" value="1">
<span style="cursor:pointer;" class="badge badge-danger" onclick="document.getElementById(\'clear_ptr_conf\').submit();">'.abTranslate('Delete older than (days):').' '.$ab_config['ptrcache_time'].'</span>
</form></li>
  </ul>
</div>
</div>
<div class="col">
';
$LoadAverage = '';
if(function_exists('sys_getloadavg')) {
foreach(sys_getloadavg() as $la) {$LoadAverage .= round($la, 2).' ';}
} else {
$LoadAverage .= 'n/a';
}

$link = glob(__DIR__."/../data/backup_*.db");
$all_filesize_backup = 0;
foreach ($link as $line) {
$all_filesize_backup = $all_filesize_backup + filesize($line);
}
// кол-во файлов бекапа:
$count_backup = count($link);
// общий размер директории Антибота:
function getDirectorySize($dir) {
$size = 0;
// Проверка существования директории
if (!file_exists($dir)) {
return $size;
}
// Создание итератора для директории
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
// Перебор всех файлов и поддиректорий для получения размера
foreach ($iterator as $file) {
if ($file->isReadable() && $file->isFile()) {
$size += $file->getSize();
}
}
 return $size;
}

$dirSize = getDirectorySize(__DIR__.'/../');

$content .= '<div class="card">
  <div class="card-header"><strong>'.abTranslate('Server resources').'</strong></div>
  <ul class="list-group list-group-flush">
    <li class="list-group-item">'.abTranslate('Load Average:').' <code>'.$LoadAverage.'</code> <a href="'.abTranslate('https://en.wikipedia.org/wiki/Load_(computing)').'" target="_blank" rel="noopener">'.abTranslate('What is Load Average?').' <i class="bi bi-box-arrow-up-right"></i></a></li>
    <li class="list-group-item">'.abTranslate('Total size of the AntiBot directory:').' <code>'.round($dirSize / 1024 / 1024, 2).' MiB</code></li>
    <li class="list-group-item">'.abTranslate('Database file size:').' data/sqlite.db: <code>'.round($size / 1024 / 1024, 2).' MiB</code> <a href="?'.$abw.$abp.'=backup">'.abTranslate('Backups').'</a></li>
    <li class="list-group-item">'.abTranslate('Size of backup files:').' <code>'.round($all_filesize_backup / 1024 / 1024, 2).' MiB</code> - '.(($count_backup == 0) ? abTranslate('No backups found.') : abTranslate('Files:').' '.$count_backup).'</li>
    <li class="list-group-item"><a href="?'.$abw.$abp.'=phpinfo">'.abTranslate('Information about the current PHP configuration - phpinfo()').'</a></li>
    <li class="list-group-item"><a href="?'.$abw.$abp.'=serverip">'.abTranslate('Server IP addresses').'</a></li>
  </ul>

</div>
</div>
</div>
';

if (file_exists(__DIR__.'/../data/errorlog.txt')) {
$phperrorsize = filesize(__DIR__.'/../data/errorlog.txt');
$content .= '<br /><div class="card border-danger">
<div class="card-header text-white bg-danger border-danger"><strong>'.abTranslate('PHP Error Log').'</strong>  
'.abTranslate('These problems need to be solved.').' 
'.abTranslate('File size:').' '.round($phperrorsize / 1024 / 1024, 4).' MiB 
</div>';
if ($phperrorsize < 1500) {$indent = 0;} else {$indent = $phperrorsize - 1500;}
$handle = fopen(__DIR__.'/../data/errorlog.txt', "r");
fseek($handle, $indent);
$last_lines = fread($handle, 1500);
fclose($handle);
$last_lines = explode("\n", $last_lines, 2);
if (count($last_lines) > 3) {unset($last_lines[0]);}
$last_lines = implode("\n", $last_lines);
$content .= '<div class="card-body"><pre>'.htmlentities($last_lines).'</pre></div>
<div class="card-footer text-muted d-flex justify-content-between">
    <p>'.abTranslate('These are the last 1500 bytes of the log, there may be additional lines. The entire log is located in the file data/errorlog.txt').' <a href="https://'.$ab_config['main_url'].'/FAQ/php-errors.html" target="_blank" rel="noopener">'.abTranslate('FAQ').' <i class="bi bi-box-arrow-up-right"></i></a></p>
    <p><form id="removephperrorlog" action="?'.$abw.$abp.'=removephperrorlog" method="post" onsubmit="return check()">
<input name="removephperrorlog_submit" type="hidden" value="1">
<span style="cursor:pointer;" class="badge badge-danger" onclick="document.getElementById(\'removephperrorlog\').submit();">'.abTranslate('Delete log file').'</span>
</form> </p>
</div>
</div>
';
}

$content .= '<br />
<div class="row">
<div class="col-md-5"><div class="card">
  <div class="card-header"><strong>'.abTranslate('Last cron log').'</strong></div>
  <div class="card-body">
<pre>'.@file_get_contents(__DIR__.'/../data/cronlog').'</pre>
  </div>
<div class="card-footer">
<form id="manual_cron" action="?'.$abw.$abp.'=cron" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="cron_submit" type="hidden" value="1">
<span style="cursor:pointer;" class="badge badge-danger" onclick="document.getElementById(\'manual_cron\').submit();">'.abTranslate('Run CRON manually').'</span>
</form>
</div>
</div></div>
<div class="col"><div class="card">
  <div class="card-header"><strong>'.abTranslate('News').'</strong></div>
  <div class="card-body">
<!-- новости -->
<span id="other_ab_msg"></span>
  </div>
</div></div>
</div>
';

$content .= '<script>
var xmlhttp = new XMLHttpRequest();
xmlhttp.onreadystatechange = function() {
if (this.readyState == 2 && this.status == 200) {
document.getElementById("warning").innerHTML = "<div class=\"alert alert-danger\"><a href=\"data/sqlite.db\" target=\"_blank\">data/sqlite.db</a> - '.abTranslate('The SQLite3 database file has a server response code of 200. It may be available for download, which is unsafe since the database may contain confidential information. Protect the database file from web access.').' <a href=\"https://'.$ab_config['main_url'].'/FAQ/nginx-php-fpm.html\" target=\"_blank\" rel=\"noopener\">'.abTranslate('Protecting directories using NGINX.').' <i class=\"bi bi-box-arrow-up-right\"></i></a></div>";
}
};
xmlhttp.timeout = 1000;
xmlhttp.open("GET", "data/sqlite.db", true);
xmlhttp.send();
</script>
';
