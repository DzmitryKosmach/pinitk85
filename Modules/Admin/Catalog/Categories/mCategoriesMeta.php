<?php

/** Админка: настройка параметров генерации заголовков и метатегов серий и товаров в категории
 * @author	Seka
 */


class mCategoriesMeta extends Admin {
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
	static $catId;

	/**
	 * @var array
	 */
	static $catInf;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		self::$catId = intval($_GET['id']);
		$oCategories = new Catalog_Categories();
		self::$catInf = $oCategories->getRow(
			'*',
			'`id` = ' . self::$catId
		);
		if(self::$catInf === false){
			Pages::flash('Запрошенная для редактирования категория не найдена.', true, Url::a('admin-catalog-categories'));
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'catInf'	=> self::$catInf
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit(self::$catInf);
		return $frm->run('mCategoriesMeta::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		$save = array(
			'pattern_series_title'	=> $newData['pattern_series_title'],
			'pattern_series_h1'		=> $newData['pattern_series_h1'],
			'pattern_series_dscr'	=> $newData['pattern_series_dscr'],
			'pattern_series_kwrd'	=> $newData['pattern_series_kwrd'],
			'pattern_items_title'	=> $newData['pattern_items_title'],
			'pattern_items_h1'		=> $newData['pattern_items_h1'],
			'pattern_items_dscr'	=> $newData['pattern_items_dscr'],
			'pattern_items_kwrd'	=> $newData['pattern_items_kwrd'],
			'pattern_items_text'	=> $newData['pattern_items_text'],

			'pattern_page_title'	=> $newData['pattern_page_title'],
			'pattern_page_h1'		=> $newData['pattern_page_h1'],
			'pattern_page_dscr'		=> $newData['pattern_page_dscr'],
			'pattern_page_kwrd'		=> $newData['pattern_page_kwrd'],

			'prefix_photo_alt1'		=> $newData['prefix_photo_alt1'],
			'prefix_photo_title1'	=> $newData['prefix_photo_title1']
		);

		$oCategories = new Catalog_Categories();
		$oCategories->upd(self::$catId, $save);

		Pages::flash(
			'Параметры генерации заголовков и метатегов серий и товаров сохранены',
			false,
			Url::buildUrl(Url::a('admin-catalog-categories'), array(
				'p'	=> self::$catInf['parent_id']
			))
		);
	}
}

?>