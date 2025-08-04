<?php

/** Админка: добавление / редактирование названия характеристики для серий категории
 * @author	Seka
 */


class mOptionsEdit extends Admin {
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

		$oOpts4Series = new Catalog_Categories_Opts4Series();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oOpts4Series->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенная для редактирования характеристика не найдена.', true, Url::a('admin-catalog-categories'));
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
			Pages::flash('Категория с подкатегориями не может содержать серии.', true, Url::a('admin-catalog-categories'));
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
		return $frm->run('mOptionsEdit::save');
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

		$oOpts4Series = new Catalog_Categories_Opts4Series();

		if(self::$pId){
			// Редактирование
			$oOpts4Series->upd(self::$pId, $save);

			$msg = 'Изменения сохранены. Названия характерик в сериях категории также изменены.';

			$oSeries = new Catalog_Series();
			$sIds = $oSeries->getCol(
				'id',
				'`category_id` = ' . self::$categoryId
			);
			if(count($sIds)){
				$oSeriesOptions = new Catalog_Series_Options();
				$oSeriesOptions->updCond(
					'`series_id` IN (' . implode(',', $sIds) . ') AND `name` = \'' . MySQL::mres($initData['name']) . '\'',
					array(
						'name'	=> $newData['name']
					)
				);
			}

		}else{
			// Добавление
			self::$pId = $oOpts4Series->add($save);

			$msg = 'Название характеристики успешно добавлено.';
		}

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-categories-options'), array(
				'c'	=> self::$categoryId
			))
		);
	}
}

?>