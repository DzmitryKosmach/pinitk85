<?php

/** Админка: Серии металлической мебели
 * @author    Seka
 */

class mMetallSeries extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_MetallSeries';

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

		// Получаем список
		$oMetallSeries = new Catalog_MetallSeries();
		$metallSeries = $oMetallSeries->get(
			'*',
			'',
			'name'
		);

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'metallSeries'	=> $metallSeries
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oMetallSeries = new Catalog_MetallSeries();
		$oMetallSeries->del(intval($iId));
		Pages::flash('Серия металлической мебели успешно удалена.');
    }
}

?>