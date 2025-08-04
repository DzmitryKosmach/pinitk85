<?php

/** Админка: Слайдер для главной страницы
 * @author    Seka
 */

class mSliderindex extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CONTENT;

    /**
     * @var string
     */
    var $mainClass = 'Slider_Index';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_PAGES;


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
        $oSlider = new Slider_Index();
		$slides = $oSlider->imageExtToData($oSlider->get(
			'*',
			'',
			'order'
		));

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'slides'	=> $slides
        ));
    }







    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oSlider = new Slider_Index();
		$oSlider->del(intval($iId));

		Pages::flash('Картинка слайдера успешно удалена.');
    }
}

?>