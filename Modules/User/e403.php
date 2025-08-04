<?php

/** Ошибка 403
 * @author	Seka
 */

class e403 {


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
    static function main($pageInf = array()){

		header('HTTP/1.0 403', true, 403);

		// Вывод страницы
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf
		));
    }
}

?>