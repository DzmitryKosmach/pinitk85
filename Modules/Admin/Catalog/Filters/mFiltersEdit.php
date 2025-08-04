<?php

/** Админка: добавление / редактирование фильтра
 * @author	Seka
 */


class mFiltersEdit extends Admin {
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

		$oFilters = new Catalog_Filters();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oFilters->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошеный для редактирования фильтр не найден.', true, Url::a('admin-catalog-filters'));
			}


		}else{
			// Добавление
			$init = array(
				'type'		=> Catalog_Filters::TYPE_CHECKBOX,
				'display'	=> 1
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
		return $frm->run('mFiltersEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		$save = array(
			'name'	=> $newData['name'],
			'type'	=> $newData['type'],
			'in_series_list'	=> intval($newData['in_series_list']) ? 1 : 0,
			'display'	=> intval($newData['display']) ? 1 : 0,
			'opened'	=> intval($newData['opened']) ? 1 : 0
		);

		$oFilters = new Catalog_Filters();

		if(self::$pId){
			// Редактирование
			$oFilters->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oFilters->add($save);

			$msg = 'Фильтр успешно добавлен.';
		}

		Pages::flash($msg, false, Url::a('admin-catalog-filters'));
	}
}

?>