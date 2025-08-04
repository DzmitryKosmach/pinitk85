<?php

/**
 * Генерация XML карты сайта
 * Запускать раз в сутки
 */

require_once(dirname(dirname(__FILE__)) . '/includes.php');

$oSitemap = new Sitemap();
$output = $oSitemap->makeXML();
// $output = substr($output, 1);
// $output = '<' . $output;
// $output = str_replace(array("\r\n", "\r", "\n"), '',  $output);

header('Content-type: application/xml');
echo $output;
exit();
print 'Ok';

?>
