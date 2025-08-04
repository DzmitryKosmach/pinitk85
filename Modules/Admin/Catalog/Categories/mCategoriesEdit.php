<?php

/** Админка: добавление / редактирование категорий каталога
 * @author	Seka
 */


class mCategoriesEdit extends Admin {
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
	static $parentId;

	/**
	 * @var int
	 */
	static $catDeepLevel;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oCategories = new Catalog_Categories();

		// Получаем дерево категорий
		$catTree = $oCategories->getTree('id', 1000, 0, false);


		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oCategories->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенная для редактирования категория не найдена.', true, Url::a('admin-catalog-categories'));
			}
			self::$parentId = $init['parent_id'];

			//if(!self::$parentId){
				$init = $oCategories->imageExtToData($init);
			//}

			// Связь с фильтрами
			/*$oCategories2Filters = new Catalog_Categories_2Filters();
			$init['filters'] = $oCategories2Filters->getCol(
				'filter_id',
				'`category_id` = ' . self::$pId
			);*/

		}else{
			// Добавление
			self::$parentId = intval($_GET['p']);

			$init = array(
				'has_subcats'	=> 0,
				'parent_id'		=> self::$parentId
			);

			// Проверка что указанная родительская категория существует (если она задана)
			if(self::$parentId){
				if(!$oCategories->getCount('`id` = ' . self::$parentId)){
					Pages::flash('При добавлении категории не найдена указанная родительская категория.', true, Url::a('admin-catalog-categories'));
				}
			}
		}

		// Определяем уровень сложенности создаваемой/редактируемой категории
		self::$catDeepLevel = 1 + $oCategories->getDeepLevel(self::$parentId);

		//
		$oYmlFiles = new Catalog_Yml_Files();
		$ymlFiles = $oYmlFiles->getHash('id, file');

		// Домены
		$oDomains = new Catalog_Domains();
		$domains = $oDomains->getHash(
			'id, domain',
			'',
			'`domain` ASC'
		);
		// Исключаем домены, использованные для других категорий
		$usedDomainsIds = $oCategories->getCol(
			'domain_id',
			'`domain_id` != 0 AND `id` != ' . self::$pId
		);
		foreach($usedDomainsIds as $id){
			unset($domains[$id]);
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init,

			'ymlFiles'	=> $ymlFiles,

			'parentId'		=> self::$parentId,
			'catDeepLevel'	=> self::$catDeepLevel,
			'catTree'		=> $catTree,

			'domains'	=> $domains
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mCategoriesEdit::save', 'mCategoriesEdit::check');
	}



	/**
	 * @param $initData
	 * @return array
	 */
	static function check($initData){

		// Проверка файла картинки
		//if(!self::$parentId){
			$imgCheck = Form::checkUploadedImage(
				self::$pId,
				'image',
				3200,
				2400,
				true
			);
			if($imgCheck !== true){
				return array($imgCheck);
			}
		//}

		// Проверка уникальности URL
		$_POST['url'] = mb_strtolower(trim($_POST['url']));
		$oCategories = new Catalog_Categories();
		if($oCategories->getCount('`url` = \'' . MySQL::mres($_POST['url']) . '\' AND `parent_id` = ' . intval(self::$parentId) . ' AND `id` != ' . intval(self::$pId))){
			return array(array(
				'name' => 'url',
				'msg'  => 'Указанный URL используется для другой категории.'
			));
		}

		return true;
	}



	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		if(self::$catDeepLevel < Catalog_Categories::MAX_DEEP_LEVEL){
			$hasSubcats = intval($newData['has_subcats']) ? 1 : 0;
		}else{
			$hasSubcats = 0;
		}

		$parentId = intval($newData['parent_id']);
		//if(!$parentId) $parentId = self::$parentId;

		$save = array(
			'domain_id'		=> intval($newData['domain_id']),
			'name'			=> $newData['name'],

			'title'		=> $newData['title'],
			'h1'		=> $newData['h1'],
			'dscr'		=> $newData['dscr'],
			'kwrd'		=> $newData['kwrd'],
			'text'		=> $newData['text'],

			'discounts_button'	=> $newData['discounts_button'],
			'discounts_title'	=> $newData['discounts_title'],
			'discounts_h1'		=> $newData['discounts_h1'],
			'discounts_dscr'	=> $newData['discounts_dscr'],
			'discounts_kwrd'	=> $newData['discounts_kwrd'],
			'discounts_text'	=> $newData['discounts_text'],

			'url'			=> $newData['url'],
			'in_index'      => intval($newData['in_index']) ? 1 : 0,
			'yml_file_id'	=> intval($newData['yml_file_id']),
			'parent_id'		=> $parentId,
			'has_subcats'	=> $hasSubcats,

			'series_info'	=> $newData['series_info'],
			'price_search_title'		=> $newData['price_search_title'],
			'price_search_title_series'	=> $newData['price_search_title_series'],

			'use_noindex'      => intval($newData['use_noindex']) ? 1 : 0,
			'use_nofollow'     => intval($newData['use_nofollow']) ? 1 : 0
		);

		$oCategories = new Catalog_Categories();

		if(self::$pId){
			// Редактирование
			$oCategories->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

			if($initData['name'] != $newData['name']){
				// Ключевые слова серий
				$oCategories->updateKeywords4Series(self::$pId);
			}

		}else{
			// Добавление
			self::$pId = $oCategories->add($save);

			$msg = 'Категория успешно добавлена.';
		}

		// Save image
		if(/*!$parentId && */$_FILES['image']['name']){
			$oCategories->imageSave(
				self::$pId,
				$_FILES['image']['tmp_name']
			);
		}

		/*if($hasSubcats == 0){
			// Сохраняем связи с фильтрами
			$oCategories2Filters = new Catalog_Categories_2Filters();
			$oCategories2Filters->delCond('`category_id` = ' . self::$pId);
			foreach($newData['filters'] as $fId){
				$fId = intval($fId);
				$oCategories2Filters->add(array(
					'category_id'		=> self::$pId,
					'filter_id'			=> $fId
				));
			}
		}*/

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-categories'), array(
				'p'	=> self::$parentId
			))
		);
	}
}

?>