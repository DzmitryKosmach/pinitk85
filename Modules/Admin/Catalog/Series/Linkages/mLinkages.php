<?php

/** Админка: Группы (блоки) связей между сериями
 * @author    Seka
 */

class mLinkages extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Series_Linkage';

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
		$oLinkage = new Catalog_Series_Linkage();
		$linkages = $oLinkage->get(
			'*',
			'',
			'order'
		);

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'linkages'	=> $linkages
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oLinkage = new Catalog_Series_Linkage();
		$oLinkage->del(intval($iId));
		Pages::flash('Блок связей между сериями успешно удалён.');
    }
}

?>