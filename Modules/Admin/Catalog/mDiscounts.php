<?php

/** Админка: Массовая настройка скидок на товары
 * @author	Seka
 */


class mDiscounts extends Admin {
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
	static $output = OUTPUT_DEFAULT;

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		if(isset($_GET['getitems'])){
			return self::getItems(trim($_GET['getitems']));
		}

		$oSeries = new Catalog_Series();
		$tmp = $oSeries->get(
			'id, name, category_id',
			'',
			'order'
		);
		$seriesByCats = array();
		foreach($tmp as $s){
			if(!is_array($seriesByCats[$s['category_id']])) $seriesByCats[$s['category_id']] = array();
			$seriesByCats[$s['category_id']][] = $s;
		}


		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'seriesByCats'	=> $seriesByCats
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit(array(
			'discount'	=> 0
		));
		return $frm->run('mDiscounts::save');
	}


	static function getItems($seriesIdsStr){
		self::$output = OUTPUT_JSON;

		$seriesIdsStr = explode(',', trim($seriesIdsStr));
		$seriesIdsStr = array_map('intval', $seriesIdsStr);
		foreach($seriesIdsStr as $n => $id){
			if(!$id) unset($seriesIdsStr[$n]);
		}
		if(!count($seriesIdsStr)){
			return array();
		}

		$oItems = new Catalog_Items();
		$tmp = $oItems->get(
			'id, name, art, series_id, price_min, discount',
			'`series_id` IN (' . implode(',', $seriesIdsStr) . ')',
			'order'
		);
		$itemsBySeries = array();
		foreach($tmp as $i){
			$i['price_min'] = Catalog::priceFormat($i['price_min']);
			$i['discount'] = Catalog::num2percent($i['discount'], Catalog::PC_DECREASE);

			if(!is_array($itemsBySeries[$i['series_id']])) $itemsBySeries[$i['series_id']] = array();
			$itemsBySeries[$i['series_id']][] = $i;
		}

		return $itemsBySeries;
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){

		$itemsIds = array_map('intval', $newData['items']);
		foreach($itemsIds as $n => $id){
			if(!$id) unset($itemsIds[$n]);
		}

		if(count($itemsIds)){

			$discount = Catalog::percent2num($newData['discount'], Catalog::PC_DECREASE);

			$oItems = new Catalog_Items();
			$oItems->updCond(
				'`id` IN (' . implode(',', $itemsIds) . ')',
				array(
					'discount'	=> $discount
				)
			);

			// Автозаметки
			$oSeries = new Catalog_Series();
			foreach($itemsIds as $itemsId){
				$oSeries->makeNotes(Catalog_Series::NOTE_ITEM_DISCOUNT, 0, $itemsId);
			}

			Pages::flash(
				'Скидка ' . $newData['discount'] . '% установлена для ' . count($itemsIds) . ' товаров',
				false,
				Url::a('admin-catalog-discounts')
			);
			exit;

		}else{
			Pages::flash(
				'Не выбраны товары для установки скидки',
				true,
				Url::a('admin-catalog-discounts')
			);
			exit;
		}
	}
}

?>