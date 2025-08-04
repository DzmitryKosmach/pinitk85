<?php

/**
 * Минимизация JS
 * В JsCopmress::$modules описаны javascript модули, состоящие из нескольких файлов
 * Методот JsCopmress::load() возвращает html-код с тегами <script src="..."></script> для вставки всех файлов модуля в страницу.
 * При этом:
 * Если Config::$debug == true, то в возвращаемом коде все js-файлы подключаются по-отдельности (много тегов <script></script>)
 * Если Config::$debug == false, то в код вставляется единственный файл ...min.js, который при необходимости на лету генерируется из всех файлов модуля
 *
 * @author	Seka
 */

class JsCopmress {

	/**
	 *
	 */
	const V = '?7';

	/** JS-модули и файлы, в них входящие
	 * @var array
	 */
	static $modules = array(
		'core'	=> array(
			'/Js/core/all-core.js',
            '/Js/core/all-core1.min.js',
	/*		'/Js/core/devfunc.js',
			'/Js/core/core.js',
			'/Js/core/core_ajax.js',
			'/Js/core/core_popup.js'		*/
		),
		'core1'	=> array(
		    '/Js/core1/all-core1.min.js'
/*			'/Js/core1/jquery-1.11.0.min.js',
			'/Js/core1/jquery.mousewheel.min.js',
			'/Js/core1/jquery.cookie.js',
			'/Js/core1/jquery.fancybox.pack.js',
			'/Js/core1/jquery.scrollTo.min.js',
			'/Js/core1/stuff.js',
			'/Js/core1/popup.js',
			'/Js/core1/jquery.maskedinput.min.js',
			'/Js/core1/menu-catalog.js',
			'/Js/core1/search.title.js',
			'/Js/core1/search.title-inheader.js',
			'/Js/core1/easycart.js'  */
		),
		'index'	=> array(
			'/Js/index/jssor.core.js',
			'/Js/index/jssor.utils.js',
			'/Js/index/jssor.slider.js'
		),
		'news'	=> array(
			'/Js/news/news.list-banners.js',

		),
		'catalog'	=> array(
			'/Js/catalog/offers.js',
			'/Js/catalog/catalog.section.js',
			'/Js/catalog/catalog.section.list.js',
			'/Js/catalog/series/jquery.jscrollpane.min.js',
			'/Js/catalog/series/jquery.jscrollpane.ext.js'

		),
		'category'	=> array(
			'/Js/catalog/category/catalog.smart.filter.js',
			'/Js/catalog/category/catalog.smart.filter.slider.color.js',
			'/Js/catalog/category/catalog.smart.filter.slider.core.js',
			'/Js/catalog/category/catalog.smart.filter.slider.widget.js',
			'/Js/catalog/category/catalog.smart.filter.slider.mouse.js',
			'/Js/catalog/category/catalog.smart.filter.slider.slider.js',
			'/Js/catalog/category/catalog.sorter.js',

		),
		'series'	=> array(
			'/Js/catalog/series/glass.js',
			'/Js/catalog/series/catalog.element.js',
			'/Js/catalog/series/reviews.js'
		),



		'main'	=> array(
			'/Js/all-main.js',
			//'/Js/prefixfree.min.js',
	/*		'/Js/main.js',    */
			//'/Js/check-cookie.js',
	/*		'/Js/visual.js',
			'/Js/catalog.js',
			'/Js/materials.js',
			'/Js/catalog/compare.js',
			'/Js/search.js',
			'/Js/cart.js',
			'/Js/gototop.js',  */
			//'/Js/HighSlide/highslide-full.js',
			//'/Js/seo-hide.js'
		),
		'main-async'	=> array(
			//'/Js/fix-sizes.js',
			//'/Js/callback.js',
			'/Js/popup.js',
			//'/Js/index-slider.js',
			'/Js/items-scroll.js',
			//'/Js/ya-share.js',
			//'/Js/skype-uri.js',
			//'/Js/hs-main.js'
		)
	);

	/**
	 * Папка для ...min.js
	 */
	const JS_PATH = '/Js/min/';

	/**
	 * Параметры запроса к сервису минификации
	 */
	const MIN_URL = 'http://closure-compiler.appspot.com/compile';
	const MIN_POST = 'js_code=%code%&compilation_level=SIMPLE_OPTIMIZATIONS&output_format=text&output_info=compiled_code';


	/** Генерация html-кода для вставки скриптов модуля в страницу
	 * @static
	 * @param	string	$module
	 * @param	bool	$async
	 * @return	string
	 */
	static function load($module, $async = false){
		if(!isset(self::$modules[$module])){
			return '';
		}

		if(Config::$debug){
			$html = '';
			foreach(self::$modules[$module] as $file){
				$html .= '<script ' . ($async ? 'async' : '') . ' src="' . $file . self::V . '"></script>';
			}

		}else{
			$minFile = self::minFileName($module);
			$html = '<script ' . ($async ? 'async' : '') . ' src="' . $minFile . self::V . '"></script>';

			if(!is_file(_ROOT . $minFile)){
				self::makeMin($module);
			}
		}

		return $html;
	}



	/** Получаем имя .min.js файла для модуля
	 * @static
	 * @param	string	$module
	 * @return	string
	 */
	static function minFileName($module){
		return self::JS_PATH . $module . '.min.js';
	}





	/** Формирование ...min.js файла
	 * @static
	 * @param	string	$module
	 */
	protected static function makeMin($module){
		if(!isset(self::$modules[$module])) return;

		$moduleJS = '';
		foreach(self::$modules[$module] as $file){
			$moduleJS .= fgc(_ROOT . $file) . "\n\n";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::MIN_URL);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			str_replace(
				'%code%',
				urlencode($moduleJS),
				self::MIN_POST
			)
		);
		$minCode = curl_exec($ch);
		curl_close($ch);

		file_put_contents(
			_ROOT . self::minFileName($module),
			$minCode
		);
	}
}
