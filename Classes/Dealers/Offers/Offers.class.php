<?php

/**
 * Коммерческие предложения диллеров
 *
 * @author	Seka
 */

class Dealers_Offers extends ExtDbList {

	/**
	 * @var string
	 */
	static string $tab = 'dealers_offers';

	/**
	 *
	 */
	const FORMAT_XLS = 'xls';
	const FORMAT_PDF = 'pdf';

	/**
	 *
	 */
	const PATH_XLS = '/Uploads/OffersXLS/';
	const PATH_PDF = '/Uploads/OffersPDF/';

	/**
	 *
	 */
	const SESS_KEY_DISCOUNTS = 'dealer-discounts';


	/**
	 * @param	int		$offerId
	 * @param	array	$cart
	 * @param	array	$options
	 * @param	array	$photos
	 * @return	int
	 */
	function saveOffer($offerId, $cart, $options, $photos){
		$offerId = intval($offerId);

		// Дилер
		if(!Dealers_Security::isAuthorized()) return 0;
		$dealer = Dealers_Security::getCurrent();

		// Скидки, установленные вручную
		$discounts = self::discounts($cart);

		// Сумма заказа
		$totalPriceIn = 0;
		$totalPriceOut = 0;
		$totalAmount = 0;
		foreach($cart as $c){
			$totalPriceIn += $c['price-in'] * $c['amount'];
			$totalPriceOut += $c['price'] * $c['amount'];
			$totalAmount += $c['amount'];
		}

		$oOptions = new Orders_Options();
		$optionsPrices = $oOptions->calc($cart, $options);
		$priceOptions = 0;
		foreach($optionsPrices as $op){
			$priceOptions += $op['price'];
		}

		//
		$photos = array_map('intval', $photos);

		$offer = array(
			'date'		=> MySQL::dateTime(),
			'dealer_id'	=> $dealer['id'],
			'cart'		=> serialize($cart),
			'discounts'	=> serialize($discounts),
			'price_in'	=> $totalPriceIn,
			'price_out'	=> $totalPriceOut,
			'amount'	=> $totalAmount,
			'options'		=> serialize($optionsPrices),
			'options_form'	=> serialize($options),
			'price_options'	=> $priceOptions,
			'photos'	=> serialize($photos)
		);
		if($offerId){
			$this->upd($offerId, $offer);
			$this->delFiles($offerId);
		}else{
			$offerId = $this->add($offer);
		}

		return $offerId;
	}


	/**
	 * @param	int		$offerId
	 * @param	string	$format
	 * @return	null
	 */
	function download($offerId, $format){
		$offerId = intval($offerId);
		$format = trim($format);
		if($format !== self::FORMAT_XLS && $format !== self::FORMAT_PDF){
			return;
		}
		if(!$this->getCount('`id` = ' . $offerId)){
			return;
		}

		if($format === self::FORMAT_XLS){
			$this->downloadXLS($offerId);

		}else/*if($format === self::FORMAT_PDF)*/{
			$this->downloadPDF($offerId);
		}
	}


	/**
	 * @param	int	$offerId
	 */
	protected function downloadXLS($offerId){
		$offerId = intval($offerId);

		$file = _ROOT . self::PATH_XLS . $offerId . '.xls';
		if(!is_file($file)){
			$offer = $this->getRow('*', '`id` = ' . $offerId);
			if(!$offer) return;
			Dealers_Offers_XLS::make($offer, $file);
		}

		$date = $this->getCell('date', '`id` = ' . $offerId);

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="offer-' . date('Y-m-d-H-i-s', MySQL::fromDateTime($date)) . '.xls"');
		header('Cache-Control: max-age=0');
		readfile($file);
		//unlink($file);
		exit;
	}


