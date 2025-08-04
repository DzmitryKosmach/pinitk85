<?php

/** Админка: Статусы заказов
 * @author    Seka
 */

class mStatuses extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::ORDERS;

    /**
     * @var string
     */
    var $mainClass = 'Orders_Statuses';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_ORDERS;


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
		$oStatuses = new Orders_Statuses();
		$statuses = $oStatuses->get(
			'*',
			'',
			'order'
		);

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'statuses'	=> $statuses
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oStatuses = new Orders_Statuses();
		$oStatuses->del(intval($iId));
		Pages::flash('Статус заказов удалён.');
    }
}

?>