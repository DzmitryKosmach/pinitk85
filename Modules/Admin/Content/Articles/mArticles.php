<?php

/** Админка: Статьи
 * @author    Seka
 */

class mArticles extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CONTENT;

    /**
     * @var string
     */
    var $mainClass = 'Articles';

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
		$oArticles = new Articles();
		$articles = $oArticles->imageExtToData($oArticles->get(
			'*',
			'',
			'`order` DESC'
		));

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'articles'	=> $articles
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oArticles = new Articles();
		$oArticles->del(intval($iId));

		Pages::flash('Статья успешно удалена.');
    }
}

?>