<?php

/** Админка: Категории каталога
 * @author    Seka
 */

class mCategories extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Categories';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CATALOG;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array()) {
		$o = new self();
		$o->checkRights();
		$o->getOperations();

        $oCategories = new Catalog_Categories();

        // Находим текущую категорию-родителя, если она задана
        $parentId = intval($_GET['p']);
        $parentCat = false;
        if($parentId){
			$parentCat = $oCategories->getRow('*', '`id` = ' . $parentId);

			if(!intval($parentCat['has_subcats'])){
				Pages::flash('Запрошенная категория не может содержать подкатегории.', true, Url::a('admin-catalog-categories'));
				exit;
			}

			$catsDeepLevel = 1 + $oCategories->getDeepLevel($parentId);

        }else{
			$catsDeepLevel = 1;
		}

        // Получаем категории для указанного родителя (или категории верхнего уровня)
		$categories = $oCategories->get(
			'*',
			'`parent_id` = ' . $parentId,
			'order'
		);
		//if(!$parentId){
			$categories = $oCategories->imageExtToData($categories);
		//}

		// Получаем для каждой категории к-во подкатегорий и серий
		$cIds = array();
		foreach($categories as $c) $cIds[] = $c['id'];
		if(count($cIds)){
			if($catsDeepLevel < Catalog_Categories::MAX_DEEP_LEVEL){
				// Подкатегории (не для последнего уровня)
				$subCnt = $oCategories->getHash(
					'parent_id, COUNT(*)',
					'`parent_id` IN (' . implode(',', $cIds) . ')',
					'',
					0,
					'',
					'parent_id'
				);
			}

			// Серии
			$oSeries = new Catalog_Series();
			$seriesCnt = $oSeries->getHash(
				'category_id, COUNT(*)',
				'`category_id` IN (' . implode(',', $cIds) . ') AND `out_of_production` = 0',
				'',
				0,
				'',
				'category_id'
			);

			// Группы теговых страниц
			$oPagesGroups = new Catalog_Pages_Groups();
			$groupsCnt = $oPagesGroups->getHash(
				'category_id, COUNT(*)',
				'`category_id` IN (' . implode(',', $cIds) . ')',
				'',
				0,
				'',
				'category_id'
			);

			// Группы товаров в сериях
			$oItemsGroups = new Catalog_Items_Groups();
			$itemsGroupsCnt = $oItemsGroups->getHash(
				'category_id, COUNT(*)',
				'`category_id` IN (' . implode(',', $cIds) . ')',
				'',
				0,
				'',
				'category_id'
			);

			// Названия характеристик серий
			$oOpts4Series = new Catalog_Categories_Opts4Series();
			$opts4SeriesCnt = $oOpts4Series->getHash(
				'category_id, COUNT(*)',
				'`category_id` IN (' . implode(',', $cIds) . ')',
				'',
				0,
				'',
				'category_id'
			);

			// Отображаемые в категории группы товаров и товары
			$oItemsLinks = new Catalog_Categories_ItemsLinks();
			$itemsLinksGroups = $oItemsLinks->getHash(
				'category_id, COUNT(*)',
				'`category_id` IN (' . implode(',', $cIds) . ') AND `page_id` = 0 AND `item_id` = 0',
				'',
				0,
				'',
				'category_id'
			);
			$itemsLinksItems = $oItemsLinks->getHash(
				'category_id, COUNT(*)',
				'`category_id` IN (' . implode(',', $cIds) . ') AND `page_id` = 0 AND `item_id` != 0',
				'',
				0,
				'',
				'category_id'
			);

			foreach($categories as &$c){
				if($catsDeepLevel < Catalog_Categories::MAX_DEEP_LEVEL){
					$c['sub_cnt'] = isset($subCnt[$c['id']]) ? intval($subCnt[$c['id']]) : 0;
				}
				$c['series_cnt'] = isset($seriesCnt[$c['id']]) ? intval($seriesCnt[$c['id']]) : 0;
				$c['groups_cnt'] = isset($groupsCnt[$c['id']]) ? intval($groupsCnt[$c['id']]) : 0;
				$c['items_groups_cnt'] = isset($itemsGroupsCnt[$c['id']]) ? intval($itemsGroupsCnt[$c['id']]) : 0;
				$c['opts4series_cnt'] = isset($opts4SeriesCnt[$c['id']]) ? intval($opts4SeriesCnt[$c['id']]) : 0;
				$c['items_links_groups'] = isset($itemsLinksGroups[$c['id']]) ? intval($itemsLinksGroups[$c['id']]) : 0;
				$c['items_links_items'] = isset($itemsLinksItems[$c['id']]) ? intval($itemsLinksItems[$c['id']]) : 0;
			}
			unset($c);
		}

		$oYmlFiles = new Catalog_Yml_Files();
		$ymlFiles = $oYmlFiles->getHash('id, file');

		// Домены
		$oDomains = new Catalog_Domains();
		$domains = $oDomains->getHash(
			'id, domain',
			'',
			'`domain` ASC'
		);

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'parentCat'		=> $parentCat,
			'catsDeepLevel'	=> $catsDeepLevel,
            'categories'	=> $categories,
			'ymlFiles'		=> $ymlFiles,
			'domains'		=> $domains
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oCategories = new Catalog_Categories();
       	$oCategories->del(intval($iId));
		Pages::flash('Категория с подкатегориями, сериями и товарами успешно удалена.');
    }
}

?>