<?php

/** Админка: Отображение в серии товаров из других серий
 *
 * @author	Seka
 */

class mCrossItems extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CATALOG;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		// Получаем инфу о серии
		$seriesId = intval($_GET['s']);
		$oSeries = new Catalog_Series();
		$seriesInf = $oSeries->getRow('*', '`id` = ' . $seriesId);
		if(!$seriesInf){
			Pages::flash('Не найдена серия для просмотра списка групп товаров.', true, Url::a('admin-catalog-series'));
			exit;
		}

		// Получаем группы товаров
		$oItemsGroups = new Catalog_Items_Groups();
		$groups = $oItemsGroups->get(
			'id, name',
			'`category_id` = ' . $seriesInf['category_id'],
			'order'
		);
		array_unshift(
			$groups,
			array(
				'id'	=> 0,
				'name'	=> 'Без группы'
			)
		);

		//
		$gIds = array();
		foreach($groups as $g) $gIds[] = $g['id'];
		if(count($gIds)){
			// Отображаемые на стр. серии товары из других серий
			$oCrossItems = new Catalog_Series_CrossItems();
			$crossItems = $oCrossItems->getHash(
				'where_group_id, COUNT(*)',
				'`where_series_id` = ' . $seriesId . ' AND `where_group_id` IN (' . implode(',', $gIds) . ') AND `item_id` != 0',
				'',
				0,
				'',
				'where_group_id'
			);

			foreach($groups as &$g){
				$g['cross_items'] = isset($crossItems[$g['id']]) ? intval($crossItems[$g['id']]) : 0;
			}
			unset($g);
		}

		// Выводим страницу
   		 $tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'seriesInf'	=> $seriesInf,
			'groups'	=> $groups
		));
	}
}

?>