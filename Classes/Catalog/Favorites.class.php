<?php

/**
 * Список "Мне понравилось" для серий
 *
 * @author	Seka
 */

class Catalog_Favorites
{

	/**
	 * Массив серий хранится в сессии:
	 * $_SESSION[self::SESS_KEY][$seriesId] = 1
	 */
	const SESS_KEY = 'favorites';


	/**
	 * @static
	 * @param	int	$seriesId
	 */
	static function add($seriesId)
	{
		$seriesId = intval($seriesId);

		if (!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		if (isset($_SESSION[self::SESS_KEY][$seriesId])) return;

		$oSeries = new Catalog_Series();
		if ($oSeries->getCell('id', '`id` = ' . $seriesId)) {
			$_SESSION[self::SESS_KEY][$seriesId] = 1;
		}
	}


	/**
	 * @static
	 * @param	int	$seriesId
	 */
	static function remove($seriesId)
	{
		$seriesId = intval($seriesId);
		unset($_SESSION[self::SESS_KEY][$seriesId]);
	}


	/**
	 * @static
	 * @return	array	($series, $matsBySeries, $options, $materials)
	 * $series:			Готовый к отображениюб массив секрий
	 * $matsBySeries:	array(seriesId => (matId, matId...), ...)
	 * $options:		array(optName => array(seriesId => optValue, seriesId => optValue, ...), ...)
	 * $materials:		материалы всех серий в списке, в виде дерева
	 * @see	Catalog_Materials::getTree()
	 */
	static function get()
	{
		if (!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		$sIds = array_keys($_SESSION[self::SESS_KEY]);

		if (count($sIds)) {
			$sIds = array_map('intval', $sIds);

			// Получаем серии
			$oSeries = new Catalog_Series();
			$series = $oSeries->get(
				'*',
				'`id` IN (' . implode(',', $sIds) . ')',
				'order'
			);
			$series = $oSeries->details($series);

			// Опции серий
			$oSeriesOptions = new Catalog_Series_Options();
			$tmp = $oSeriesOptions->get(
				'series_id, name, value',
				'`series_id` IN (' . implode(',', $sIds) . ')',
				'order'
			);
			$options = array();
			foreach ($tmp as $o) {
				$options[$o['name']][$o['series_id']] = $o['value'];
			}
			foreach ($options as &$series2val) {
				foreach ($series as $s) {
					if (!isset($series2val[$s['id']])) {
						$series2val[$s['id']] = '-';
					}
				}
			}
			unset($series2val);

			// Материалы серий
			$oSeries2Materials = new Catalog_Series_2Materials();
			$tmp = $oSeries2Materials->get(
				'series_id, material_id',
				'`series_id` IN (' . implode(',', $sIds) . ')'
			);
			$matsBySeries = array();
			foreach ($tmp as $m) {
				$matsBySeries[$m['series_id']][] = $m['material_id'];
			}
			foreach ($series as $s) {
				if (!isset($matsBySeries[$s['id']])) {
					$matsBySeries[$s['id']] = array();
				}
			}

			$oMaterials = new Catalog_Materials();
			$materials = $oMaterials->getTree($sIds);
		} else {
			$series = array();
			$matsBySeries = array();
			$options = array();
			$materials = array();
		}

		return array($series, $matsBySeries, $options, $materials);
	}


	/**
	 * @static
	 * @param	int	$seriesId
	 * @return	bool
	 */
	static function check($seriesId)
	{
		$seriesId = intval($seriesId);
		return isset($_SESSION[self::SESS_KEY][$seriesId]);
	}


	/**
	 * @static
	 * @return int
	 */
	static function count()
	{
		if (!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		return count($_SESSION[self::SESS_KEY]);
	}
}
