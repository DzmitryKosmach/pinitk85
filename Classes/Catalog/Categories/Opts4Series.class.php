<?php

/**
 * Шаблон перечня названий характеристик для серий в категории
 *
 * @author	Seka
 */

class Catalog_Categories_Opts4Series extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_categories_opts4series';


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
		$options = $this->get('name, category_id', $cond);
		$result = parent::delCond($cond);

		if(count($options)){
			// Удаляем значения опций с совпадающими названияи для серии данной категории
			$optsByCats = array();
			foreach($options as $o){
				if(!isset($optsByCats[$o['category_id']])) $optsByCats[$o['category_id']] = array();
				$optsByCats[$o['category_id']][] = MySQL::mres($o['name']);
			}

			$oSeries = new Catalog_Series();
			$oSeriesOptions = new Catalog_Series_Options();
			foreach($optsByCats as $cId => $opts){
				$sIds = $oSeries->getCol(
					'id',
					'`category_id` = ' . $cId
				);
				if(count($sIds)){
					$oSeriesOptions->delCond(
						'`series_id` IN (' . implode(',', $sIds) . ') AND `name` IN (\'' . implode('\',\'', $opts) . '\')'
					);
				}
			}
		}

		return $result;
	}
}
