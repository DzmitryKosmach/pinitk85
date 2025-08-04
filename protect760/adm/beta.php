<?php
// обновление
if(!defined('ANTIBOT')) die('access denied');

// удаление старых потерянных счетчиков:
$link = glob(__DIR__."/../data/counters/*");
foreach ($link as $line) {
if (filemtime($line) < $ab_config['time'] - 259200) {
unlink($line);
}
}

$unique_db = new SQLite3(__DIR__.'/../data/unique.db'); 
$unique_db->busyTimeout(2000);
$unique_db->exec("PRAGMA journal_mode = WAL;");
$vacuum = $unique_db->exec("VACUUM;");

clearstatcache(); // Clears file status cache
if(function_exists('opcache_reset')) {
@opcache_reset();
}

$content = '';

if (isset($_POST['update_submit']) AND isset($_POST['upd'])) {
// список изменений и хэши:
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$ab_config['files_url'].'/static/update/update9beta.json');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$actual = @json_decode(trim(curl_exec($ch)), true);
curl_close($ch);

// скачивание архива с обновлением:
$fp = fopen(__DIR__.'/../data/updatebeta.zip', 'w');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$ab_config['files_url'].'/static/update/update9beta.zip');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$outch = curl_exec($ch);
curl_close($ch);
fclose($fp);


if ($actual['md5'] == md5_file(__DIR__.'/../data/updatebeta.zip')) {
$zip = new ZipArchive;
if ($zip->open(__DIR__.'/../data/updatebeta.zip') === TRUE) {
    $zip->extractTo(__DIR__.'/../');
    $zip->close();
$content .= '<div class="alert alert-success" role="alert">
'.abTranslate('Update has been successful.').'
</div>';
file_put_contents(__DIR__.'/../data/beta', '1', LOCK_EX);
} else {
$content .= '<div class="alert alert-danger" role="alert">
'.abTranslate('Update failed.').'
</div>';
}

clearstatcache(true); // Clears file status cache
if(function_exists('opcache_reset')) {
@opcache_reset();
}

echo '<script>document.location.href="?'.$abw.$abp.'=beta2";</script>';
abDie();
} else {
$content .= '<div class="alert alert-danger" role="alert">
'.abTranslate('Update failed.').' '.abTranslate('The MD5 hash of the archive does not match the reference.').'
</div>';
}

} else {
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

// ---------------------------------------------------------------------
}

$title = abTranslate('Update');

// не установлен ZIP
if (!class_exists('ZipArchive')) {
$content .= '<div class="alert alert-danger" role="alert">
'.abTranslate('The ZipArchive class doesn\'t exist. Please install the ZIP extension for PHP.').'
</div>';
}

$antibotdir = realpath(__DIR__.'/../');

// получение массива актуальных файлов:
function getDirContents($dir, &$results = array()) {
global $antibotdir;
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = $dir . DIRECTORY_SEPARATOR . $value;
        if (!is_dir($path)) {
//            $results[$path] = md5_file($path);
            $filename = explode($antibotdir.DIRECTORY_SEPARATOR, $path);
			$filename[1] = str_replace('\\', '/', $filename[1]);
//            echo $filename[1].'<br>';
            $results[$filename[1]] = md5_file($path).'_'.md5($filename[1]);
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            //$results[$path] = md5_file($path);
        }
    }
    return $results;
}

$local = getDirContents(realpath(__DIR__.'/../')); // локальный массив

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$ab_config['files_url'].'/static/update/update9beta.json');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$actual = @json_decode(trim(curl_exec($ch)), true);
curl_close($ch);

if (isset($actual['md5'])) {
$new_ab_version = isset($actual['v']) ? trim(strip_tags($actual['v'])) : 'n/a';
unset($actual['md5']);
unset($actual['v']);

// Сравнивает array1 с одним или несколькими другими массивами и возвращает значения из array1, которые отсутствуют во всех других массивах.
$differences = array_diff($actual, $local); // Отличающиеся файлы, которые есть в архиве
$content .= '<div class="row">
<div class="col">
<div class="card">
  <div class="card-header bg-warning">'.abTranslate('Your script version:').' '.$ab_version.' '.(($beta == 1) ? '<span style="color:red;">Beta</span>' : '').' ➜ '.abTranslate('New version:').' '.$new_ab_version.' <span style="color:red;">Beta</span></div>
<iframe style="border:none; width: 100%; height: 200px;" src="https://'.$ab_config['main_url'].'/'.abTranslate('static/update/changelog9beta.txt').'"></iframe>
</div>
</div>
</div>
<br />
    <div class="row">
        <div class="col">
<div class="card">
<div class="card-header"><strong>'.abTranslate('These files will be replaced or added:').'</strong></div>
<ul class="list-group list-group-flush">';
$upderror = 0;
$updcount = 0;
foreach ($differences as $k => $v) {
if (file_exists(__DIR__.'/../'.$k) AND is_writable(__DIR__.'/../'.$k)) {
$content .= '<li class="list-group-item text-success">'.$k.' - '.abTranslate('will be replaced').'</li>';
} elseif (!file_exists(__DIR__.'/../'.$k)) {
$content .= '<li class="list-group-item text-success">'.$k.' - '.abTranslate('will be added').'</li>';
} else {
$content .= '<li class="list-group-item text-danger">'.$k.' - '.abTranslate('set write permissions for this file').'</li>';
$upderror = 1;
}
$updcount++;
}
$content .= '</ul>';
if ($upderror == 1) {
$content .= '<div class="card-body"><div class="alert alert-danger" role="alert">
'.abTranslate('Update is not possible. Please fix the errors mentioned above.').'
</div></div>';
} elseif ($updcount == 0) {
$content .= '<div class="card-body"><div class="alert alert-success" role="alert">
'.abTranslate('No update required.').'
</div></div>';
} elseif ($host == 'antibot.xx') {
$content .= '<div class="card-body"><div class="alert alert-danger" role="alert">
Тут нельзя обновлять.
</div></div>';
} else {
$content .= '<div class="card-body"><form class="form-inline" action="?'.$abw.$abp.'=beta" method="post">
<input name="upd" type="hidden" value="1">
<input style="cursor:pointer;" class="btn btn-block btn-success" type="submit" name="update_submit" value="'.abTranslate('Make update').'">
</form></div>
';
}

$differences = array_diff($local, $actual); // различия
foreach ($differences as $k => $v) {
if (isset($actual[$k])) {unset($differences[$k]);}
}
$content .= '</div></div>
        <div class="col">
<div class="card">
<div class="card-header"><strong>'.abTranslate('Local files that will not be modified:').'</strong></div>
<ul class="list-group list-group-flush">';
foreach ($differences as $k => $v) {
$content .= '<li class="list-group-item">'.$k.'</li>';
}
$content .= '</ul>
</div>
    </div></div>
';

} else {
$content .= '<div class="alert alert-danger" role="alert">
'.abTranslate('Error checking for updates.').'
</div>';
}
