<?php

/** Нефункциональная текстовая страница
 * Текст выводится в табличке "Успех"
 * @author	Seka
 */

class mMsgOk {

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf,
			'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id']))
		));
	}
}

?>