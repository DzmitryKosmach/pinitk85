<?php

/** Админка: Значения для фильтров
 * @author    Seka
 */

class mValues extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Filters_Values';

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
		$oFiltersValues = new Catalog_Filters_Values();

        // Находим фильтр
        $filterId = intval($_GET['f']);
        $filter = $oFilters->getRow('*', '`id` = ' . $filterId);
		if(!$filter){
			Pages::flash('Запрошенный для редактирования фильтр не найден.', true, Url::a('admin-catalog-filters'));
			exit;
		}

        // Получаем список значений
        $values = $oFiltersValues->get(
			'*',
			'`filter_id` = ' . $filterId,
            'order'
        );

		// Получаем для каждого значения к-во соответствующих ему серий
		$vIds = array();
		foreach($values as $v) $vIds[] = $v['id'];
		if(count($vIds)){
			$oSeries2Filters = new Catalog_Series_2Filters();
			$seriesCnt = $oSeries2Filters->getHash(
				'value_id, COUNT(*)',
				'`value_id` IN (' . implode(',', $vIds) . ')',
				'',
				0,
				'',
				'value_id'
			);
			foreach($values as &$v){
				$v['series_cnt'] = isset($seriesCnt[$v['id']]) ? intval($seriesCnt[$v['id']]) : 0;
			}
			unset($v);
		}

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
			'filter'	=> $filter,
            'values'	=> $values
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oFiltersValues = new Catalog_Filters_Values();
		$oFiltersValues->del(intval($iId));
		Pages::flash('Вариант значения для фильтра удалён.');
    }
}

?>