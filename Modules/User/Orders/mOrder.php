<?php

/** Просмотр заказа
 * @author	Seka
 */

class mOrder {

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){

		$oOrders = new Orders();
		$orderId = $oOrders->detectByUrl();

		if($orderId){
			$order = $oOrders->getRow(
				'*',
				'`id` = ' . intval($orderId)
			);
			if($order){
				$order['cart'] = unserialize($order['cart']);
				$order['options'] = unserialize($order['options']);
				$order['user'] = unserialize($order['user']);
			}
		}else{
			$order = false;
		}

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf,
			'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
			'order'		=> $order
		));
	}
}

?>