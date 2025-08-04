<?php

/** Админка: Инфо-блоки внизу страницы серии
 * @author	Seka
 */


class mSeriesInfo extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CONTENT;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_PAGES;

	/**
	 * @var array
	 */
	protected static $optNames = array(
		'series-info1-title',
		'series-info1-text',
		'series-info2-title',
		'series-info2-text',
		'series-info3-title',
		'series-info3-text'
	);



	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();


		$init = array();

		foreach(self::$optNames as $on){
			$init[$on] = Options::name($on);
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
		return $frm->run('mSeriesInfo::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		foreach(self::$optNames as $on){
			Options::set(
				$on,
				trim($newData[$on])
			);
		}

		Pages::flash('Изменения сохранены');
	}
}

?>