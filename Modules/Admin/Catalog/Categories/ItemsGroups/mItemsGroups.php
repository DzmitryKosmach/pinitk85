<?php

/** Админка: Группы товаров в сериях категории
 *
 * @author	Seka
 */

class mItemsGroups extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

	/**
	 * @var string
	 */
	var $mainClass = 'Catalog_Items_Groups';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CATALOG;

	/**
	 * @var	int
	 */
	static $catId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();
		$o->getOperations();

		// Данные категории
		$categoryId = intval($_GET['c']);
		$oCategories = new Catalog_Categories();
		$categoryInf = $oCategories->getRow('*', '`id` = ' . $categoryId);
		if(!$categoryInf){
			Pages::flash('Запрошенная категория не найдена.', true, Url::a('admin-catalog-categories'));
			exit;
		}
		if(intval($categoryInf['has_subcats'])){
			Pages::flash('Категория с подкатегориями не может содержать серии, а значит и группы товаров.', true, Url::a('admin-catalog-categories'));
			exit;
		}

		// Получаем группы товаров
		$oItemsGroups = new Catalog_Items_Groups();
		$groups = $oItemsGroups->get(
			'*',
			'`category_id` = ' . $categoryId,
			'order'
		);

		// К-во товаров в каждой группе
		$gIds = array();
		foreach($groups as $g) $gIds[] = $g['id'];
		if(count($gIds)){
			$oItems = new Catalog_Items();
			$itemsCnt = $oItems->getHash(
				'group_id, COUNT(*)',
				'`group_id` IN (' . implode(',', $gIds) . ')',
				'',
				0,
				'',
				'group_id'
			);

			foreach($groups as &$g){
				$g['items_cnt'] = isset($itemsCnt[$g['id']]) ? intval($itemsCnt[$g['id']]) : 0;
			}
			unset($g);
		}

		// Выводим страницу
   		 $tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'categoryInf'	=> $categoryInf,
			'groups'		=> $groups
		));
	}


	/** Удаление
	 * @param $iId
	 */
	function delItem($iId){
		$oItemsGroups = new Catalog_Items_Groups();
		$oItemsGroups->del(intval($iId));

		Pages::flash('Группа товаров удалена (но не товары в ней).');
	}
}

?>