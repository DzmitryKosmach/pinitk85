<?php

/** Админка: добавление / редактирование значения для фильтра
 * @author	Seka
 */


class mValuesEdit extends Admin {
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
	static $filterId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oFilters = new Catalog_Filters();
		$oFiltersValues = new Catalog_Filters_Values();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oFiltersValues->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошеный для редактирования вариант начения для фильтра не найден.', true, Url::a('admin-catalog-filters'));
			}
			self::$filterId = $init['filter_id'];

		}else{
			// Добавление
			self::$filterId = intval($_GET['f']);
			$init = array();
		}

		// Находим фильтр
		$filter = $oFilters->getRow('*', '`id` = ' . self::$filterId);
		if(!$filter){
			Pages::flash('Запрошенный для редактирования фильтр не найден.', true, Url::a('admin-catalog-filters'));
			exit;
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'filter'	=> $filter,
			'init'		=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mValuesEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		$save = array(
			'value'		=> $newData['value'],
			'filter_id'	=> self::$filterId
		);

		$oFiltersValues = new Catalog_Filters_Values();

		if(self::$pId){
			// Редактирование
			$oFiltersValues->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oFiltersValues->add($save);

			$msg = 'Вариант значения для фильтра успешно добавлен.';
		}

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-filters-values'), array(
				'f'	=> self::$filterId
			))
		);
	}
}

?>