<?php

/** Админка: добавление / редактирование серии металлической мебели
 * @author	Seka
 */


class mMetallSeriesEdit extends Admin {
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

		$oMetallSeries = new Catalog_MetallSeries();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oMetallSeries->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенная для редактирования серия металлической мебели не найдена.', true, Url::a('admin-catalog-metallseries'));
			}

		}else{
			// Добавление
			$init = array();
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
		return $frm->run('mMetallSeriesEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'name'			=> $newData['name'],
			'price_accembly'=> $newData['price_accembly']
		);

		$oMetallSeries = new Catalog_MetallSeries();

		if(self::$pId){
			// Редактирование
			$oMetallSeries->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oMetallSeries->add($save);

			$msg = 'Серия металлической мебели успешно добавлен.';
		}

		Pages::flash($msg, false, Url::a('admin-catalog-metallseries'));
	}
}

?>