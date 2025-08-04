<?php

/** Админка: КП дилеров
 * @author    Seka
 */

class mDealersOffers extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::DEALERS;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_DEALERS;

	/**
	 * @var string
	 */
	var $mainClass = 'Dealers_Offers';


	/**
	 * К-во записей на стр.
	 */
	const ON_PAGE = 50;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()) {
		$o = new self(); $o->getOperations();

		if(isset($_GET['download'])){
			self::download(
				intval($_GET['id']),
				$_GET['download']
			);
		}

		$oDealers = new Dealers();
		$oOffers = new Dealers_Offers();

		$dealerId = intval($_GET['dealer']);
		if($dealerId){
			$dealer = $oDealers->getRow('*', '`id` = ' . $dealerId);
			if(!$dealer){
				$dealerId = 0;
			}
		}else{
			$dealer = false;
		}

		// Получаем список
		list($offers, $toggle) = $oOffers->getByPage(
			intval($_GET['page']),
			self::ON_PAGE,
			'*',
			'`saved` = 1' . ($dealerId ? ' AND `dealer_id` = ' . $dealerId : ''),
			'`date` DESC'
		);
		foreach($offers as &$o){
			$o['cart'] = unserialize($o['cart']);
			$o['discounts'] = unserialize($o['discounts']);
			$o['options'] = unserialize($o['options']);
		}
		unset($o);

		// Получаем имена дилеров
		$dIds = array();
		foreach($offers as $o) $dIds[] = $o['dealer_id'];
		if(count($dIds)){
			$dealersNames = $oDealers->getHash('id, name', '`id` IN (' . implode(',', $dIds) . ')');
			foreach($offers as &$o){
				$o['dealer_name'] = isset($dealersNames[$o['dealer_id']]) ? $dealersNames[$o['dealer_id']] : '-unknown-';
			}
			unset($o);
		}

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'dealer'	=> $dealer,
			'offers'	=> $offers,
			'toggle'	=> $toggle
		));
	}


	/** Удаление
	 * @param $iId
	 */
	function delItem($iId){
		$oOffers = new Dealers_Offers();
		$oOffers->del(intval($iId));

		Pages::flash('Коммерческое предложение успешно удалено.');
	}


	/**
	 * @static
	 * @param $offerId
	 * @param $format
	 * @return mixed
	 */
	static function download($offerId, $format){
		$offerId = intval($offerId);
		$format = trim($format);
		if($format !== Dealers_Offers::FORMAT_XLS && $format !== Dealers_Offers::FORMAT_PDF){
			return;
		}

		$oOffers = new Dealers_Offers();
		if(!$oOffers->getCount('`id` = ' . $offerId)){
			return;
		}

		$oOffers->download($offerId, $format);
		exit;
	}
}

?>