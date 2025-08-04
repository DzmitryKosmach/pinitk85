<?php

/** Админка: добавление / редактирование маркера (ленточки/флажка) для серий
 * @author	Seka
 */


class mMarkersEdit extends Admin {
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

		$oMarkers = new Catalog_Markers();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oMarkers->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенный для редактирования маркер не найден.', true, Url::a('admin-catalog-markers'));
			}

		}else{
			// Добавление
			$init = array(
				'padding'	=> '35'
			);
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'	=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mMarkersEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'color'		=> $newData['color'],
			'padding'	=> abs(intval($newData['padding'])),
			'text'		=> $newData['text']
		);

		$oMarkers = new Catalog_Markers();

		if(self::$pId){
			// Редактирование
			$oMarkers->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oMarkers->add($save);

			$msg = 'Маркер успешно добавлен.';
		}

		Pages::flash($msg, false, Url::a('admin-catalog-markers'));
	}
}

?>