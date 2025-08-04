<?php

/** Смена пароля дилера
 * @author	Seka
 */

class mChangepass {

	/**
	 * @var bool
	 */
	static $secure = true;

	/**
	 * @var array
	 */
	static $userInfo = array();

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main($pageInf = array()){

		// Исходные данные
		self::$userInfo = Dealers_Security::getCurrent();

		// Создание шаблона формы
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf,
			'userInfo'	=> self::$userInfo
		));

		// Вывод формы
		$frm = new Form($formHtml);
		$frm->setInit(array(

		));
		return $frm->run('mChangepass::save', 'mChangepass::check');
	}






	/** Проверка данных из формы
	 * @static
	 * @param	array	$initData
	 * @return	array|bool
	 */
	static function check($initData){
		foreach($_POST as &$v) if(!is_array($v)) $v = trim($v); unset($v);

		// Проверяем пароль и повтор
		if($_POST['pass'] !== $_POST['pass2']) return array(
			array(
				'name'	=> 'pass',
				'msg'	=> 'Пароль и его повтор должны совпадать!'
			),
			array(
				'name'	=> 'pass2'
			)
		);

		// Проверяем старый пароль
		if($_POST['pass_old'] != self::$userInfo['pass']) return array(array(
			'name'	=> 'pass_old',
			'msg'	=> 'Старый пароль указан неверно.'
		));


		return true;
	}






	/** Сохранение результатов (и запуск импорта)
	 * @static
	 * @param	array	$initData
	 * @param	array	$newData
	 */
	static function save($initData, $newData){

		// Обновляем учётные данные
		$oDealers = new Dealers();
		$oDealers->upd(
			self::$userInfo['id'],
			array(
				'pass'	=> $newData['pass']
			)
		);

		$oSecurity = new Dealers_Security();
		$oSecurity->updatePass($newData['pass']);

		// Редирект
		Pages::flash('Новый пароль сохранён.', false, Url::a('dealer'));
		exit;
	}
}

?>