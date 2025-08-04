<?php

/** Админка: Товары в серии
 *
 * @author	Seka
 */

class mItems extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CATALOG;

	/**
	 * @var string
	 */
	var $mainClass = 'Catalog_Items';

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
		$o->getOperations();

		// Получаем инфу о серии
		$seriesId = intval($_GET['s']);
		$oSeries = new Catalog_Series();
        $seriesInf = $oSeries->getRow('*', '`id` = ' . $seriesId);
        if(!$seriesInf){
            Pages::flash('Не найдена серия для просмотра списка товаров.', true, self::retURL());
			exit;
        }

        // Получаем товары
		$oItems = new Catalog_Items();
        $items = $oItems->imageExtToData($oItems->get(
			'*',
			'`series_id` = ' . $seriesId,
			'order'
		));

		// Группы товаров
		$oItemsGroups = new Catalog_Items_Groups();
		$groups = $oItemsGroups->getHash(
			'id, name',
			'`category_id` = ' . $seriesInf['category_id'],
			'order'
		);

		// К-во отображаемых на стр. серии групп товаров и товаров из других серий
		$oCrossItems = new Catalog_Series_CrossItems();
		$crossItemsCnt = $oCrossItems->getCount(
			'`where_series_id` = ' . $seriesId . ' AND `item_id` != 0'
		);

        // Выводим страницу
        $tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'seriesInf'	=> $seriesInf,
			'items'		=> $items,
			'groups'	=> $groups,

			'crossItemsCnt'		=> $crossItemsCnt
        ));
    }


	/** Получаем URL для возврата к списку серий
	 * @static
	 * @return string
	 */
	static function retURL(){
		if(isset($_GET['ret']) && trim($_GET['ret'])){
			return $_GET['ret'];
		}else{
			return Url::a('admin-catalog-series');
		}
	}


    /** Удаление
     * @param $iId
     */
    function delItem($iId){
		$iId = intval($iId);

		// Автозаметка
		$oSeries = new Catalog_Series();
		$oSeries->makeNotes(Catalog_Series::NOTE_ITEM_REMOVE, 0, $iId);

		$oItems = new Catalog_Items();
		$oItems->del($iId);

        Pages::flash('Товар удалён.');
    }
}

?>