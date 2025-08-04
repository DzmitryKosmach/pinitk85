<?php

/** Админка: редактирование фотографии/схемы проекта
 * Редактируется только параметр ALT и тип изображения
 * @author	Seka
 */


class mProjectsPicsEdit extends Admin {
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
	 * @var int
	 */
	static $projectId;


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
		$oPics = new Clients_Projects_Pics();
		$init = $oPics->imageExtToData(
			$oPics->getRow('*', '`id` = ' . self::$pId)
		);
		if($init === false){
			Pages::flash('Запрошенная для редактирования фотография/схема не найдена.', true, Url::a('admin-clients-projects'));
			exit;
		}

		// Данные серии
		self::$projectId = $init['project_id'];
		$oProjects = new Clients_Projects();
		$projectInf = $oProjects->getRow('*', '`id` = ' . self::$projectId );
		if(!$projectInf){
			Pages::flash('Не найден проект для редактирования его фотографий/схем.', true, Url::a('admin-clients-projects'));
			exit;
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'			=> $init,
			'projectInf'	=> $projectInf
		));

		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mProjectsPicsEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		// Данные для сохранения
		$save = array(
			'type'	=> $newData['type'],
			'alt'	=> $newData['alt']
		);

		$oPics = new Clients_Projects_Pics();
		$oPics->upd(self::$pId, $save);
		$msg = 'Изменения сохранены.';

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-clients-projects-pics'), array(
				'p'		=> self::$projectId
			))
		);
	}
}

?>