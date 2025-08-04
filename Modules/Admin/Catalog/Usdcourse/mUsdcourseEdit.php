<?php

/** Админка: добавление / редактирование курса валют
 * @author	Seka
 */


class mUsdcourseEdit extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CATALOG;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CATALOG;

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

		$oUsdCourses = new Catalog_UsdCourses();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oUsdCourses->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенный для редактирования курс валют не найден.', true, Url::a('admin-catalog-usdcourse'));
			}

		}else{
			// Добавление
			$init = array(
				'course'	=> abs(Options::name('global-usd-course'))
			);
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
		return $frm->run('mUsdcourseEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'name'		=> $newData['name'],
			'course'	=> abs(str_replace(',', '.', trim($newData['course'])))
		);

		$oUsdCourses = new Catalog_UsdCourses();

		if(self::$pId){
			// Редактирование
			$oUsdCourses->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oUsdCourses->add($save);

			$msg = 'Курс валюты успешно добавлен.';
		}

		Pages::flash($msg, false, Url::a('admin-catalog-usdcourse'));
	}
}

?>