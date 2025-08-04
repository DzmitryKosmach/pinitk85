<?php
// обновление шаблона error.txt
if(!defined('ANTIBOT')) die('access denied');

clearstatcache(); // Clears file status cache

if (isset($_POST['updateerror_submit']) AND isset($_POST['upd'])) {
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$ab_config['files_url'].'/static/update/error9.json');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$actual = @json_decode(trim(curl_exec($ch)), true);
curl_close($ch);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$ab_config['files_url'].'/static/update/error9.txt');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$archive = @trim(curl_exec($ch));
curl_close($ch);

if ($actual['md5'] == md5($archive)) {
file_put_contents(__DIR__.'/../data/error.txt', $archive, LOCK_EX);
$content .= '<div class="alert alert-success" role="alert">
'.abTranslate('Update has been successful.').'
</div>
<script>document.location.href="?'.$abw.$abp.'=update";</script>
';
} else {
$content .= '<div class="alert alert-danger" role="alert">
'.abTranslate('Update failed.').'
</div>';
}
} else {
echo '<script>document.location.href="?'.$abw.$abp.'=update";</script>';
abDie();
} 

$title = abTranslate('Update');
