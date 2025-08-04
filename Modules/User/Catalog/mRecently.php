<?php

/**
 * @author	Seka
 */

class mRecently {

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

		$recentlyIds = Catalog_Recently::get();
		$recentlyIds[] = 0;

		$oSeries = new Catalog_Series();
		if(intval($_GET['onpage']) === -1){
			$series = $oSeries->get(
				'*',
				'`id` IN (' . implode(',', $recentlyIds) . ') AND `out_of_production` = 0',
				'order'
			);
			$toggle = '';
			$pgNum = 1;
		}else{
			list($series, $toggle, $pgNum, $seriesCnt) = $oSeries->getByPage(
				intval($_GET['page']),
				self::SERIES_ON_PAGE_DEFAULT,
				'*',
				'`id` IN (' . implode(',', $recentlyIds) . ') AND `out_of_production` = 0',
				'order'
			);
		}
		$series = $oSeries->details($series);

		//
		if($pgNum > 1){
			$pageInf['dscr'] = '';
			$pageInf['kwrd'] = '';
		}

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf,
			'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
			'series'	=> $series,
			'toggle'	=> $toggle
		));
	}
}

?>