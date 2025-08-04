<?php

/** Админка: Опции калькулятора корзины
 * @author	Seka
 */


class mOrdersOptions extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::ORDERS;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_ORDERS;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oOptions = new Orders_Options();
		$init = $oOptions->getHash(
			'name, value'
		);
		foreach($init as &$v){
			$v = round($v, Catalog::PRICES_DECIMAL);
		}
		unset($v);


		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mOrdersOptions::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		$oOptions = new Orders_Options();
		foreach($oOptions->get('id, name') as $o){
			$oOptions->upd(
				$o['id'],
				array(
					'value'	=> $newData[$o['name']]
				)
			);
		}

		Pages::flash('Опции калькулятора корзины сохранены');
	}
}

?>