<?php

/** Админка: Серии мебели
 * @author  Seka
 */

class mSeries extends Admin
{

    static $output = OUTPUT_DEFAULT;

    /**
     * @var int
     */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Series';

    /**
     * @var int
     */
    var $rights = Administrators::R_CATALOG;

    /**
     *
     */
    const ON_PAGE_DEFAULT = 50;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();
        $o->getOperations();

        if (intval($_GET['clear-notes'])) {
            self::clearAllNotes();
            exit;
        }
        if (isset($_GET['upd-filter'])) {
            return (new mSeries)->getSuppliersAndMaterialsByCategory(intval($_GET['upd-filter']));
        }

        if (isset($_GET['supplier_id'])) {
            $_GET['supplier_id'] = $_GET['supplier_id'] !== "" ? (int)$_GET['supplier_id'] : "";
        } else {
            $_GET['supplier_id'] = "";
        }

        if (isset($_GET['material_id'])) {
            $_GET['material_id'] = $_GET['material_id'] !== "" ? (int)$_GET['material_id'] : "";
        } else {
            $_GET['material_id'] = "";
        }

        $_GET['on_page'] = isset($_GET['on_page']) ? (int)$_GET['on_page'] : 50;

        $_GET['have_comments'] = isset($_GET['have_comments']) && $_GET['have_comments'] == "Y";

        // Поиск серий
        $search = [];

        if (isset($_GET['id'])) {

            $sId = trim($_GET['id']);
            $str = mb_strtolower($_GET['id']);

            // Поиск по ID
            if (substr($str, 0, 3) == "id:" && intval(substr($str, 3, strlen($str)))) {
                $search[] = '`id` = ' . intval(str_replace('id:', '', $sId));
            } else {
                $search[] = '(LOWER(`name`) LIKE \'%' . MYSQL::mres($sId) . '%\' OR LOWER(`url`) LIKE \'%' . MYSQL::mres($sId) . '%\')';
            }
        }

        if ($catId = intval($_GET['category_id'])) {
            // Поиск по категории
            $search[] = '`category_id` = ' . $catId;
        }

        if (trim($_GET['supplier_id']) !== '') {
            // Поиск по поставщику
            $search[] = '`supplier_id` = ' . intval($_GET['supplier_id']);
        }

        if ($matId = intval($_GET['material_id'])) {
            // Поиск по материалу
            $oSeries2Materials = new Catalog_Series_2Materials();
            $sIds = $oSeries2Materials->getCol(
                'series_id',
                '`material_id` = ' . $matId
            );
            $sIds = array_unique($sIds);
            $sIds[] = 0;
            $search[] = '`id` IN (' . implode(',', $sIds) . ')';
        }
        if (!isset($_GET['out_of_production']) || trim($_GET['out_of_production']) === '0') {
            $search[] = '`out_of_production` = 0';
        } elseif (trim($_GET['out_of_production']) === '1') {
            $search[] = '`out_of_production` = 1';
        }

        if ($_GET['have_comments']) {
            $search[] = "`admin_comment` <> ''";
        }

        if (!$onPage = intval($_GET['on_page'])) {
            $onPage = self::ON_PAGE_DEFAULT;
        }

        // Получаем список
        $oSeries = new Catalog_Series();

        list($series, $toggle) = $oSeries->getByPage(
            intval($_GET['page']),
            $onPage,
            '*',
            implode(' AND ', $search),
            'order'
        );

        $series = $oSeries->details($series);

        // Для каждой серии получаем к-во товаров, фоток, значения фильтров, отзывов
        $sIds = array();

        foreach ($series as $s) {
            $sIds[] = $s['id'];
        }

        if (count($sIds)) {
            // Товары
            $oItems = new Catalog_Items();
            $itemsCnt = $oItems->getHash(
                'series_id, COUNT(*)',
                '`series_id` IN (' . implode(',', $sIds) . ')',
                '',
                0,
                '',
                'series_id'
            );

            // Фотки
            $oPhotos = new Catalog_Series_Photos();
            $photosCnt = $oPhotos->getHash(
                'series_id, COUNT(*)',
                '`series_id` IN (' . implode(',', $sIds) . ')',
                '',
                0,
                '',
                'series_id'
            );

            // Отзывы
            $oReviews = new Reviews();
            $reviewsCnt = $oReviews->getHash(
                'object_id, COUNT(*)',
                '`object` = \'' . Reviews::OBJ_SERIES . '\' AND `object_id` IN (' . implode(',', $sIds) . ')',
                '',
                0,
                '',
                'object_id'
            );

            // Отображаемые на стр. серии товары из других серий
            $oCrossItems = new Catalog_Series_CrossItems();
            $crossItems = $oCrossItems->getHash(
                'where_series_id, COUNT(*)',
                '`where_series_id` IN (' . implode(',', $sIds) . ')',
                '',
                0,
                '',
                'where_series_id'
            );

            foreach ($series as &$s) {
                $s['items_cnt'] = isset($itemsCnt[$s['id']]) ? intval($itemsCnt[$s['id']]) : 0;
                $s['photos_cnt'] = isset($photosCnt[$s['id']]) ? intval($photosCnt[$s['id']]) : 0;
                $s['reviews_cnt'] = isset($reviewsCnt[$s['id']]) ? intval($reviewsCnt[$s['id']]) : 0;

                $s['cross_items'] = isset($crossItems[$s['id']]) ? intval($crossItems[$s['id']]) : 0;
            }
            unset($s);
        }

