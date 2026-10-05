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

	/** Возвращает букву столбца по его номеру (нумерация с нуля)
	 * @static
	 * @param	int	$num
	 * @return	string
	 */
	protected static function colN2C($num){
		$num = (int)$num;
		if ($num < 0) {
			$num = 0;
		}
		if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate')) {
			return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($num + 1);
		}
		$letters = '';
		$n = $num + 1;
		while ($n > 0) {
			$n--;
			$letters = chr(65 + ($n % 26)) . $letters;
			$n = intdiv($n, 26);
		}
		return $letters;
	}


	/** Возвращает номер столбца по его букве (нумерация с нуля)
	 * CN - сокращение от Column Number
	 * @param	string	$char
	 * @return	int
	 */
	protected static function colC2N($char){
		$char = strtoupper((string)$char);
		if ($char === '') {
			return 0;
		}
		if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate')) {
			return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($char) - 1;
		}
		$num = 0;
		$len = strlen($char);
		for ($i = 0; $i < $len; $i++) {
			$num = $num * 26 + (ord($char[$i]) - 64);
		}
		return max(0, $num - 1);
	}
}

?>