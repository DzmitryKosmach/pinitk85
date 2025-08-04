<?php

/**
 * Варианты значений для поисковых фильтров
 *
 * @author	Seka
 */

class Catalog_Filters_Values extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_filters_values';


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
			$oSeries2Filters = new Catalog_Series_2Filters();
			$oSeries2Filters->delCond('`value_id` IN (' . implode(',', $ids) . ')');
		}

		return $result;
	}
}

?>
