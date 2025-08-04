<?php

/** Админка: Курсы валют
 * @author    Seka
 */

class mUsdcourse extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_UsdCourses';

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

		if(isset($_POST['course_glob'])){
			Options::set(
				'global-usd-course',
				abs(str_replace(',', '.', trim($_POST['course_glob'])))
			);
			Pages::flash('Общий курс валюты сохранён');
		}

		// Получаем список
		$oUsdCourses = new Catalog_UsdCourses();
		$usdCourses = $oUsdCourses->get(
			'*',
			'',
			'name'
		);

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'usdCourses'	=> $usdCourses
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oUsdCourses = new Catalog_UsdCourses();
		$oUsdCourses->del(intval($iId));
		Pages::flash('Курс валют успешно удалён.');
    }
}

?>