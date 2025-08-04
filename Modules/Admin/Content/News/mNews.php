<?php

/** Админка: Новости
 * @author    Seka
 */

class mNews extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CONTENT;

    /**
     * @var string
     */
    var $mainClass = 'News';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_NEWS;


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
		$oNews = new News();
		$news = $oNews->imageExtToData($oNews->get(
			'*',
			'',
			'`date` DESC, `id` DESC'
		));

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'news'		=> $news
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oNews = new News();
		$oNews->del(intval($iId));

		Pages::flash('Новость успешно удалена.');
    }
}

?>