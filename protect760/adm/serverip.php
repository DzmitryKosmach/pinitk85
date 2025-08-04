<?php
// обновление
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Server IP addresses');

$content .= '<p>'.abTranslate('Your server\'s IP addresses. They must be in the allow rules. They are automatically added when you navigate to this page and during the initial setup of the antibot script.').'</p>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://ipv4.mikfoxi.com/');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$serverIPv4 = @trim(strip_tags(curl_exec($ch)));
curl_close($ch);

if (filter_var($serverIPv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
$content .= '<p>IPv4: <code>'.$serverIPv4.'</code></p>';
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '".$serverIPv4."', '".AbIp2num($serverIPv4)."', '".AbIp2num($serverIPv4)."', 'allow', 'Server IPv4', '9999999999');");
} else {
$content .= '<p>IPv4: <code>'.abTranslate('undefined or does not exist').'</code></p>';
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://ipv6.mikfoxi.com/');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'AntiBot');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
$serverIPv6 = @trim(strip_tags(curl_exec($ch)));
curl_close($ch);

if (filter_var($serverIPv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
$content .= '<p>IPv6: <code>'.$serverIPv6.'</code></p>';
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('10', '".abExpand($serverIPv6)."', '".AbIp2num(abExpand($serverIPv6))."', '".AbIp2num(abExpand($serverIPv6))."', 'allow', 'Server IPv6', '9999999999');");
} else {
$content .= '<p>IPv6: <code>'.abTranslate('undefined or does not exist').'</code></p>';
}
