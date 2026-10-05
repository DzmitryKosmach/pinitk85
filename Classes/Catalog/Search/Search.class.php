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
		$words = self::queryWords($text);
		if (!$words) {
			return false;
		}

		$nameLikes = array();
		foreach ($words as $word) {
			$esc = MySQL::mres($word);
			$nameLikes[] = '`name` LIKE \'%' . $esc . '%\'';
		}

		// Два и более слова: все должны быть в названии серии (то, что видно в выдаче).
		// FULLTEXT здесь нельзя: короткие слова MySQL выкидывает, и остаётся совпадение только по одному токену.
		if (count($words) >= 2) {
			return '(' . implode(' AND ', $nameLikes) . ')';
		}

		$word = $words[0];
		$esc = MySQL::mres($word);
		$oWordforms = new Wordforms();
		$base = trim($oWordforms->getBase($word));
		if ($base === '') {
			$base = $word;
		}

		$match = 'MATCH (`keywords`) AGAINST (\'' . MySQL::mres('+' . $base . '*') . '\' IN BOOLEAN MODE)';
		$like = '(`name` LIKE \'%' . $esc . '%\' OR `keywords` LIKE \'%' . $esc . '%\')';

		return '((' . $match . ') OR (' . $like . '))';
	}

	/**
	 * Нормализует запрос в слова без дополнения до ft_min_word_len,
	 * чтобы «пол» могло совпасть с «полозья».
	 *
	 * @param string $text
	 * @return array
	 */
	protected static function queryWords($text)
	{
		$text = trim(mb_strtolower((string)$text));
		if ($text === '') {
			return array();
		}

		$text = str_replace('ё', 'е', $text);
		$text = preg_replace('/[^a-z0-9а-я]/ius', ' ', $text);
		$text = preg_replace('/\s+/u', ' ', trim((string)$text));
		if ($text === '') {
			return array();
		}

		$words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
		$out = array();
		foreach ($words as $word) {
			$word = trim($word);
			if ($word === '' || mb_strlen($word) < 2) {
				continue;
			}
			$out[$word] = $word;
		}

		return array_values($out);
	}
}

?>
