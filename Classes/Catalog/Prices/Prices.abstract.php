<?php

/**
 * Базовый абстрактный метод для импорта/экспорта прайсов
 * Содержит констранты и конфиг
 *
 * @author	Seka
 */

abstract class Catalog_Prices {

	const FLD_SERIES_ID = 'id';
	const FLD_SERIES_CATEGORY = 'category';
	const FLD_SERIES_SUPPLIER = 'supplier';		//
	const FLD_SERIES_NAME = 'name';				//
	const FLD_SERIES_EXTRA = 'extra';			//
	const FLD_SERIES_DISCOUNT = 'discount';
	const FLD_SERIES_CHARACTERS = 'characters';	//
	const FLD_SERIES_TITLE = 'title';			//
	const FLD_SERIES_HEADER = 'h1';			//
	const FLD_SERIES_DSCR = 'dscr';				//
	const FLD_SERIES_KWRD = 'kwrd';				//

	const FLD_ITEMS_ID = 'id';
	const FLD_ITEMS_GROUP = 'group';
	const FLD_ITEMS_NAME = 'name';
	const FLD_ITEMS_ART = 'art';
	const FLD_ITEMS_SIZE = 'size';
	const FLD_ITEMS_VOLUME = 'volume';
	const FLD_ITEMS_WEIGHT = 'weight';
	const FLD_ITEMS_DESCRIPTION = 'text';
	const FLD_ITEMS_PRICES = 'prices';
	const FLD_ITEMS_DISCOUNT = 'discount';

	/**
	 * @var array
	 */
	protected static $fldsSeries = array(
		self::FLD_SERIES_ID			=> 'ID',
		self::FLD_SERIES_CATEGORY	=> 'Категория',
		self::FLD_SERIES_SUPPLIER	=> 'Поставщик',
		self::FLD_SERIES_NAME		=> 'Название серии',
		self::FLD_SERIES_EXTRA		=> 'Наценка (%)',
		self::FLD_SERIES_DISCOUNT	=> 'Скидка (%)',
		self::FLD_SERIES_TITLE		=> 'Тайтл страницы',
		self::FLD_SERIES_HEADER		=> 'H1 страницы',
		self::FLD_SERIES_DSCR		=> 'Мета-тэг describtion',
		self::FLD_SERIES_KWRD		=> 'Мета-тэг keywords'
	);

	/**
	 * @var array
	 */
	protected static $fldsItems = array(
		self::FLD_ITEMS_ID			=> 'ID',
		self::FLD_ITEMS_GROUP		=> 'Группа',
		self::FLD_ITEMS_NAME		=> 'Наименование',
		self::FLD_ITEMS_ART			=> 'Артикул',
		self::FLD_ITEMS_SIZE		=> 'Размер',
		self::FLD_ITEMS_VOLUME		=> 'Объём',
		self::FLD_ITEMS_WEIGHT		=> 'Масса',
		self::FLD_ITEMS_DESCRIPTION	=> 'Описание',
		self::FLD_ITEMS_PRICES		=> 'Наценка (%)',	// Ценовые колонки строятся отдельно; здесь имеется ввиду, что, если при экспорте выбрана опция self::FLD_ITEMS_PRICES, то в этом месте идёт столбец "Наценка"
		self::FLD_ITEMS_DISCOUNT	=> 'Скидка (%)'
	);

	/**
	 * @var array
	 */
	protected static $xlsColChars = array(
		'A',  'B',  'C',  'D',  'E',  'F',  'G',  'H',  'I',  'J',  'K',  'L',  'M',  'N',  'O',  'P',  'Q',  'R',  'S',  'T',  'U',  'V',  'W',  'X',  'Y',  'Z',
		'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
		'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
		'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ'
	);


	/** Возвращает букву столбца по его номеру (нумерация с нуля)
	 * @static
	 * @param	int	$num
	 * @return	string
	 */
	protected static function colN2C($num){
		return isset(self::$xlsColChars[$num]) ? self::$xlsColChars[$num] : '?';
	}


	/** Возвращает номер столбца по его букве (нумерация с нуля)
	 * CN - сокращение от Column Number
	 * @param	string	$char
	 * @return	int
	 */
	protected static function colC2N($char){
		$num = array_search($char, self::$xlsColChars);
		return $num ? $num : 0;
	}
}

?>