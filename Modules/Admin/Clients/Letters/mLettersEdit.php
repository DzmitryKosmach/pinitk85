<?php

/** Админка: редактирование письма клиента
 * Редактируется только параметр `comment`
 * @author	Seka
 */


class mLettersEdit extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CLIENTS;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CLIENTS;

	/**
	 * @var int
	 */
	static $pId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		// Исходные данные
		self::$pId = intval($_GET['id']);
		$oLetters = new Clients_Letters();
		$init = $oLetters->imageExtToData($oLetters->getRow(
			'*',
			'`id` = ' . self::$pId
		));
		if($init === false){
			Pages::flash('Запрошенное для редактирования письмо клиента не найдено.', true, Url::a('admin-clients-letters'));
			exit;
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'			=> $init
		));

		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mLettersEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		// Данные для сохранения
		$save = array(
			'comment'	=> $newData['comment'],
			'in_index'	=> intval($newData['in_index']) ? 1 : 0
		);

		$oLetters = new Clients_Letters();
		$oLetters->upd(self::$pId, $save);
		$msg = 'Изменения сохранены.';

		Pages::flash(
			$msg,
			false,
			Url::a('admin-clients-letters')
		);
	}
}

?>