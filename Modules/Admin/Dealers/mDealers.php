<?php

/** Админка: дилеры
 * @author    Seka
 */

class mDealers extends Admin {

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
	var $mainClass = 'Dealers';

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

		// Получаем список
		$oDealers = new Dealers();
		list($dealers, $toggle) = $oDealers->getByPage(
			intval($_GET['page']),
			self::ON_PAGE,
			'*',
			'',
			'`regdate` ASC'
		);

		// Получаем к-во КП по каждому дилеру
		$dIds = array();
		foreach($dealers as $d) $dIds[] = $d['id'];
		if(count($dIds)){
			$oOffers = new Dealers_Offers();
			$offersCnt = $oOffers->getHash(
				'dealer_id, COUNT(*)',
				'`dealer_id` IN (' . implode(',', $dIds) . ') AND `saved` = 1',
				'',
				0,
				'',
				'dealer_id'
			);
			foreach($dealers as &$d){
				$d['offers'] = isset($offersCnt[$d['id']]) ? intval($offersCnt[$d['id']]) : 0;
			}
			unset($d);
		}

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'dealers'	=> $dealers,
			'toggle'	=> $toggle
		));
	}


	/** Удаление
	 * @param $iId
	 */
	function delItem($iId){
		$oDealers = new Dealers();
		$oDealers->del(intval($iId));

		Pages::flash('Дилер успешно удалён.');
	}
}

?>