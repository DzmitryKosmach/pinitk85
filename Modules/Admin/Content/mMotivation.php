<?php

/** Админка: блоки мотивации на главной стр.
 * @author	Seka
 */


class mMotivation extends Admin {
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
		'index-motivation1-title',
		'index-motivation1-text',
		'index-motivation1-but',
		'index-motivation1-but-func',
		'index-motivation2-title',
		'index-motivation2-text',
		'index-motivation2-but',
		'index-motivation2-but-func',
		'index-motivation3-title',
		'index-motivation3-text',
		'index-motivation3-but',
		'index-motivation3-but-func'
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
		return $frm->run('mMotivation::save');
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