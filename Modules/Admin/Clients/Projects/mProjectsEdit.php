<?php

/** Админка: Добавление/Редактирование проекта
 * @author	Seka
 */


class mProjectsEdit extends Admin {
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

		$oProjects = new Clients_Projects();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oProjects->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенный для редактирования проект не найден.', true, Url::a('admin-clients-projects'));
			}

			$init['date'] = Form::setDate(MySQL::fromDateTime($init['date']));

		}else{
			// Добавление
			$init = array();
			$init['date'] = Form::setDate(time());
		}


		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mProjectsEdit::save', 'mProjectsEdit::check');
	}




	/**
	 * @param $initData
	 * @return array
	 */
	static function check($initData){

		// Проверка уникальности URL
		$_POST['url'] = mb_strtolower(trim($_POST['url']));
		$oProjects = new Clients_Projects();
		if($oProjects->getCount('`url` = \'' . MySQL::mres($_POST['url']) . '\' AND `id` != ' . intval(self::$pId))){
			return array(array(
				'name'	=> 'url',
				'msg'	=> 'Указанный URL используется для другого раздела.'
			));
		}

		return true;
	}



	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'date'	=> MySQL::date(Form::getDate($newData['date'])),
			'name'	=> $newData['name'],
			'city'	=> $newData['city'],
			'title'	=> $newData['title'],
			'dscr'	=> $newData['dscr'],
			'kwrd'	=> $newData['kwrd'],
			'text'	=> $newData['text'],
			'url'	=> $newData['url'],
			'in_index'	=> intval($newData['in_index']) ? 1 : 0
		);

		$oProjects = new Clients_Projects();

		if(self::$pId){
			// Редактирование
			$oProjects->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oProjects->add($save);

			$msg = 'Проект успешно добавлен.';
		}

		Pages::flash($msg, false, Url::a('admin-clients-projects'));
	}
}

?>