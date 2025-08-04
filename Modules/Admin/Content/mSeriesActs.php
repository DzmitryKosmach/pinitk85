<?php

/** Админка: Блоки действий справа на страницах серий
 * @author	Seka
 */


class mSeriesActs extends Admin {
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
		'series-acts1-title',
		'series-acts1-text',
		'series-acts2-title',
		'series-acts2-text',
		'series-acts3-title',
		'series-acts3-text'/*,
		'series-acts4-title',
		'series-acts4-text'*/
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
		return $frm->run('mSeriesActs::save');
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