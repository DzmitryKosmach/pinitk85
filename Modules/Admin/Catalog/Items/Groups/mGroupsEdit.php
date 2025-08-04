<?php

/** Админка: добавление / редактирование группы товаров в серии
 * @author	Seka
 */


class mGroupsEdit extends Admin {
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
	 * @var int
	 */
	static $seriesId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oItemsGroups = new Catalog_Items_Groups0();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oItemsGroups->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенная для редактирования группа товаров не найдена.', true, Url::a('admin-catalog-series'));
			}
			self::$seriesId = $init['series_id'];

		}else{
			// Добавление
			self::$seriesId = intval($_GET['s']);

			$init = array();
		}

		// Находим серию
		$oSeries = new Catalog_Series();
		$seriesInf = $oSeries->getRow('*', '`id` = ' . self::$seriesId);
		if(!$seriesInf){
			Pages::flash('Запрошенная для редактирования серия мебели не найдена.', true, Url::a('admin-catalog-series'));
			exit;
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init,
			'seriesInf'	=> $seriesInf
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mGroupsEdit::save');
	}



	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		$save = array(
			'name'	=> $newData['name'],
			'series_id'	=> self::$seriesId
		);

		$oItemsGroups = new Catalog_Items_Groups0();

		if(self::$pId){
			// Редактирование
			$oItemsGroups->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oItemsGroups->add($save);

			$msg = 'Группа товаров успешно добавлена.';
		}

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-items-groups'), array(
				's'		=> self::$seriesId,
				'ret'	=> $_GET['ret']
			))
		);
	}
}

?>