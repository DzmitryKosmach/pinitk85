<?php

/** Админка: Шаблоны E-mail уведомлений
 * @author
 */

class mEmails extends Admin {

	/**
	 * @var int
	 */
	static $adminMenu = Admin::SETTINGS;

	/**
	 * @var string
	 */
	var $mainClass = 'Email_Tpl';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_SETTINGS;



	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();
		$o->getOperations();

		$oEmails = new Email_Tpl();

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'emails'  => $oEmails->get('*')
		));
	}
}

?>