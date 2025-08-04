<?php

/** Админка: Группы теговых страниц категории
 * @author    Seka
 */

class mGroups extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Pages_Groups';

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

        $oGroups = new Catalog_Pages_Groups();

		// Данные категории
        $categoryId = intval($_GET['c']);
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

        // Список групп
        $groups = $oGroups->get(
			'*',
			'`category_id` = ' . $categoryId,
            'order'
        );

		// Получаем для каждой группы к-во теговых страниц
		$gIds = array();
		foreach($groups as $g) $gIds[] = $g['id'];
		if(count($gIds)){
			$oPages = new Catalog_Pages();
			$pagesCnt = $oPages->getHash(
				'group_id, COUNT(*)',
				'`group_id` IN (' . implode(',', $gIds) . ')',
				'',
				0,
				'',
				'group_id'
			);


			foreach($groups as &$g){
				$g['pages_cnt'] = isset($pagesCnt[$g['id']]) ? intval($pagesCnt[$g['id']]) : 0;
			}
			unset($g);
		}

        // Выводим шаблон
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
		$oGroups = new Catalog_Pages_Groups();
		$oGroups->del(intval($iId));
		Pages::flash('Группа теговых страниц категории успешно удалена.');
    }
}

?>