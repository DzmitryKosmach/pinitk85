<?php

/** Админка: Фильтры для серий
 * @author    Seka
 */

class mFilters extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Filters';

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

        $oFilters = new Catalog_Filters();

        // Получаем список
        $filters = $oFilters->get(
			'*',
			'',
            'order'
        );

		// Получаем для каждого фильтра к-во вариантов значений и к-во категорий, в которых он используется
		$fIds = array();
		foreach($filters as $f) $fIds[] = $f['id'];
		if(count($fIds)){
			$oFiltersValues = new Catalog_Filters_Values();
			$valuesCnt = $oFiltersValues->getHash(
				'filter_id, COUNT(*)',
				'`filter_id` IN (' . implode(',', $fIds) . ')',
				'',
				0,
				'',
				'filter_id'
			);
			$oCategories2Filters = new Catalog_Categories_2Filters();
			$catsCnt = $oCategories2Filters->getHash(
				'filter_id, COUNT(*)',
				'`filter_id` IN (' . implode(',', $fIds) . ')',
				'',
				0,
				'',
				'filter_id'
			);
			foreach($filters as &$f){
				$f['values_cnt'] = isset($valuesCnt[$f['id']]) ? intval($valuesCnt[$f['id']]) : 0;
				$f['cats_cnt'] = isset($catsCnt[$f['id']]) ? intval($catsCnt[$f['id']]) : 0;
			}
			unset($f);
		}

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'filters'		=> $filters
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oFilters = new Catalog_Filters();
		$oFilters->del(intval($iId));
		Pages::flash('Фильтр успешно удалён.');
    }
}

?>