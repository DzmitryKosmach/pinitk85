<?php

/** Админка: Опции
 * @author	Seka
 */


class mOptions extends Admin {

	/**
	 * @var int
	 */
	static $adminMenu = Admin::SETTINGS;

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$oId = intval($_GET['id']);

		$oOptions = new Options();
		$init = $oOptions->getRow('*', '`id` = ' . $oId, '', '', '', true);
		if($init === false){
			Pages::flash('Опция не найдена', true);
		}

		// Изменяем боковое меню
		if(isset($_GET['menu'])){
			self::$adminMenu = intval($_GET['menu']);
		}


		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'opt'	=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->setInit($init);
		$frm->adminMode = true;
		return $frm->run('mOptions::save');
	}






	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$oOptions = new Options();
		$oOptions->upd($initData['id'], array(
			'value'	=> $newData['value']
		));

		Pages::flash('Опция "' . $initData['dsc'] . '" сохранена.', false, $_GET['ret']);
	}

}

?>