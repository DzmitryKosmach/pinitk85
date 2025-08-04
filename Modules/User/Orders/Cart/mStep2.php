<?php

/** Оформление заказа, Шаг 1
 * @author	Seka
 */

class mStep2 {

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

		// Проверяем, введены ли данные юзера
		if(!isset($_SESSION['cart-user']) || !is_array($_SESSION['cart-user'])){
			header('Location: ' . Url::a('catalog-cart-step1'));
			exit;
		}

		// Исходные данные
		$init = array();

		if(isset($_SESSION['cart-options']) && is_array($_SESSION['cart-options'])){
			$init['options'] = $_SESSION['cart-options'];
		}else{
			$init['options'] = Orders_Options::$default;
		}

		// Стоимость опций (доставка, сборка и проч.)
		$oOptions = new Orders_Options();
		$optionsPrices = $oOptions->calc($cart, $init['options']);

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'pageInf'		=> $pageInf,
			'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
			'cartPageName'	=> $cartPageName,
			'cart'			=> $cart,
			'optionsPrices'	=> $optionsPrices,

			'totalAmount'	=> $totalAmount,
			'totalPrice'	=> $totalPrice,
			'totalPriceOld'	=> $totalPriceOld
		));
		// Выводим форму
		$frm = new Form($formHtml, false, false, false);
		$frm->setInit($init);
		return $frm->run();
	}
}

?>