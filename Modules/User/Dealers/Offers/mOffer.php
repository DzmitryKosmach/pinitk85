<?php

/** Оформление / Редактирование коммерческого предложения для дилера
 * @author	Seka
 */

class mOffer {

	/**
	 * @var array
	 */
	static $cart = array();

	/**
	 * @var array
	 */
	static $options = array();

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){

		Catalog_Cart::fix();

		// Данные корзины
		self::$cart = Catalog_Cart::get();
		//print_array(self::$cart); exit;
		if(!count(self::$cart) || !Dealers_Security::isAuthorized()){
			header('Location: ' . Url::a('catalog-cart'));
			exit;
		}
		list($totalAmount, $totalPrice, $totalPriceOld) = Catalog_Cart::total();

		// Скидки, установленные дилером вручную
		$discounts = Dealers_Offers::discounts(self::$cart);

		// Фотографии всех серий всех товаров в корзине
		$sIds = array();
		foreach(self::$cart as $c){
			$sIds[] = $c['item']['series_id'];
		}
		if(count($sIds)){
			$sIds = array_unique($sIds);

			$oCategories = new Catalog_Categories();
			$oSeries = new Catalog_Series();
			$oPhotos = new Catalog_Series_Photos();

			$seriesPhotos = $oSeries->get(
				'id, name, category_id',
				'`id` IN (' . implode(',', $sIds) . ')',
				'order'
			);
			foreach($seriesPhotos as &$s){
				$photos = $oPhotos->imageExtToData($oPhotos->get(
					'*',
					'`series_id` = ' . $s['id'],
					'order'
				));
				foreach($photos as &$p){
					$p['name'] = $oCategories->nameArr($s['category_id']);
					$p['name'][] = $s['name'];
				}
				unset($p);
				$s['photos'] = $photos;
			}
			unset($s);
		}else{
			$seriesPhotos = array();
		}
		if(isset($_SESSION['offer-photos']) && is_array($_SESSION['offer-photos'])){
			$selectedPhotos = array_map('intval', $_SESSION['offer-photos']);
		}else{
			$selectedPhotos = array();
		}

		// Исходные данные
		$init = array();

		if(isset($_SESSION['cart-options']) && is_array($_SESSION['cart-options'])){
			$init['options'] = $_SESSION['cart-options'];
		}else{
			$init['options'] = Orders_Options::$default;
		}
		self::$options = $init['options'];

		// Стоимость опций (доставка, сборка и проч.)
		$oOptions = new Orders_Options();
		$optionsPrices = $oOptions->calc(self::$cart, $init['options']);

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'pageInf'		=> $pageInf,
			'cart'			=> self::$cart,
			'discounts'		=> $discounts,
			'optionsPrices'	=> $optionsPrices,

			'seriesPhotos'	=> $seriesPhotos,
			'selectedPhotos'=> $selectedPhotos,

			'totalAmount'	=> $totalAmount,
			'totalPrice'	=> $totalPrice,
			'totalPriceOld'	=> $totalPriceOld
		));
		// Выводим форму
		// С формой следующая хитрость:
		// При помощи Form::setInit() заполняются значения формы id="offer-form1"
		// Но эта форма отправляется на адрес Url::a('catalog-cart')
		// А в данном модуле обрабатывается форма id="offer-form3"
		$frm = new Form($formHtml, 'offer-form1', false, false);
		$frm->setInit($init);
		return $frm->run('mOffer::save');
	}


	/** Переход к следующему шагу оформления заказа
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$oOffers = new Dealers_Offers();
		$oOffers->saveOffer(
			intval($_SESSION['offer-edit']),
			self::$cart,
			self::$options,
			array_map('intval', $newData['photos'])
		);

		Catalog_Cart::clear();
		unset($_SESSION['offer-photos']);
		unset($_SESSION['offer-edit']);

		Pages::flash(
			'Коммерческое предложение оформено.<br>Ниже, в списке ваших КП, Вы можете отердактировать его, скачать (XLS, PDF) или сохранить и отправить администрации сайта.',
			false,
			Url::a('dealer')
		);
	}
}

?>