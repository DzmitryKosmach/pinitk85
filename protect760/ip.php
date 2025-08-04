<?php
header('Access-Control-Allow-Origin: *');
// CloudFlare https://www.cloudflare.com/ips-v4
$ab_proxy['173.245.48.0/20'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['103.21.244.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['103.22.200.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['103.31.4.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['141.101.64.0/18'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['108.162.192.0/18'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['190.93.240.0/20'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['188.114.96.0/20'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['197.234.240.0/22'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['198.41.128.0/17'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['162.158.0.0/15'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['104.16.0.0/13'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['104.24.0.0/14'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['172.64.0.0/13'] = 'HTTP_CF_CONNECTING_IP';
$ab_proxy['131.0.72.0/22'] = 'HTTP_CF_CONNECTING_IP';

require_once(__DIR__.'/data/proxy.php');
require_once(__DIR__.'/code/func.php');

$ab_config['ip'] = isset($_SERVER['REMOTE_ADDR']) ? trim(strip_tags($_SERVER['REMOTE_ADDR'])) : abDie('Remote Addr Error');

// check for cloudflare and proxy:
if (filter_var($ab_config['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
foreach ($ab_proxy as $proxy_mask => $proxy_attr) {
if (net_match($proxy_mask, $ab_config['ip']) == 1 AND isset($_SERVER[$proxy_attr])) {
$ab_config['ip'] = $_SERVER[$proxy_attr];
break;
}
}
}

echo $ab_config['ip'];
