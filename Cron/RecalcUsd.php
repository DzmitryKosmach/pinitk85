<?php

/**
 * Пересчёт мин. и макс. цен на товары, для серий которых используется общий курс у.е.
 * Запускать раз в час
 */

require_once(dirname(dirname(__FILE__)) . '/includes.php');

$oSeries = new Catalog_Series();
$sIds = $oSeries->getCol(
	'id',
	'`usd_course` = 0'
);
if(count($sIds)){
	$oItems = new Catalog_Items();
	$iIds = $oItems->getCol(
		'id',
		'`series_id` in (' . implode(',', $sIds) . ')'
	);
	foreach($iIds as $iId){
		$oItems->calcDeferred($iId);
	}
}

print 'ok';

?>
