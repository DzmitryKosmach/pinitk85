<?php

/**
 * недавно просмотренные серии
 *
 * @author	Seka
 */

class Catalog_Recently {

	/**
	 * Массив серий хранится в сессии:
	 * $_SESSION[self::SESS_KEY][$seriesId] = 1
	 */
	const SESS_KEY = 'recently';


	/**
	 * @static
	 * @param	int	$seriesId
	 */
	static function add($seriesId){
		$seriesId = intval($seriesId);

		if(!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		if(isset($_SESSION[self::SESS_KEY][$seriesId])) return;

		$oSeries = new Catalog_Series();
		if($oSeries->getCell('id', '`id` = ' . $seriesId)){
			$_SESSION[self::SESS_KEY][$seriesId] = 1;
		}
	}


	/**
	 * @static
	 * @param	int	$seriesId
	 */
	static function remove($seriesId){
		$seriesId = intval($seriesId);
		unset($_SESSION[self::SESS_KEY][$seriesId]);
	}


	/**
	 * @static
	 * @return	array
	 */
	static function get(){
		if(!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		return array_keys($_SESSION[self::SESS_KEY]);
	}


	/**
	 * @static
	 * @return int
	 */
	static function count(){
		if(!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		return count($_SESSION[self::SESS_KEY]);
	}
}

?>