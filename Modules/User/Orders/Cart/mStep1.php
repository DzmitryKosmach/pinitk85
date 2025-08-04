<?php

/** Оформление заказа, Шаг 1
 * @author	Seka
 */

class mStep1 {

	/**
	 * @var int
	 */
	static $output = OUTPUT_DEFAULT;

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){

		Catalog_Cart::fix();

		// Данные корзины
		$cart = Catalog_Cart::get();
		if(!count($cart) || Dealers_Security::isAuthorized()){
			header('Location: ' . Url::a('catalog-cart'));
			exit;
		}
		list($totalAmount, $totalPrice, $totalPriceOld) = Catalog_Cart::total();

		// Название страницы "Корзина" для хлебных крошек
		$oPages = new Pages();
		$cartPageName = $oPages->getCell('name', '`alias` = \'catalog-cart\'');

		// Исходные данные
		$init = array();
		if(isset($_SESSION['cart-user']) && is_array($_SESSION['cart-user'])){
			$init['user'] = $_SESSION['cart-user'];
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'pageInf'		=> $pageInf,
			'cartPageName'	=> $cartPageName,
			'cart'			=> $cart,
			'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
			'totalAmount'	=> $totalAmount,
			'totalPrice'	=> $totalPrice,
			'totalPriceOld'	=> $totalPriceOld,

			'userInf'	=> $init['user']
		));
		//return $formHtml;

		// Выводим форму
		$frm = new Form($formHtml, 'cart-form1');
		$frm->setInit($init);
		return $frm->run('mStep1::save');
	}


	/** Переход к следующему шагу оформления заказа
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$_SESSION['cart-user'] = $newData['user'];

		header('Location: ' . Url::a('catalog-cart-step2'));
		exit;
	}
}

?>