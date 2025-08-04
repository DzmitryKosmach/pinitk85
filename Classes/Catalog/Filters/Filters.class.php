<?php

/**
 * Фильтры для поиска
 *
 * @author	Seka
 */

class Catalog_Filters extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_filters';

	/**
	 * Как отобразить фильтр
	 */
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_RADIO = 'radio';


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
			$oFiltersValues = new Catalog_Filters_Values();
			$oFiltersValues->delCond('`filter_id` IN (' . implode(',', $ids) . ')');

			$oCategories2Filters = new Catalog_Categories_2Filters();
			$oCategories2Filters->delCond('`filter_id` IN (' . implode(',', $ids) . ')');
		}

		return $result;
	}


	/** Поиск серий по комбинации фильтров
	 * @param	array	$filter2Values		(id_фильтра => (id_значения, id_значения, id_значения, ...))
	 * @return	array|bool	Массив ID серий или true (что означет, что подходят все серии)
	 */
	function searchSeries($filter2Values){
		if(!is_array($filter2Values)){
			return true;
		}
		foreach($filter2Values as $fId => &$values){
			if(!is_array($values)){
				$values = array($values);
			}
			$values = array_map('intval', array_unique($values));
			foreach($values as $n => $v){
				if(!$v) unset($values[$n]);
			}
			if(!count($values)){
				unset($filter2Values[$fId]);
			}
		}
		unset($values);

		if(!count($filter2Values)){
			return true;
		}

		$oSeries2Filters = new Catalog_Series_2Filters();
		$sIds = false;
		foreach($filter2Values as $fId => $values){
			$tmp = $oSeries2Filters->getCol(
				'series_id',
				'`value_id` IN (' . implode(',', $values) . ')',
				'', 0, '',
				'series_id'
			);
			if($sIds === false){
				$sIds = $tmp;
			}else{
				$sIds = array_intersect($sIds, $tmp);
			}
		}
		return $sIds;
	}
}

?>
