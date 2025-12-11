<?php

/**
 * Минимизация CSS
 * В CssCopmress::$modules описаны CSS-группы из нескольких файлов
 * Методот CssCopmress::load() возвращает html-код с тегами <link href="..." rel="stylesheet"> для вставки всех файлов группы в страницу.
 * При этом:
 * Если Config::$debug == true, то в возвращаемом коде все CSS-файлы подключаются по-отдельности
 * Если Config::$debug == false, то в код вставляется единственный файл ...min.css, который при необходимости на лету генерируется из всех файлов группы
 *
 * @author	Seka
 */

class CssCopmress {

	/**
	 *
	 */
	const V = '?6';

	/** CSS-группы файлов
	 * @var array
	 */
	static $modules = array(
		'core'		=> array(
			/* отключено: legacy стили ядра */
		),
		'news'		=> array(
			'/Skins/css/news/news-list.css',
			'/Skins/css/news/news-list-banners.css',
			'/Skins/css/news/news-detail.css',
			'/Skins/css/news/news-line.css'
		),
		'catalog'	=> array(
			'/Skins/css/catalog/catalog.css',
			'/Skins/css/catalog/catalog-section.css',
			'/Skins/css/catalog/catalog-section-list.css'
			/* отключено: catalog-section-list2.css, offers.css, js-jquery.jscrollpane.css */
		),
		'category'	=> array(
			'/Skins/css/catalog/category/catalog-smart-filter.css',
			'/Skins/css/catalog/category/catalog-smart-filter-slider.css',
			'/Skins/css/catalog/category/catalog-sorter.css'
		),
		'series'	=> array(
			/* отключено: js-glass.css, catalog.element.css */
			'/Skins/css/catalog/series/reviews.css'
		),
		'cart'		=> array(
			'/Skins/css/cart/sale.order.ajax.css'
		)


		/*'default'	=> array(
			'/Skins/css/base.css',
			'/Skins/css/head-floating.css',
			'/Skins/css/catalog.css',
			'/Skins/css/index.css',
			'/Skins/css/cart.css',
			'/Skins/css/media.css',
			'/Js/HighSlide/highslide.css'
		)*/
	);

	/**
	 * Папка для ...min.css
	 */
	const CSS_PATH = '/Skins/css/min/';




	/** Генерация html-кода для вставки CSS-группы в страницу
	 * @static
	 * @param	string	$module
	 * @return	string
	 */
	static function load($module){
		if(!isset(self::$modules[$module]) || !count(self::$modules[$module])){
			return '';
		}

		if(Config::$debug){
			$html = '';
			foreach(self::$modules[$module] as $file){
				$html .= '<link href="' . $file . self::V . '" rel="stylesheet">';
			}

		}else{
			$minFile = self::minFileName($module);
			$html = '<link href="' . $minFile . self::V . '" rel="stylesheet">';

			if(!is_file(_ROOT . $minFile)){
				self::makeMin($module);
			}
		}

		return $html;
	}



	/** Получаем имя .min.css файла для группы
	 * @static
	 * @param	string	$module
	 * @return	string
	 */
	static function minFileName($module){
		return self::CSS_PATH . $module . '.min.css';
	}





	/** Формирование ...min.css файла
	 * @static
	 * @param	string	$module
	 */
	protected static function makeMin($module){
		if(!isset(self::$modules[$module])) return;

		$moduleCSS = '';
		foreach(self::$modules[$module] as $file){
			$moduleCSS .= self::compress(fgc(_ROOT . $file));
		}

		file_put_contents(
			_ROOT . self::minFileName($module),
			$moduleCSS
		);
	}




	/** Сокращаем CSS-код
	 * @static
	 * @param	string	$css
	 * @return	string
	 */
	protected static function compress($css){
		//$css = preg_replace('/\/\*[^\/]*\*\//ius', '', $css);
		$css = preg_replace('/\/\*.*?\*\//ius', '', $css);
		$css = str_replace(array("\n", "\r", "\t"), '', $css);
		$css = preg_replace('/[[:space:]]{2,}/ius', ' ', $css);
		$css = str_replace(array(' {', '{ '), '{', $css);
		$css = str_replace(array(' :', ': '), ':', $css);
		$css = str_replace(array(' ,', ', '), ',', $css);
		$css = str_replace(';}', '}', $css);
		return $css;
	}
}

?>
