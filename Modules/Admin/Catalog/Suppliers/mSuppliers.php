<?php

/** Админка: Поставщики
 * @author    Seka
 */

class mSuppliers extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_SUPPLIERS;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Suppliers';


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array()) {
		$o = new self();
		$o->checkRights();
		$o->getOperations();

        $oSuppliers = new Catalog_Suppliers();

		// Получаем список поставщиков
		$suppliers = $oSuppliers->imageExtToData($oSuppliers->get(
			'*',
			'',
			'`name` ASC'
		));

		// Получаем к-во серий и материалов, связанных с каждым поставщиком
		$sIds = array();
		foreach($suppliers as $s) $sIds[] = $s['id'];
		if(count($sIds)){
			$oSeries = new Catalog_Series();
			$seriesCnt = $oSeries->getHash(
				'supplier_id, COUNT(*)',
				'`supplier_id` IN (' . implode(',', $sIds) . ')',
				'',
				0,
				'',
				'supplier_id'
			);
			$oMaterials = new Catalog_Materials();
			$materialsCnt = $oMaterials->getHash(
				'supplier_id, COUNT(*)',
				'`supplier_id` IN (' . implode(',', $sIds) . ') AND `parent_id` = 0',
				'',
				0,
				'',
				'supplier_id'
			);
			foreach($suppliers as &$s){
				$s['series_cnt'] = isset($seriesCnt[$s['id']]) ? intval($seriesCnt[$s['id']]) : 0;
				$s['materials_cnt'] = isset($materialsCnt[$s['id']]) ? intval($materialsCnt[$s['id']]) : 0;
			}
			unset($s);
		}

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'suppliers'	=> $suppliers
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oSuppliers = new Catalog_Suppliers();
		$oSuppliers->del(intval($iId));
		Pages::flash('Поставщик успешно удалён.');
    }
}

?>