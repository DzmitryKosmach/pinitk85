<?php

/** Админка: просмотр информации о поставщике
 * @author	Seka
 */


class mSuppliersView extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CATALOG;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$sId = intval($_GET['id']);

		$oSuppliers = new Catalog_Suppliers();
		$supplier = $oSuppliers->imageExtToData($oSuppliers->getRow(
			'*',
			'`id` = ' . $sId
		));
		if($supplier === false){
			Pages::flash('Запрошенный для редактирования поставщик не найден.', true);
		}

		//
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'supplier'	=> $supplier
		));
	}
}

?>