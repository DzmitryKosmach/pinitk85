<?php

/** Админка: экспорт прайса
 * @author    Seka
 */

class mExport extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var int
     */
    static $output = OUTPUT_DEFAULT;

    /**
     * @var int
     */
    var $rights = Administrators::R_CATALOG;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        if (intval($_GET['calc'])) {
            // Запрос на подсчёт к-ва товаров по заданным параметрам
            return self::calc(
                intval($_GET['category_id']),
                intval($_GET['supplier_id']),
                intval($_GET['series_id'])
            );
        }

        // Серии (по категориям и по поставщикам)
        $oSeries = new Catalog_Series();
        $series = $oSeries->get(
            'id, name, category_id, supplier_id',
            '',
            'order'
        );

        $seriesByCat = array();        // $seriesByCat[category_id][supplier_id][series_id] = series_name
        foreach ($series as $s) {
            $cat = $s['category_id'];
            $sup = $s['supplier_id'];
            if (!isset($seriesByCat[$cat])) {
                $seriesByCat[$cat] = array();
            }
            if (!isset($seriesByCat[$cat][$sup])) {
                $seriesByCat[$cat][$sup] = array();
            }
            $seriesByCat[$cat][$sup][$s['id']] = $s['name'];
        }

        // Конечные категории каталога
        $oCategories = new Catalog_Categories();
        $catsIds = $oCategories->getFinishIds(0);

        // Поставщики
        $oSuppliers = new Catalog_Suppliers();
        $suppliers = $oSuppliers->getHash(
            'id, name',
            '',
            'name'
        );


        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'seriesByCat' => $seriesByCat,
            'catsIds' => $catsIds,
            'suppliers' => $suppliers
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;

        if (isset($_SESSION['export-options']) && is_array($_SESSION['export-options'])) {
            $frm->setInit($_SESSION['export-options']);
        }

        return $frm->run('mExport::export');
    }


    /**
     * @static
     * @param int $categoryId
     * @param int $supplierId
     * @param int $seriesId
     * @return    int
     */
    static function calc($categoryId, $supplierId, $seriesId)
    {
        self::$output = OUTPUT_FRAME;

        $categoryId = intval($categoryId);
        $supplierId = intval($supplierId);
        $seriesId = intval($seriesId);

        // Поисковый запрос
        $q = array('`out_of_production` = 0');
        if ($categoryId) {
            $q[] = '`category_id` = ' . $categoryId;
        }
        if ($supplierId) {
            $q[] = '`supplier_id` = ' . $supplierId;
        }
        if ($seriesId) {
            $q[] = '`id` = ' . $seriesId;
        }

        // Получаем ID серий
        $oSeries = new Catalog_Series();
        $seriesIds = $oSeries->getCol('id', implode(' AND ', $q));

        // Вычисляем к-во товаров
        if (count($seriesIds)) {
            $oItems = new Catalog_Items();
            $itemsCnt = $oItems->getCount('`series_id` IN (' . implode(',', $seriesIds) . ')');
        } else {
            $itemsCnt = 0;
        }
        return $itemsCnt;
    }

    /**
     * @param $initData
     * @param $newData
     */
    static function export($initData, $newData)
    {
        $_SESSION['export-options'] = array(
            'options' => $newData['options'],
            'items' => intval($newData['items']) ? 1 : 0,
            'series-extra-formula' => intval(intval($newData['series-extra-formula'])) ? 1 : 0
        );

        // Параметры выгрузки серий
        $optSeries = $newData['options']['series'];
        $optSeries[] = Catalog_Prices::FLD_SERIES_ID;
        $optSeries[] = Catalog_Prices::FLD_SERIES_CATEGORY;
        $optSeries[] = Catalog_Prices::FLD_SERIES_NAME;
        $seriesExtraFormula = intval($newData['series-extra-formula']) ? true : false;

        // Параметры выгрузки товаров
        if ($newData['items']) {
            $optItems = $newData['options']['items'];
            $optItems[] = Catalog_Prices::FLD_ITEMS_ID;
            $optItems[] = Catalog_Prices::FLD_ITEMS_NAME;
            $optItems[] = Catalog_Prices::FLD_ITEMS_ART;
        } else {
            $optItems = false;
        }

        // Поисковый запрос (для поиска серий)
        $q = array('`out_of_production` = 0');
        if ($categoryId = intval($newData['category_id'])) {
            $q[] = '`category_id` = ' . $categoryId;
        }
        if ($supplierId = intval($newData['supplier_id'])) {
            $q[] = '`supplier_id` = ' . $supplierId;
        }
        if ($seriesId = intval($newData['series_id'])) {
            $q[] = '`id` = ' . $seriesId;
        }

        // Экспортируем прайс
        $oExport = new Catalog_Prices_Export();
        $oExport->export(
            implode(' AND ', $q),
            (!$optSeries ? [] : $optSeries),
            (!$optItems ? [] : $optItems),
            $seriesExtraFormula
        );

        exit;
    }
}
