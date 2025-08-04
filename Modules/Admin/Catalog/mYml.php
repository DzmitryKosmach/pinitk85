<?php

/** Админка: Параметры выгрузки товаров в Яндекс.Маркет
 * @author	Seka
 */


class mYml extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CATALOG;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CATALOG;

	/**
	 * @var array
	 */
	static $options = array();

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oYmlFiles = new Catalog_Yml_Files();
		$ymlFiles = $oYmlFiles->getHash('id, file');

		$oOptions = new Options();
		self::$options = $oOptions->get(
			'*',
			'`name` IN (\'' . implode('\', \'', array_keys(Catalog_Yml::$options)) . '\')'
		);

		$init = array();
		foreach(self::$options as $o){
			$init[$o['name']] = $o['value'];
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'ymlFiles'	=> $ymlFiles,
			'options'	=> self::$options
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mYml::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		foreach(self::$options as $o){
			Options::set(
				$o['name'],
				$newData[$o['name']]
			);
		}

		Pages::flash(
			'Настройки сохранены'
		);
		exit;
	}
}

?>