        // Категории для поиска
        $oCategories = new Catalog_Categories();
        $catsIds = $oCategories->getFinishIds(0);

        // Поставщики для поиска
        $oSuppliers = new Catalog_Suppliers();
        $suppliers = $oSuppliers->getHash(
            'id, name',
            '',
            '`name` ASC'
        );

        // Материалы для поиска
        $oMaterials = new Catalog_Materials();
        $materials = $oMaterials->get(
            '*',
            '`parent_id` = 0',
            'order'
        );

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);

        return pattExeP(fgc($tpl), array(
            'series' => $series,
            'toggle' => $toggle,
            'catsIds' => $catsIds,
            'suppliers' => $suppliers,
            'materials' => $materials,
            'is_admin' => $pageInf['admin'],
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId)
    {
        $oSeries = new Catalog_Series();
        $oSeries->del(intval($iId));
        Pages::flash('Серия мебели успешно удалена.');
    }


    /**
     *
     */
    static function clearAllNotes()
    {
        $oSeries = new Catalog_Series();
        $oSeries->updCond(
            '',
            array(
                'admin_notes' => ''
            )
        );
        Pages::flash('Заметки администратора для всех серий очищены');
    }


    /**
     * @param int $catId
     * @return    array    array('suppliers'    => array(hash), 'materials'    => array(hash));
     */
    function getSuppliersAndMaterialsByCategory($catId)
    {
        self::$output = OUTPUT_JSON;

        $catId = intval($catId);

        if ($catId) {
            $oSeries = new Catalog_Series();
            $seriesIds2SuppIds = $oSeries->getHash(
                'id, supplier_id',
                '`category_id` = ' . $catId
            );
            if (count($seriesIds2SuppIds)) {
                // Поставщики
                $oSuppliers = new Catalog_Suppliers();
                $suppliers = $oSuppliers->getHash(
                    'id, name',
                    '`id` IN (' . implode(',', array_unique($seriesIds2SuppIds)) . ')',
                    '`name` ASC'
                );

                // Материалы
                $oSeries2Materials = new Catalog_Series_2Materials();
                $mIds = $oSeries2Materials->getCol(
                    'material_id',
                    '`series_id` IN (' . implode(',', array_keys($seriesIds2SuppIds)) . ')'
                );
                if (count($mIds)) {
                    $oMaterials = new Catalog_Materials();
                    $materials = $oMaterials->getWhtKeys(
                        'id, name, supplier_id',
                        '`id` IN (' . implode(',', $mIds) . ') AND `parent_id` = 0',
                        'order'
                    );
                    $sIds = array();
                    foreach ($materials as $m) {
                        $sIds[] = intval($m['supplier_id']);
                    }
                    if (count($sIds)) {
                        $sNames = $oSuppliers->getHash(
                            'id, name',
                            '`id` IN (' . implode(',', array_unique($sIds)) . ')'
                        );
                        foreach ($materials as &$m) {
                            $s = isset($sNames[$m['supplier_id']]) ? ' (' . $sNames[$m['supplier_id']] . ')' : '';
                            $m = $m['name'] . $s;
                        }
                        unset($m);
                    }
                } else {
                    $materials = array();
                }
            } else {
                $suppliers = array();
                $materials = array();
            }
        } else {
            $oSuppliers = new Catalog_Suppliers();
            $suppliers = $oSuppliers->getHash(
                'id, name',
                '',
                '`name` ASC'
            );


            $oMaterials = new Catalog_Materials();
            $materials = $oMaterials->getWhtKeys(
                'id, name, supplier_id',
                '`parent_id` = 0',
                'order'
            );
            $sIds = array();
            foreach ($materials as $m) {
                $sIds[] = intval($m['supplier_id']);
            }
            if (count($sIds)) {
                $sNames = $oSuppliers->getHash(
                    'id, name',
                    '`id` IN (' . implode(',', array_unique($sIds)) . ')'
                );
                foreach ($materials as &$m) {
                    $s = isset($sNames[$m['supplier_id']]) ? ' (' . $sNames[$m['supplier_id']] . ')' : '';
                    $m = $m['name'] . $s;
                }
                unset($m);
            }
        }


        return array(
            'suppliers' => $suppliers,
            'materials' => $materials
        );
    }
}
