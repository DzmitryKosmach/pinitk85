<?php

/**
 * Поиск серий в каталоге
 *
 * @author	Seka
 */

class Catalog_Search {

	/** Формирует часть поискового запроса для аргумента WHERE для поиска серий по текстовому запросу
	 * @static
	 * @param	string	$text
	 * @return	string
	 */
	static function textCond($text){
		$oWordforms = new Wordforms();
		$text = $oWordforms->formatKeywords($text);
		if($text === '') return false;

		return 'MATCH (`keywords`) AGAINST (\'' . MySQL::mres($text) . '\')';
	}
}

?>