	/**
	 * @param	int	$offerId
	 */
	protected function downloadPDF($offerId){
		$offerId = intval($offerId);

		$file = _ROOT . self::PATH_PDF . $offerId . '.pdf';
		if(!is_file($file)){
			$offer = $this->getRow('*', '`id` = ' . $offerId);
			if(!$offer) return;
			Dealers_Offers_PDF::make($offer, $file);
		}

		$date = $this->getCell('date', '`id` = ' . $offerId);

		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment;filename="offer-' . date('Y-m-d-H-i-s', MySQL::fromDateTime($date)) . '.pdf"');
		header('Cache-Control: max-age=0');
		readfile($file);
		//unlink($file);
		exit;
	}


	/**
	 *
	 */
	function mailToAdmin(){
		Email_Tpl::send(
			'offer-admin',
			Options::name('admin-offer-email')
		);
	}


	/**
	 * @see	DbList::delCond()
	 * @param string $cond
	 * @return bool
	 */
	function delCond($cond = ''){
		$ids = $this->getCol('id', $cond);

		$result = parent::delCond($cond);

		foreach($ids as $id){
			$this->delFiles($id);
		}

		return $result;
	}


	/**
	 * @param $offerId
	 */
	function delFiles($offerId){
		$offerId = intval($offerId);

		$file = _ROOT . self::PATH_XLS . $offerId . '.xls';
		if(is_file($file)) unlink($file);

		$file = _ROOT . self::PATH_PDF . $offerId . '.pdf';
		if(is_file($file)) unlink($file);
	}


	/**
	 * Получаем установленные вручную дилером скидки на товары в корзине
	 * @param	array	$cart
	 * @return	array
	 */
	static function discounts($cart = array()){
		if(!is_array($_SESSION[self::SESS_KEY_DISCOUNTS])) $_SESSION[self::SESS_KEY_DISCOUNTS] = array();

		$discounts = array();
		foreach($cart as $c){
			$iId = intval($c['item']['id']);
			$mId = intval($c['material']['id']);
			$discounts[$iId][$mId] =
				isset($_SESSION[self::SESS_KEY_DISCOUNTS][$iId][$mId]) ?
					$_SESSION[self::SESS_KEY_DISCOUNTS][$iId][$mId] :
					abs($c['item']['discount']);
		}

		return $discounts;
	}


	/**
	 * @param	int	$itemId
	 * @param	int	$matId
	 * @return	float|bool
	 */
	static function discount4item($itemId, $matId){
		$itemId = intval($itemId);
		$matId = intval($matId);
		return
			isset($_SESSION[self::SESS_KEY_DISCOUNTS][$itemId][$matId]) ?
				$_SESSION[self::SESS_KEY_DISCOUNTS][$itemId][$matId] :
				false;
	}


	/**
	 * @param array $cart
	 * @param array $discounts
	 */
	static function discountsSet($cart = array(), $discounts = array()){
		self::discountsClear();
		foreach($cart as $c){
			$iId = intval($c['item']['id']);
			$mId = intval($c['material']['id']);
			if(isset($discounts[$iId][$mId])){
				$d = abs($discounts[$iId][$mId]);
				if($d > 100) $d = 100;
				$d = Catalog::percent2num($d, Catalog::PC_DECREASE);
				$_SESSION[self::SESS_KEY_DISCOUNTS][$iId][$mId] = $d;
			}
		}
	}


	/**
	 * @param	int	$itemId
	 * @param	int	$materialId
	 */
	static function discountsRemove($itemId, $materialId = 0){
		$itemId = intval($itemId);
		$materialId = intval($materialId);
		unset($_SESSION[self::SESS_KEY_DISCOUNTS][$itemId][$materialId]);
		if(isset($_SESSION[self::SESS_KEY_DISCOUNTS][$itemId]) && !count($_SESSION[self::SESS_KEY_DISCOUNTS][$itemId])){
			unset($_SESSION[self::SESS_KEY_DISCOUNTS][$itemId]);
		}

	}


	/**
	 *
	 */
	static function discountsClear(){
		$_SESSION[self::SESS_KEY_DISCOUNTS] = array();
	}
}

?>
