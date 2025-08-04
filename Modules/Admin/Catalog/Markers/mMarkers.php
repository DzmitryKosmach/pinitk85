<?php

/** Админка: Маркеры (ленточки/флажки) для серий
 * @author    Seka
 */

class mMarkers extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Markers';

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
		$oMarkers = new Catalog_Markers();
		$markers = $oMarkers->get('*');

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'markers'	=> $markers
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oMarkers = new Catalog_Markers();
		$oMarkers->del(intval($iId));
		Pages::flash('Маркер удалён.');
    }
}

?>