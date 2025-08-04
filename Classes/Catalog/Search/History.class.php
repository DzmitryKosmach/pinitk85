<?php

/**
 * История поисковых запросов в каталог
 *
 * @author	Seka
 */

class Catalog_Search_History extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_search_history';

	/**
	 * @var int
	 */
	static $timeout = 2592000;	// 86400 * 30


	/** Логирование поискового запроса
	 * @param	string	$text
	 */
	function log($text){
		$text = self::simplify($text);
		if($text === '') return;

		$log = $this->getRow('id, frequency', '`text` = \'' . MySQL::mres($text) . '\'');
		if($log){
			$this->upd(
				$log['id'],
				array(
					'date'		=> MySQL::date(),
					'frequency'	=> $log['frequency'] + 1
				)
			);
		}else{
			$this->add(array(
				'text'		=> $text,
				'date'		=> MySQL::date(),
				'frequency'	=> 1
			));
		}
	}


	/** Получаем популярные поисковые запросы
	 * @param	string	$text
	 * @param	int		$limit
	 * @return	array
	 */
	function getPopular($text, $limit = 10){
		$text = self::simplify($text);
		if($text === '') array();

		$limit = intval($limit);
		return $this->getCol(
			'text',
			'`text` LIKE \'' . MySQL::mres($text) . '%\'',
			'`frequency` DESC',
			$limit
		);
	}


	/** Упрощение поискового запроса перед записью в логи
	 * @static
	 * @param	string	$text
	 * @return	string
	 */
	static function simplify($text){
		$text = mb_strtolower($text);
		$text = preg_replace('/[[:space:]]/us', ' ', $text);
		$text = preg_replace('/ {2,}/us', ' ', $text);
		return trim($text);
	}


	/**
	 * Очистка устаревших логов
	 */
	function clear(){
		$this->delCond('`date` <= \'' . MySQL::date(time() - self::$timeout) . '\'');
	}
}

?>
