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
                intval($_GET['series_id']),
                intval($_GET['include_out_of_production'] ?? 0)
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
            $init = $_SESSION['export-options'];
            $init['items'] = 1;
            $frm->setInit($init);
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
    static function calc($categoryId, $supplierId, $seriesId, $includeOutOfProduction = 0)
    {
        self::$output = OUTPUT_FRAME;

        $cond = self::seriesSearchCond($categoryId, $supplierId, $seriesId, $includeOutOfProduction);
        $oSeries = new Catalog_Series();
        $seriesIds = $oSeries->getCol('id', $cond);
        $seriesCnt = count($seriesIds);

        if ($seriesCnt) {
            $oItems = new Catalog_Items();
            $itemsCnt = $oItems->getCount('`series_id` IN (' . implode(',', $seriesIds) . ')');
        } else {
            $itemsCnt = 0;
        }

        return json_encode(array(
            'series' => $seriesCnt,
            'items' => intval($itemsCnt)
        ));
    }

    /**
     * @param $initData
     * @param $newData
     */
    static function export($initData, $newData)
    {
        Catalog_Prices_Export::prepareRuntime();

        $_SESSION['export-options'] = array(
            'options' => isset($newData['options']) ? $newData['options'] : array(),
            'items' => intval($newData['items'] ?? 0) ? 1 : 0,
            'series-extra-formula' => intval($newData['series-extra-formula'] ?? 0) ? 1 : 0,
            'include_out_of_production' => intval($newData['include_out_of_production'] ?? 0) ? 1 : 0
        );

        $optSeries = (isset($newData['options']['series']) && is_array($newData['options']['series']))
            ? $newData['options']['series']
            : array();
        $optSeries[] = Catalog_Prices::FLD_SERIES_ID;
        $optSeries[] = Catalog_Prices::FLD_SERIES_CATEGORY;
        $optSeries[] = Catalog_Prices::FLD_SERIES_NAME;
        $seriesExtraFormula = !empty($newData['series-extra-formula']);

        if (!empty($newData['items'])) {
            $optItems = (isset($newData['options']['items']) && is_array($newData['options']['items']))
                ? $newData['options']['items']
                : array();
            $optItems[] = Catalog_Prices::FLD_ITEMS_ID;
            $optItems[] = Catalog_Prices::FLD_ITEMS_NAME;
            $optItems[] = Catalog_Prices::FLD_ITEMS_ART;
        } else {
            $optItems = array();
        }

        $q = self::seriesSearchCond(
            intval($newData['category_id'] ?? 0),
            intval($newData['supplier_id'] ?? 0),
            intval($newData['series_id'] ?? 0),
            intval($newData['include_out_of_production'] ?? 0)
        );

        $params = array(
            'query' => $q,
            'optSeries' => array_values($optSeries),
            'optItems' => array_values($optItems),
            'seriesExtraFormula' => $seriesExtraFormula
        );

        try {
            $oExport = new Catalog_Prices_Export();
            $filePath = $oExport->exportToFile(
                $params['query'],
                $params['optSeries'],
                $params['optItems'],
                $params['seriesExtraFormula']
            );
            header('Location: /xls/' . basename($filePath));
            exit;
        } catch (Throwable $e) {
            Pages::flash('Ошибка экспорта: ' . $e->getMessage(), true);
        }
    }

    /**
     * Условие выборки серий для подсчёта и экспорта.
     */
    private static function seriesSearchCond($categoryId, $supplierId, $seriesId, $includeOutOfProduction = 0): string
    {
        $categoryId = intval($categoryId);
        $supplierId = intval($supplierId);
        $seriesId = intval($seriesId);
        $q = array();

        if (!intval($includeOutOfProduction)) {
            $q[] = '`out_of_production` = 0';
        }

        if ($categoryId) {
            $oCategories = new Catalog_Categories();
            $ids = $oCategories->getFinishIds($categoryId);
            $ids[] = $categoryId;
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (count($ids) === 1) {
                $q[] = '`category_id` = ' . $ids[0];
            } elseif (count($ids)) {
                $q[] = '`category_id` IN (' . implode(',', $ids) . ')';
            }
        }
        if ($supplierId) {
            $q[] = '`supplier_id` = ' . $supplierId;
        }
        if ($seriesId) {
            $q[] = '`id` = ' . $seriesId;
        }

        return implode(' AND ', $q);
    }
}
