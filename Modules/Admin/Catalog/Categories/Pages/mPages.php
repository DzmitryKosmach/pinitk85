<?php

/** Админка: Теговые страницы категории
 * @author    Seka
 */

class mPages extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Pages';

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

        $oPages = new Catalog_Pages();

		// Данные группы
		$groupId = intval($_GET['g']);
		$oGroups = new Catalog_Pages_Groups();
		$groupInf = $oGroups->getRow('*', '`id` = ' . $groupId);
		if(!$groupInf){
			Pages::flash('Запрошенная группа теговых страниц не найдена.', true, Url::a('admin-catalog-categories'));
			exit;
		}

		// Данные категории
        $categoryId = $groupInf['category_id'];
		$oCategories = new Catalog_Categories();
		$categoryInf = $oCategories->getRow('*', '`id` = ' . $categoryId);
		if(!$categoryInf){
			Pages::flash('Запрошенная категория не найдена.', true, Url::a('admin-catalog-categories'));
			exit;
		}
		/*if(intval($categoryInf['has_subcats'])){
			Pages::flash('Категория с подкатегориями не может содержать теговых страниц.', true, Url::a('admin-catalog-categories'));
			exit;
		}*/

        // Список страниц
        $pages = $oPages->get(
			'*',
			'`group_id` = ' . $groupId,
            'order'
        );

		// Получаем для каждой категории к-во подкатегорий и серий
		$pIds = array();
		foreach($pages as $p) $pIds[] = $p['id'];
		if(count($pIds)){
			// Отображаемые на странице группы товаров и товары
			$oItemsLinks = new Catalog_Categories_ItemsLinks();
			$itemsLinksGroups = $oItemsLinks->getHash(
				'page_id, COUNT(*)',
				'`page_id` IN (' . implode(',', $pIds) . ') AND `item_id` = 0',
				'',
				0,
				'',
				'page_id'
			);
			$itemsLinksItems = $oItemsLinks->getHash(
				'page_id, COUNT(*)',
				'`page_id` IN (' . implode(',', $pIds) . ') AND `item_id` != 0',
				'',
				0,
				'',
				'page_id'
			);

			foreach($pages as &$p){
				$p['items_links_groups'] = isset($itemsLinksGroups[$p['id']]) ? intval($itemsLinksGroups[$p['id']]) : 0;
				$p['items_links_items'] = isset($itemsLinksItems[$p['id']]) ? intval($itemsLinksItems[$p['id']]) : 0;
			}
			unset($p);
		}

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
			'groupInf'		=> $groupInf,
			'categoryInf'	=> $categoryInf,
			'pages'		=> $pages
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oPages = new Catalog_Pages();
		$oPages->del(intval($iId));
		Pages::flash('Теговая страница категории успешно удалена.');
    }
}

?>