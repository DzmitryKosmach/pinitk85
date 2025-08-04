<?php

$_PAGE_ID = 940;
include_once($_SERVER['DOCUMENT_ROOT'] . '/init.php');


/** Выход из аккаунта дилера
 * @author	Seka
 */

class mLogout {

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
    static function main($pageInf = array()){
		$oSecurity = new Dealers_Security();
		$oSecurity->logout();
		header('Location: /');
		exit;
    }
}


$pageInf = array(
	'module'	=> '/User/Dealers/mLogout'
);

$_CONTENT = mLogout::main($pageInf);
include_once($_SERVER['DOCUMENT_ROOT'] . '/output.php');

?>