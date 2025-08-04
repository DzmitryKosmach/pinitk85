<?php

/** Админка: добавление / редактирование теговой страницы в категории
 * @author	Seka
 */


class mPagesEdit extends Admin {
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
	static $groupId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oPages = new Catalog_Pages();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oPages->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенная для редактирования теговая страница не найдена.', true, Url::a('admin-catalog-categories'));
			}
			self::$groupId = $init['group_id'];

			// Значения фильтров
			/*$oPagesFilters = new Catalog_Pages_Filters();
			$init['filters'] = $oPagesFilters->getCol(
				'value_id',
				'`page_id` = ' . self::$pId
			);*/

		}else{
			// Добавление
			self::$groupId = intval($_GET['g']);

			$init = array();
		}

		// Данные о группе теговых страниц
		$oGroups = new Catalog_Pages_Groups();
		$groupInf = $oGroups->getRow('*', '`id` = ' . self::$groupId);
		if(!$groupInf){
			Pages::flash('Запрошенная группа теговых страниц не найдена.', true, Url::a('admin-catalog-categories'));
			exit;
		}

		// Данные о категории
		$oCategories = new Catalog_Categories();
		$categoryInf = $oCategories->getRow('*', '`id` = ' . $groupInf['category_id']);
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
			'groupInf'		=> $groupInf,
			'categoryInf'	=> $categoryInf,
			//'filters'		=> $filters
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mPagesEdit::save', 'mPagesEdit::check');
	}



	/**
	 * @param $initData
	 * @return array
	 */
	static function check($initData){

		// Проверка уникальности URL
		$_POST['url'] = mb_strtolower(trim($_POST['url']));
		$oPages = new Catalog_Pages();
		if($oPages->getCount('`url` = \'' . MySQL::mres($_POST['url']) . '\' AND `group_id` = ' . intval(self::$groupId) . ' AND `id` != ' . intval(self::$pId))){
			return array(array(
				'name' => 'url',
				'msg'  => 'Указанный URL используется для другой страницы.'
			));
		}

		return true;
	}



	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'name'		=> $newData['name'],
			'title'		=> $newData['title'],
			'h1'		=> $newData['h1'],
			'dscr'		=> $newData['dscr'],
			'kwrd'		=> $newData['kwrd'],
			'text'		=> $newData['text'],
			'url'		=> $newData['url'],
			'group_id'	=> self::$groupId
		);

		$oPages = new Catalog_Pages();

		if(self::$pId){
			// Редактирование
			$oPages->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oPages->add($save);

			$msg = 'Теговая страница успешно добавлена.';
		}

		// Сохраняем значения фильтров
		/*$oPagesFilters = new Catalog_Pages_Filters();
		$oPagesFilters->delCond('`page_id` = ' . self::$pId);
		$newData['filters'] = array_unique(array_map('intval', $newData['filters']));
		foreach($newData['filters'] as $vId){
			if($vId){
				$oPagesFilters->add(array(
					'page_id'	=> self::$pId,
					'value_id'	=> $vId
				));
			}
		}*/

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-categories-groups-pages'), array(
				'g'	=> self::$groupId
			))
		);
	}
}

?>