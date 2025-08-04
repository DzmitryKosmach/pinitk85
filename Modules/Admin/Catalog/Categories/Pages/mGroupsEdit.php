<?php

/** Админка: добавление / редактирование группы теговых страниц в категории
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
	static $categoryId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oGroups = new Catalog_Pages_Groups();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oGroups->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенная для редактирования группа теговых страниц не найдена.', true, Url::a('admin-catalog-categories'));
			}
			self::$categoryId = $init['category_id'];

		}else{
			// Добавление
			self::$categoryId = intval($_GET['c']);

			$init = array();
		}

		// Данные о категории
		$oCategories = new Catalog_Categories();
		$categoryInf = $oCategories->getRow('*', '`id` = ' . self::$categoryId);
		if(!$categoryInf){
			Pages::flash('Запрошенная категория не найдена.', true, Url::a('admin-catalog-categories'));
			exit;
		}
		/*if(intval($categoryInf['has_subcats'])){
			Pages::flash('Категория с подкатегориями не может содержать теговых страниц.', true, Url::a('admin-catalog-categories'));
			exit;
		}*/

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init,
			'categoryInf'	=> $categoryInf
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
			'name'			=> $newData['name'],
			'in_series_list'=> intval($newData['in_series_list']) ? 1 : 0,
			'opened'		=> intval($newData['opened']) ? 1 : 0,
			'category_id'	=> self::$categoryId
		);

		$oGroups = new Catalog_Pages_Groups();

		if(self::$pId){
			// Редактирование
			$oGroups->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oGroups->add($save);

			$msg = 'Группа успешно добавлена.';
		}

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-categories-groups'), array(
				'c'	=> self::$categoryId
			))
		);
	}
}

?>