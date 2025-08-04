<?php

exit;

/**
 * @author	Seka
 */

class mDiscounts {

	/**
	 *
	 */
	const SERIES_IN_CAT = 5;

	/**
	 * @var int
	 */
	static $output = OUTPUT_DEFAULT;

	/**
	 *
	 */
	const SERIES_ON_PAGE_DEFAULT = 20;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){

		$oCategories = new Catalog_Categories();
		$catIds = $oCategories->getFinishIds(0);
		$catIds[] = 0;
		$categories = $oCategories->get(
			'id, name, h1',
			'`id` IN (' . implode(',', $catIds) . ')',
			'FIELD(`id`, ' . implode(',', $catIds) . ')'
		);

		$oSeries = new Catalog_Series();
		foreach($categories as $cn => &$c){
			$c['series'] = $oSeries->get(
				'*',
				'`category_id` = ' . $c['id'] . ' AND (`marker_id` != 0 OR `price_min_old` > 0) AND `out_of_production` = 0',
				'RAND()',
				self::SERIES_IN_CAT
			);
			if(!count($c['series'])){
				unset($categories[$cn]);
				continue;
			}
			$c['series'] = $oSeries->details($c['series']);
		}
		unset($c);

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf,
			'categories'=> $categories
		));
	}
}

?>