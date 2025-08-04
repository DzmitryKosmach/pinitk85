<?php

/** Кабинет дилера
 *
 * @author	Seka
 */

class mAccount {

	/**
	 * @var bool
	 */
	static $dealerSecure = true;

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
    static function main($pageInf = array()){
		if(intval($_GET['del'])){
			self::del(intval($_GET['del']));
		}
		if(isset($_GET['download'])){
			self::download(
				intval($_GET['id']),
				$_GET['download']
			);
		}
		if(intval($_GET['edit'])){
			self::edit(intval($_GET['edit']));
		}
		if(intval($_GET['save'])){
			self::save(intval($_GET['save']));
		}

		// Инфа о дилере
		$dealer = Dealers_Security::getCurrent();

		// КП дилера
		$oOffers = new Dealers_Offers();
		$offers = $oOffers->get(
			'*',
			'`dealer_id` = ' . $dealer['id'],
			'`date` DESC'
		);

    	// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf,
			'dealer'	=> $dealer,
			'offers'	=> $offers
		));
    }


	/**
	 * @static
	 * @param 	int		$offerId
	 * @param	string	$format
	 */
	static function download($offerId, $format){
		$offerId = intval($offerId);
		$format = trim($format);
		if($format !== Dealers_Offers::FORMAT_XLS && $format !== Dealers_Offers::FORMAT_PDF){
			return;
		}

		$dealer = Dealers_Security::getCurrent();
		$oOffers = new Dealers_Offers();
		if(!$oOffers->getCount('`id` = ' . $offerId . ' AND `dealer_id` = ' . $dealer['id'])){
			return;
		}

		$oOffers->download($offerId, $format);
		exit;
	}


	/**
	 * @static
	 * @param 	int		$offerId
	 */
	static function del($offerId){
		$offerId = intval($offerId);

		$dealer = Dealers_Security::getCurrent();
		$oOffers = new Dealers_Offers();
		if(!$oOffers->getCount('`id` = ' . $offerId . ' AND `dealer_id` = ' . $dealer['id'])){
			return;
		}

		$oOffers->del($offerId);
		Pages::flash('Коммерческое предложение удалено.');
	}


	/**
	 * @static
	 * @param	int	$offerId
	 */
	static function edit($offerId){
		$offerId = intval($offerId);

		$dealer = Dealers_Security::getCurrent();
		$oOffers = new Dealers_Offers();
		$offer = $oOffers->getRow('*', '`id` = ' . $offerId . ' AND `dealer_id` = ' . $dealer['id'] . ' AND `saved` = 0');
		if(!$offer){
			return;
		}

		$cart = array();
		$offerCart = unserialize($offer['cart']);
		foreach($offerCart as $c){
			$iId = intval($c['item']['id']);
			$mId = intval($c['material']['id']);
			if(!is_array($cart[$iId])) $cart[$iId] = array();
			$cart[$iId][$mId] = $c['amount'];
		}

		$discounts = unserialize($offer['discounts']); if(!is_array($discounts)) $discounts = array();
		$photos = unserialize($offer['photos']); if(!is_array($photos)) $photos = array();

		$options = unserialize($offer['options_form']); if(!is_array($options)) $options = array();


		$_SESSION[Catalog_Cart::SESS_KEY] = $cart;
		$_SESSION[Dealers_Offers::SESS_KEY_DISCOUNTS] = $discounts;
		$_SESSION['cart-options'] = $options;
		$_SESSION['offer-photos'] = $photos;
		$_SESSION['offer-edit'] = $offerId;

		header('Location: ' . Url::a('catalog-cart'));
		exit;
	}


	/**
	 * @static
	 * @param	int	$offerId
	 */
	static function save($offerId){
		$offerId = intval($offerId);

		$dealer = Dealers_Security::getCurrent();
		$oOffers = new Dealers_Offers();
		if(!$oOffers->getCount('`id` = ' . $offerId . ' AND `dealer_id` = ' . $dealer['id'] . ' AND `saved` = 0')){
			return;
		}

		$oOffers->upd(
			$offerId,
			array(
				'saved'	=> 1
			)
		);
		$oOffers->mailToAdmin();

		Pages::flash('Коммерческое предложение сохранено и отправлено администратору сайта.');
	}
}

?>