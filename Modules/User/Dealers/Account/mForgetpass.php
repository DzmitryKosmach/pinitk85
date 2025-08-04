<?php

/** Восстановление пароля дилера
 *
 * @author	Seka
 */

class mForgetpass {

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main($pageInf = array()){

		// Создание шаблона формы
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf
		));

		// Вывод формы
		$frm = new Form($formHtml);
		$frm->setInit(array(

		));
		return $frm->run('mForgetpass::save', 'mForgetpass::check');
	}






	/** Проверка данных из формы
	 * @static
	 * @param	array	$initData
	 * @return	array|bool
	 */
	static function check($initData){
		$_POST['email'] = trim($_POST['email']);

		$oDealers = new Dealers();
		if(!$oDealers->getCount('`email` = \'' . MySQL::mres($_POST['email']) . '\'')) return array(array(
			'name'	=> 'email',
			'msg'	=> 'Дилер с указанным адресом E-mail не найден'
		));

		return true;
	}


	/** Сохранение результатов (и запуск импорта)
	 * @static
	 * @param	array	$initData
	 * @param	array	$newData
	 */
	static function save($initData, $newData){
		$oDealers = new Dealers();
		$oDealers->restorePass($oDealers->getCell('id', '`email` = \'' . MySQL::mres($_POST['email']) . '\''));

		// Редирект
		header('Location: ' . Url::a('dealer-forgetpass-ok'));
		exit;
	}
}

?>