<?php

/** Админка: редактирование фотографии серии
 * Редактируется только параметр ALT и способ ресайза
 * @author	Seka
 */


class mPhotosEdit extends Admin {
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
	static $seriesId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		// Исходные данные
		self::$pId = intval($_GET['id']);
		$oPhotos = new Catalog_Series_Photos();
		$init = $oPhotos->imageExtToData(
			$oPhotos->getRow('*', '`id` = ' . self::$pId)
		);
		if($init === false){
			Pages::flash('Запрошенная для редактирования фотография не найдена.', true, Url::a('admin-catalog-series'));
			exit;
		}

		// Данные серии
		self::$seriesId = $init['series_id'];
		$oSeries = new Catalog_Series();
		$seriesInf = $oSeries->getRow('*', '`id` = ' . self::$seriesId);
		if(!$seriesInf){
			Pages::flash('Не найдена серия для редактирования её фотографий.', true, Url::a('admin-catalog-series'));
			exit;
		}

			// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init,
			'seriesInf'	=> $seriesInf
		));

		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mPhotosEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		// Данные для сохранения
		$save = array(
			'alt'	=> $newData['alt'],
			'rm'	=> $newData['rm']
		);

		$oPhotos = new Catalog_Series_Photos();
		$oPhotos->upd(self::$pId, $save);
		$msg = 'Изменения сохранены.';

		Pages::flash(
			$msg,
			false,
			Url::buildUrl(Url::a('admin-catalog-series-photos'), array(
				's'		=> self::$seriesId,
				'ret'	=> $_GET['ret']
			))
		);
	}
}

?>