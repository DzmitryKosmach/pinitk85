<?php

/** Админка: Тексты и заголовки страниц
 * @author    Seka
 */

class mPages extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CONTENT;

    /**
     * @var string
     */
    var $mainClass = 'Pages';

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

        $oPages = new Pages();

        // Находим текущую страницу-родителя, если она задана
        $parentId = intval($_GET['p']);
        $parentPage = false;
        if($parentId){
            $parentPage = $oPages->getRow('*', '`id` = ' . $parentId);

            if($parentPage['id'] == 1){
                Pages::flash('У данной страницы не может быть вложенных страниц.', true, Url::a('admin-content-pages'));
            }
        }

        // Находим детей текущей страницы-родителя
        $pages = $oPages->get(
            Pages::$tab . '.*, IF(p2.parent_id, COUNT(*), 0) AS subcnt',
            Pages::$tab . '.`parent_id` = ' . $parentId . ' AND ' . Pages::$tab . '.admin = 0',
            'order',
            0,
            'LEFT JOIN `' . Pages::$tab . '` AS p2 ON (' . Pages::$tab . '.id = p2.parent_id)',
            'id'
        );

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'parentPage'	=> $parentPage,
            'pages'			=> $pages
        ));
    }


    /** Удаление страницы
     * @param $iId
     */
    function delItem($iId){
        $oPages = new Pages();
        $res = $oPages->del(intval($iId));

        if($res == 1){
            Pages::flash('Страница успешно удалена.', false);
        }elseif($res > 1){
            Pages::flash('Страница и вложенные в неё подразделы удалены.', false);
        }else{
            Pages::flash('Данную страницу нельзя удалить.', true);
        }
    }
}

?>