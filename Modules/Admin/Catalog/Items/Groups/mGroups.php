<?php

/** Админка: Группы товаров в серии
 *
 * @author	Seka
 */

class mGroups extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

	/**
	 * @var string
	 */
	var $mainClass = 'Catalog_Items_Groups0';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CATALOG;

	/**
	 * @var	int
	 */
	static $seriesId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();
		$o->getOperations();

		// Получаем инфу о серии
		self::$seriesId = intval($_GET['s']);
		$oSeries = new Catalog_Series();
		$seriesInf = $oSeries->getRow('*', '`id` = ' . self::$seriesId);
		if(!$seriesInf){
			Pages::flash('Не найдена серия для просмотра списка групп товаров.', true, Url::a('admin-catalog-series'));
			exit;
		}

		if(trim($_GET['act']) == 'search-group'){
			self::searchGroup(intval($_GET['gid']), intval($_GET['on']));
			exit;
		}

		// Получаем группы товаров
		$oItemsGroups = new Catalog_Items_Groups0();
		$groups = $oItemsGroups->getWhtKeys(
			'*',
			'`series_id` = ' . self::$seriesId,
			'order'
		);

		// Выводим страницу
   		 $tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'seriesInf'	=> $seriesInf,
			'groups'	=> $groups
		));
	}


	static function searchGroup($groupId, $on){
		$groupId = intval($groupId);

		$oSeries = new Catalog_Series();
		$oSeries->upd(
			self::$seriesId,
			array(
				'price_search_group_id'	=> $on ? $groupId : 0
			)
		);

		Pages::flash('Группа поиска изменена.');
		exit;
	}


	/** Удаление
	 * @param $iId
	 */
	function delItem($iId){
		$oItemsGroups = new Catalog_Items_Groups0();
		$oItemsGroups->del(intval($iId));

		Pages::flash('Группа товаров удалена (но не товары в ней).');
	}
}

?>