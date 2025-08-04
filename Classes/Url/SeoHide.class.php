<?php

/**
 *
 * @author	Seka
 */

class Url_SeoHide {

	/**
	 * @param	string	$url
	 * @return	string
	 */
	static function encode($url){
		static $shift = 15;
		static $chars = array(
			'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9',':','/','-','_','.','?'
		);
		static $charsCnt; if(!$charsCnt) $charsCnt = count($chars);

		$url = mb_strtolower(trim($url));
		$l = mb_strlen($url);
		$code = '';
		for($i = 0; $i < $l; $i++){
			$ch = mb_substr($url, $i, 1);
			$n = array_search($ch, $chars);
			if($n !== false){
				$n += $shift;
				if($n >= $charsCnt) $n -= $charsCnt;
				$ch = $chars[$n];
			}
			$code .= $ch;
		}
		return $code;
	}


	/**
	 * @param	string	$html
	 * @return	string
	 */
	static function parse($html){
		// Подключаем phpQuery
		include_once(Config::path('external') . '/phpQuery/phpQuery.php');

		$html = self::javaScriptRemove($html);

		$htmlDOM = phpQuery::newDocument($html)->get(0);
		foreach(pq($htmlDOM)->find('a') as $elDOM){
			$url = pq($elDOM)->attr('href');
			pq($elDOM)->attr('href', '#');
			pq($elDOM)->attr('data-shcode', self::encode($url));
		}

		return self::javaScriptRestore((string)pq($htmlDOM));
	}


	/** Временное хранилище для блоков JS-кода (перед анализом формы они вырезаются, а в конце вставляются на места)
	 * @var array
	 */
	protected static $JSblocks = array();


	/** Удаляет JS из кода и запоминает, что и откуда было удалено
	 * @param	string	$html
	 * @return	string
	 */
	protected static function javaScriptRemove($html){
		self::$JSblocks = array();
		$htmlOrig       = $html;

		// Ищем скрипты
		$jsNum = 0;
		$html  = strtolower($htmlOrig);
		$start = strpos($html, '<script');
		$end   = false;
		if($start !== false) $end = strpos($html, 'script>', $start + strlen('>script'));
		while($start !== false && $end !== false){
			$JS                     = substr($htmlOrig, $start, $end - $start + strlen('script>'));
			self::$JSblocks[$jsNum] = $JS;

			$htmlOrig = str_replace($JS, '[[[javascript-block-' . $jsNum . ']]]', $htmlOrig);

			$jsNum++;
			$html  = strtolower($htmlOrig);
			$start = strpos($html, '<script');
			if($start !== false) $end = strpos($html, 'script>', $start + strlen('>script'));
		}

		return $htmlOrig;
	}



	/** Вставляет JS обратно в код
	 * @param	string	$html
	 * @return	string
	 */
	protected static function javaScriptRestore($html){
		foreach(self::$JSblocks as $jsNum => $JS)
			$html = str_replace('[[[javascript-block-' . $jsNum . ']]]', $JS, $html);

		self::$JSblocks = array();
		return $html;
	}
}

?>