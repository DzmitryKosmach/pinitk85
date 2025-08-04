<?php

/** Админка: Оформленные заказы
 * @author    Seka
 */

class mOrders extends Admin {

	/**
	 * @var int
	 */
    static $adminMenu = Admin::ORDERS;

    /**
     * @var string
     */
    var $mainClass = 'Orders';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_ORDERS;

	/**
	 *
	 */
	const ON_PAGE = 20;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array()) {
		$o = new self();
		$o->checkRights();
		$o->getOperations();

		if(intval($_GET['status'])){
			return self::setStatus(intval($_GET['order_id']), intval($_GET['status']));
		}

		// Статусы заказов
		$oStatuses = new Orders_Statuses();
		$statuses = $oStatuses->get(
			'*',
			'',
			'order'
		);

		$inArchive = intval($_GET['in_archive']);

		// Получаем список
		$oOrders = new Orders();
		list($orders, $toggle) = $oOrders->getByPage(
			intval($_GET['page']),
			self::ON_PAGE,
			'*',
			'`in_archive` = ' . $inArchive,
			'`date` DESC'
		);
		foreach($orders as &$o){
			$o['cart'] = unserialize($o['cart']);
			$o['options'] = unserialize($o['options']);
			$o['user'] = unserialize($o['user']);
		}
		unset($o);


        $seriesIds = [];

        foreach($orders as $o)
        {
            foreach($o['cart'] as $cart) {
                //print_array($cart['series']);
                if (!isset($cart['series']['id'])) {
                    continue;
                }
                $seriesIds[] = $cart['series']['id'];
            }
        }

        $seriesIds = array_unique($seriesIds);

        $suppliers = [];

        if (count($seriesIds)) {
            $oSeries = new Catalog_Series();
            $supp = $oSeries->get(
                'id, supplier_id, cs.name',
                '`catalog_series`.`id` in (' . implode(',', $seriesIds) . ')',
                '`catalog_series`.`id` ASC',
                0,
                'left join catalog_suppliers as cs on cs.id=`catalog_series`.`supplier_id`'
            );

            foreach ($supp as $s) {
                $suppliers[$s['id']] = $s;
            }
        }

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'orders'	=> $orders,
            'suppliers' => $suppliers,
			'toggle'	=> $toggle,
			'statuses'	=> $statuses
        ));
    }


	static function setStatus($orderId, $statusId){
		$oOrders = new Orders();
		$oOrders->setStatus($orderId, $statusId);
		Pages::flash('Статус заказа успешно изменён');
		exit;
	}


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$oOrders = new Orders();
		$oOrders->del(intval($iId));
		Pages::flash('заказ удалён из базы.');
    }
}
