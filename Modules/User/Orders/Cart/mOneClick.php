<?php

/**
 * Оформление заказа за 1 клик
 * Форма, откуда приходят данные в этот модуль, находится на 1-м шаге оформления заказа
 * Здесь только обрабатывается данные и сохраняется заказ
 * @author	Seka
 */

class mOneClick {

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

		// Проверка данных из формы
		$ok = true;
		$fields = array('fio', 'phone');
		foreach($fields as $f){
			if(!isset($_POST['quick'][$f]) || trim($_POST['quick'][$f]) === ''){
				$ok = false;
				break;
			}
		}
		if(!$ok){
			Pages::flash(
				'Проверьте корректность введённых данных для быстрого оформления заказа (ФИО и телефон)',
				true,
				Url::a('catalog-cart-step1')
			);
			exit;
		}

		// Вычисляем стоимость опций заказа
		$oOptions = new Orders_Options();
		$options = Orders_Options::$default;
		$optionsPrices = $oOptions->calc($cart, $options);

		// Запоминаем данные юзера в сессию
		$user = array();
		if(!is_array($_SESSION['cart-user'])){
			$_SESSION['cart-user'] = array();
		}
		foreach($fields as $f){
			$user[$f] = trim($_POST['quick'][$f]);
			$_SESSION['cart-user'][$f] = trim($_POST['quick'][$f]);
		}

		// Сохраняем заказ
		$user['info'] = 'Оформление заказа за 1 клик';
		$oOrders = new Orders();
		$orderId = $oOrders->newOrder(
			$cart,
			$optionsPrices,
			$user,
			Orders::PAYMETHOD_NO
		);

		Catalog_Cart::clear();

		Pages::flash(
			'Ваш заказ успешно оформлен.<br>В ближайшее время с вами свяжется представитель для уточнения деталей.',
			false,
			Orders::a($orderId)
		);
	}
}

?>
