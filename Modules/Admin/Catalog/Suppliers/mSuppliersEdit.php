<?php

/** Админка: добавление / редактирование поставщика
 * @author	Seka
 */


class mSuppliersEdit extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CATALOG;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_SUPPLIERS;

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

		$oSuppliers = new Catalog_Suppliers();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oSuppliers->imageExtToData($oSuppliers->getRow(
				'*',
				'`id` = ' . self::$pId
			));
			if($init === false){
				Pages::flash('Запрошенный для редактирования поставщик не найден.', true, Url::a('admin-catalog-suppliers'));
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
		return $frm->run('mSuppliersEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'name'			=> $newData['name'],
			'description'	=> $newData['description'],

			'fio'		=> $newData['fio'],
			'phone'		=> $newData['phone'],
			'email'		=> $newData['email'],

			'discount'	=> $newData['discount'],
			'delivery'	=> $newData['delivery'],
			'assembly'	=> $newData['assembly']
		);

		$oSuppliers = new Catalog_Suppliers();

		if(self::$pId){
			// Редактирование
			$oSuppliers->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oSuppliers->add($save);

			$msg = 'Поставщик успешно добавлен.';
		}

		// Save image
		if($_FILES['file']['name']){
			$oSuppliers->imageSave(
				self::$pId,
				$_FILES['file']['tmp_name'],
				$_FILES['file']['name']
			);
		}

		Pages::flash($msg, false, Url::a('admin-catalog-suppliers'));
	}
}

?>