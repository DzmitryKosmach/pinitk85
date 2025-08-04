<?php

/**
 * Прямая связь между сериями и теговыми страницами категорий
 * Обычно они связываются через значения фильтров, но можно сделать это напрямую, поставив галочку в свойствах серии
 * @see	Catalog_Pages
 *
 * @author	Seka
 */

class Catalog_Series_2Pages extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_series2pages';
}
