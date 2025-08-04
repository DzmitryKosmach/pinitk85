<?php

/**
 * Обновление ключевых слов для поиска для всех серий
 * Запускать раз в час
 */

require_once(dirname(dirname(__FILE__)) . '/includes.php');

$oSeries = new Catalog_Series();
mtime(1);
foreach($oSeries->getCol('id') as $sId){
	$oSeries->updateKeywords($sId);
}
print mtime(1);

?>
