<?php

/** Админка: Добавление/Редактирование новости
 * @author	Seka
 */


class mNewsEdit extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CONTENT;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_NEWS;

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

		$oNews = new News();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oNews->imageExtToData($oNews->getRow(
				'*',
				'`id` = ' . self::$pId
			));
			if($init === false){
				Pages::flash('Запрошенная для редактирования новость не найдена.', true, Url::a('admin-content-news'));
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
		return $frm->run('mNewsEdit::save', 'mNewsEdit::check');
	}




	/**
	 * @param $initData
	 * @return array
	 */
	static function check($initData){

		// Проверка файла картинки
		$imgCheck = Form::checkUploadedImage(
			self::$pId,
			'image',
			3200,
			2400,
			true
		);
		if($imgCheck !== true){
			return array($imgCheck);
		}

		// Проверка уникальности URL
		$_POST['url'] = mb_strtolower(trim($_POST['url']));
		$oNews = new News();
		if($oNews->getCount('`url` = \'' . MySQL::mres($_POST['url']) . '\' AND `id` != ' . intval(self::$pId))){
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
			'date'		=> MySQL::date(Form::getDate($newData['date'])),
			'n_title'	=> $newData['n_title'],
			'brief'		=> $newData['brief'],
			'title'		=> $newData['title'],
			'dscr'		=> $newData['dscr'],
			'kwrd'		=> $newData['kwrd'],
			'text'		=> $newData['text'],
			'url'		=> $newData['url']
		);

		$oNews = new News();

		if(self::$pId){
			// Редактирование
			$oNews->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oNews->add($save);

			$msg = 'Новость успешно добавлена.';
		}

		// Save image
		if($_FILES['image']['name']){
			$oNews->imageSave(
				self::$pId,
				$_FILES['image']['tmp_name']
			);
		}

		Pages::flash($msg, false, Url::a('admin-content-news'));
	}
}

?>