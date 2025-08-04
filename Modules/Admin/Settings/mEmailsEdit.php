<?php

/** Админка: Шаблоны E-mail уведомлений - редактирование
 * @author	Seka
 */


class mEmailsEdit extends Admin {

	/**
	 * @var int
	 */
	static $adminMenu = Admin::SETTINGS;

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

		$tId = intval($_GET['id']);

		$oEmails = new Email_Tpl();
		$init = $oEmails->getRow('*', '`id` = ' . $tId, '', '', '', true);
		if($init === false){
			Pages::flash('Запрошенный шаблон не найден.', true);
		}


		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'tpl'	=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->setInit($init);
		$frm->adminMode = true;
		return $frm->run('mEmailsEdit::save');
	}






	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$oEmails = new Email_Tpl();
		$oEmails->upd($initData['id'], array(
			'subj'		=> $newData['subj'],
			'from'		=> $newData['from'],
			'from_name'	=> $newData['from_name'],
			'body'		=> $newData['body']
		));

		Pages::flash('Шаблон e-mail уведомления сохранён.', false, Url::a('admin-settings-emails'));
	}

}

?>