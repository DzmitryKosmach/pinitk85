<?php

/** Авторизация дилера
 * @author	Seka
 */

class mLogin {

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main($pageInf = array()){

		if(Dealers_Security::isAuthorized()){
			// Дилер уже залогинен
			header('Location: ' . Url::a('dealer'));
			exit;
		}

		// Создание шаблона формы
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf
		));

		// Вывод формы
		$frm = new Form($formHtml);
		$frm->setInit(array(

		));
		return $frm->run('mLogin::login', 'mLogin::check');
	}


	/** Проверка данных из формы
	 * @static
	 * @param	array	$initData
	 * @return	array|bool
	 */
	static function check($initData){
		// Пробуем залогиниться
		$oSecurity = new Dealers_Security();
		$loginResult = $oSecurity->login(
			trim($_POST['login']),
			trim($_POST['pass'])
		);

		if($loginResult == Dealers_Security::LOGIN_ERR_INCORRECT) return array(array(
			'msg'	=> 'Пользователь с указанными логином и паролем не найден'
		));
		if($loginResult == Dealers_Security::LOGIN_ERR_BANNED) return array(array(
			'msg'	=> 'Ваш аккаунт заблокирован'
		));

		// Всё ок
		return true;
	}


	/**
	 * @static
	 * @param	array	$initData
	 * @param	array	$newData
	 */
	static function login($initData, $newData){
		// Редирект
		header('Location: ' . Url::a('dealer'));
		exit;
	}
}

?>