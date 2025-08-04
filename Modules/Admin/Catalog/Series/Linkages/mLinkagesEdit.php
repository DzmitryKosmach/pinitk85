<?php

/** Админка: добавление / редактирование группы (блока) связей между сериями
 * @author	Seka
 */


class mLinkagesEdit extends Admin {
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

		$oLinkage = new Catalog_Series_Linkage();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oLinkage->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенный для редактирования блок связей не найден.', true, Url::a('admin-catalog-series-linkages'));
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
		return $frm->run('mLinkagesEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'name'			=> $newData['name'],
			'color_name'	=> $newData['color_name'],
			'sidecol'		=> intval($newData['sidecol']) ? 1 : 0
		);

		$oLinkage = new Catalog_Series_Linkage();

		if(self::$pId){
			// Редактирование
			$oLinkage->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oLinkage->add($save);

			$msg = 'Блок для связи между сериями успешно добавлен.';
		}

		Pages::flash($msg, false, Url::a('admin-catalog-series-linkages'));
	}
}

?>