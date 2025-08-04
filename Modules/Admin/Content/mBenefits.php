<?php

/** Админка: Блоки "Преимущества" в шапке
 * @author	Seka
 */


class mBenefits extends Admin {
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
		'head-benefit1',
		'head-benefit1-url',
		'head-benefit2',
		'head-benefit2-url',
		'head-benefit3',
		'head-benefit3-url',
		'head-benefit4',
		'head-benefit4-url',
		'head-benefit5',
		'head-benefit5-url',
		'head-benefit6',
		'head-benefit6-url',
		'head-benefit7',
		'head-benefit7-url',
		'head-benefit8',
		'head-benefit8-url'
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
		return $frm->run('mBenefits::save');
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