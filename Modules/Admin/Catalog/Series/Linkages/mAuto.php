<?php

/** Админка: автоматическая перелинковка серий
 * @author	Seka
 */


class mAuto extends Admin {

	static $output = OUTPUT_DEFAULT;

	/**
	 * @var int
	 */
	static $adminMenu = Admin::CATALOG;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CATALOG;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		if(intval($_GET['category-id'])){
			// Запрос на генерацию списка URL'ов по заданным параметрам для автозаполнения списка доноров и акцепторов
			return self::autoFill(
				intval($_GET['category-id']),
				intval($_GET['price-grouping']),
				intval($_GET['price-cnt'])
			);
		}

		// Блоки линковок
		$oLinkage = new Catalog_Series_Linkage();
		$linkages = $oLinkage->get(
			'*',
			'',
			'order'
		);
		if(!count($linkages)){
			$tpl = Pages::tplFile($pageInf, 'no_linkages');
			return pattExeP(fgc($tpl), array());
		}

		// К-во серий в блоке по-умолчанию
		$oPages = new Pages();
		include_once(Pages::moduleFile($oPages->getRow('module', '`id` = ' . Catalog::CATALOG_PAGE_ID)));

		// Категории для автозаполнения
		$oCategories = new Catalog_Categories();
		$catsIds = $oCategories->getFinishIds(0);

		// Исходные значения для формы
		$init = isset($_SESSION['linkage-auto-form-values']) ?
			$_SESSION['linkage-auto-form-values'] :
			array(
				'max_series'		=> mCatalog::SERIES_IN_CAT,
				'autofill_price_cnt'=> mCatalog::SERIES_IN_CAT + 1
			);

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init,
			'linkages'	=> $linkages,
			'catsIds'	=> $catsIds
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mAuto::save');
	}


	/**
	 * Генерация списка URL'ов по заданным параметрам для автозаполнения списка доноров и акцепторов
	 * @param	int		$categoryId
	 * @param	bool	$priceGrouping
	 * @param	int		$priceGroupingCnt
	 * @return	string
	 */
	static function autoFill($categoryId, $priceGrouping, $priceGroupingCnt){
		self::$output = OUTPUT_FRAME;
		$oLinkageAuto = new Catalog_Series_Linkage_Auto();
		return $oLinkageAuto->autoFillForm($categoryId, $priceGrouping, $priceGroupingCnt);
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		$_SESSION['linkage-auto-form-values'] = $newData;
		unset(
			$_SESSION['linkage-auto-form-values']['donors'],
			$_SESSION['linkage-auto-form-values']['acceptors']
		);

		$oLinkageAuto = new Catalog_Series_Linkage_Auto();
		$linksCnt = $oLinkageAuto->generate(
			intval($newData['linkage_id']),
			intval($newData['max_series']),
			trim($newData['donors']),
			trim($newData['acceptors'])
		);

		Pages::flash(
			'Перелинковка серий выполнена. Всего создано связей: ' . $linksCnt,
			false,
			Url::buildUrl(0)
		);
	}
}

?>