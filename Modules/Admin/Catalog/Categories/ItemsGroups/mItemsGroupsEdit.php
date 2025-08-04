<?php

/** Админка: добавление / редактирование группы товаров в сериях категории
 * @author	Seka
 */


class mItemsGroupsEdit extends Admin {
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

		$oItemsGroups = new Catalog_Items_Groups();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oItemsGroups->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенная для редактирования группа товаров не найдена.', true, Url::a('admin-catalog-categories'));
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
		if(intval($categoryInf['has_subcats'])){
			Pages::flash('Категория с подкатегориями не может содержать серии, а значит и группы товаров.', true, Url::a('admin-catalog-categories'));
			exit;
		}

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
		return $frm->run('mItemsGroupsEdit::save');
	}



	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'name'			=> $newData['name'],
			'category_id'	=> self::$categoryId
		);

		$oItemsGroups = new Catalog_Items_Groups();

		if(self::$pId){
			// Редактирование
			$oItemsGroups->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oItemsGroups->add($save);

			$msg = 'Группа успешно добавлена.';
		}

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-categories-itemsgroups'), array(
				'c'	=> self::$categoryId
			))
		);
	}
}

?>