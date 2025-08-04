<?php

/**
 * Специальная наценка на отображаемыую входную цену для дилеров
 *
 * @author	Seka
 */

class Dealers_Extra extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'dealers_extra';


	/** Получаем массив наценок для указанного дилера
	 * @static
	 * @param	int	$dealerId
	 * @return	array	хеш (supplier_id => extra)
	 */
	static function getForDealer($dealerId){
		static $o; if(!$o) $o = new self();
		static $sIds;
		if(!$sIds){
			$oSuppliers = new Catalog_Suppliers();
			$sIds = $oSuppliers->getCol('id');
		}

		$dealerId = intval($dealerId);
		$extra = $o->getHash(
			'supplier_id, extra',
			'`dealer_id` = ' . $dealerId
		);

		foreach($sIds as $sId){
			if(!isset($extra[$sId])){
				$extra[$sId] = 1;
			}
		}

		return $extra;
	}
}

?>
