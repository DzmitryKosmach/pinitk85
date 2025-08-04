<?php

/**
 * Класс для определения браузера
 *
 * @author	Seka
 */

class Browsers {

	/**
	 * @return array	($browser, $version)
	 */
	function detect(){
		include_once(Config::path('external') . '/UASparser.php');

		$useragent = $_SERVER['HTTP_USER_AGENT'];

		$oUASparser = new UASparser();
		$oUASparser->SetCacheDir(Config::path('temp'));
		$result = $oUASparser->parse($useragent);

		$b = $result['ua_family'];
		$v = $result['ua_version'];

		// Если браузер на основе Chrome (Chromium), нам нужно вернуть "Chrome" и его версию
		if(mb_strpos($useragent, 'Chrome') !== false){
			$b = 'Chrome';

			preg_match('/Chrome\/([0-9]+\.[0-9]+)/ius', $useragent, $m);
			$v = $m[1];
		}

		// Оставляем в номере версии только два числа (xx.xxx)
		$v = explode('.', $v);
		$v = $v[0] . (count($v) > 1 ? '.' . $v[1] : '');

		return array(
			$b,
			$v
		);
	}
}

?>