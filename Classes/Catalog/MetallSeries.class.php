<?php

/**
 * Серии металлической мебели
 * Отдельные товары могут относится к одной из этих серий, что повлияет на стоимость их сборки в калькуляторе корзины
 *
 * @author	Seka
 */

class Catalog_MetallSeries extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_metall_series';


	/**
	 * @see	DbList::delCond()
	 * @param string $cond
	 * @return bool
	 */
	function delCond($cond = ''){
		$ids = $this->getCol('id', $cond);
		$result = parent::delCond($cond);

		if(count($ids)){
			// Удаляем зависимые данные
			$oItems = new Catalog_Items();
			$oItems->updCond(
				'`metall_series_id` IN (' . implode(',', $ids) . ')',
				array(
					'metall_series_id'	=> 0
				)
			);
		}

		return $result;
	}
}

?>
