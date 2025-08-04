<?php

/**
 * ЭТОТ КЛАСС БОЛЬШЕ НЕ ИСПОЛЬЗУЕТСЯ !!!
 * Группы товаров (в пределах серии)
 *
 * @author	Seka
 */

class Catalog_Items_Groups0 extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_items_groups_';


	/** Переопределим метод для присвоения order
	 * @see	DbList::addArr()
	 * @param	array	$data
	 * @param	string	$method
	 * @return	int
	 */
	function addArr($data = array(), $method = self::INSERT){
		$res = parent::addArr($data, $method);
		$this->setOrderValue();
		return $res;
	}


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
				'`group_id` IN (' . implode(',', $ids) . ')',
				array(
					'group_id'	=> 0
				)
			);

			$oSeries = new Catalog_Series();
			$oSeries->updCond(
				'`price_search_group_id` IN (' . implode(',', $ids) . ')',
				array(
					'price_search_group_id'	=> 0
				)
			);
		}

		return $result;
	}
}

?>
