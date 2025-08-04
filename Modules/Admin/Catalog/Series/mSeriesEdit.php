<?php

/** Админка: добавление / редактирование серий мебели
 * @author    Seka
 */


class mSeriesEdit extends Admin
{

    /**
     * @var int
     */
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
     * @var int
     */
    static $pId;

    /**
     * @var int
     */
    static $catId = 0;

    /**
     * @var array
     */
    static $usdCourses = array();


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();

        // Получаем все конечные категории
        $oCategories = new Catalog_Categories();
        $catsIds = $oCategories->getFinishIds(0);

        $oSeries = new Catalog_Series();
        $oSeriesOptions = new Catalog_Series_Options();
        $oItems = new Catalog_Items();

        self::$pId = intval($_GET['id']);

        if (isset($_GET['series-by-supp'])) {
            self::$output = OUTPUT_JSON;
            return self::seriesByCats4Links($_GET['series-by-supp']);
        }

        if (self::$pId) {
            // Редактирование
            $init = $oSeries->getRow('*', '`id` = ' . self::$pId);

            if ($init === false) {
                Pages::flash('Запрошенная для редактирования серия не найдена.', true, self::retURL());
                exit;
            }
            self::$catId = $init['category_id'];

            $init['extra_charge'] = Catalog::num2percent($init['extra_charge'], Catalog::PC_INCREASE);

            // Значения фильтров
            /*$oSeries2Filters = new Catalog_Series_2Filters();
            $init['filters'] = $oSeries2Filters->getCol(
                'value_id',
                '`series_id` = ' . self::$pId
            );*/

            // Характеристики серии
            /*$init['options'] = $oSeriesOptions->get(
                'name, value',
                '`series_id` = ' . self::$pId,
                'order'
            );*/
            $oOpts4Series = new Catalog_Categories_Opts4Series();
            $catOptions = $oOpts4Series->getCol(
                'name',
                '`category_id` = ' . self::$catId,
                'order'
            );
            $sOptions = $oSeriesOptions->getHash(
                'name, value',
                '`series_id` = ' . self::$pId,
                'order'
            );
            $init['options'] = array();
            foreach ($catOptions as $coName) {
                if (isset($sOptions[$coName])) {
                    $init['options'][] = array(
                        'name' => $coName,
                        'value' => $sOptions[$coName],
                        'fixed' => 1
                    );
                    unset($sOptions[$coName]);
                } else {
                    $init['options'][] = array(
                        'name' => $coName,
                        'fixed' => 1
                    );
                }
            }
            foreach ($sOptions as $oName => $oVal) {
                $init['options'][] = array(
                    'name' => $oName,
                    'value' => $oVal,
                    'fixed' => 0
                );
            }

            // Материалы
            $oSeries2Materials = new Catalog_Series_2Materials();
            $init['materials'] = $oSeries2Materials->getCol(
                'material_id',
                '`series_id` = ' . self::$pId
            );

            // Линковки
            $oSeriesLinkage2Series = new Catalog_Series_Linkage_2Series();
            $tmp = $oSeriesLinkage2Series->get(
                'linkage_id, series2_id',
                '`series1_id` = ' . self::$pId
            );
            $init['linkage'] = array();
            foreach ($tmp as $l) {
                if (!is_array($init['linkage'][$l['linkage_id']])) {
                    $init['linkage'][$l['linkage_id']] = array();
                }
                $init['linkage'][$l['linkage_id']][] = $l['series2_id'];
            }
            $tmp = $oSeriesLinkage2Series->get(
                'linkage_id, series1_id',
                '`series2_id` = ' . self::$pId
            );
            foreach ($tmp as $l) {
                $init['linkage'][$l['linkage_id']][] = $l['series1_id'];
            }
            foreach ($init['linkage'] as &$sIds) {
                $sIds = array_unique($sIds);
            }
            unset($sIds);

            // Товары "В комлекте"
            $oCrossItems = new Catalog_Series_CrossItems();
            $crossItemsIds = $oCrossItems->getItemsIds(self::$pId, false);
            $crossItemsIds[] = 0;
            $items = $oItems->getWhtKeys(
                'id, name, art, series_id',
                '`series_id` = ' . self::$pId . ' OR `id` IN (' . implode(',', $crossItemsIds) . ')',
                'order'
            );
            $oSetItems = new Catalog_Series_SetItems();
            $tmp = $oSetItems->get(
                '*',
                '`series_id` = ' . self::$pId
            );
            /*$tmp = $oItems->get(
                'id, on_series_photo, in_series_set_amount, on_series_photo_x, on_series_photo_y',
                '`series_id` = ' . self::$pId . ' AND `in_series_set` = 1'
            );*/
            $init['items-set'] = array();
            $itemsOnPhoto = array();
            $itemsInSetAmounts = array();
            foreach ($tmp as $i) {
                $init['items-set'][] = $i['item_id'];
                $itemsInSetAmounts[$i['item_id']] = $i['amount'];
                if ($i['on_photo']) {
                    $itemsOnPhoto[$i['item_id']] = array(
                        $i['on_photo_x'],
                        $i['on_photo_y']
                    );
                }
            }

            // 1-я фотография серии для расстановки на ней точек
            $oPhotos = new Catalog_Series_Photos();
            $photoDb = $oPhotos->getRow(
                '*',
                '`series_id` = ' . self::$pId,
                'order'
            );
            $photo0 = is_array($photoDb) ? $oPhotos->imageExtToData($photoDb) : [];

            // С какими теговыми страницами связана серия
            $oSeries2Pages = new Catalog_Series_2Pages();
            $init['pages'] = $oSeries2Pages->getCol('page_id', '`series_id` = ' . self::$pId);

            if (!intval($init['usd_course_id']) && abs($init['usd_course'])) {
                $init['usd_course_id'] = '-1';
            }
        } else {
            // Добавление
            // Находим категорию, в которую добавляется серия
            self::$catId = intval($_GET['category_id']);
            $catInf = $oCategories->getRow('*', '`id` = ' . self::$catId);
            if (!$catInf || $catInf['has_subcats']) {
                // Неподходящая для серий категория
                // Показываем форму выбора конечной категории
                $tpl = Pages::tplFile($pageInf, 'catsel');
                return pattExeP(fgc($tpl), array(
                    'catInf' => $catInf,
                    'catsIds' => $catsIds
                ));
            }

            // Исходные данные для формы
            $init = array(
                'category_id' => self::$catId,
                'in_stock' => 1,
                'rate' => 5,
                'usd_course_id' => '0',
                'usd_course' => '0.00',
                'extra_charge' => '0',

                'extra_charge_action' => Catalog_Series::EXTRA_ACTION_REPL_PART,
                //'import_extra_formula'	=> Catalog_Series::IMPORT_FORMULA_EXTRA
            );

            // В левую часть таблицы характеристик серии вписываем их названия, заранее определённые для категории
            $oOpts4Series = new Catalog_Categories_Opts4Series();
            $options = $oOpts4Series->getCol(
                'name',
                '`category_id` = ' . self::$catId,
                'order'
            );
            $init['options'] = $oOpts4Series->get(
                '`name`, 1 AS `fixed`',
                '`category_id` = ' . self::$catId,
                'order'
            );

            $items = array();

            $itemsOnPhoto = array();
            $itemsInSetAmounts = array();
            $photo0 = false;
        }

        // Поставщики
        $oSuppliers = new Catalog_Suppliers();
        $suppliers = $oSuppliers->getHash(
            'id, name',
            '',
            '`name` ASC'
        );

        // Все доступные маркеры
        $oMarkers = new Catalog_Markers();
        $markers = $oMarkers->get('*');

        // Группы товаров
        $oItemsGroups = new Catalog_Items_Groups();
        if (self::$pId) {
            $groups = $oItemsGroups->getForSeries(self::$pId, false, true);
        } else {
            $groups = $oItemsGroups->getForCat(self::$catId);
        }
        $init['groups-h2'] = array();
        foreach ($groups as $g) {
            if (intval($g['h2'])) {
                $init['groups-h2'][] = $g['id'];
            }
        }

        // Все использованные варианты названий и значений для характеристик серий
        $optionsValues = $oSeriesOptions->getCol('value', '', 'value', 1000, '', 'value');

        // Материалы (верхнего уровня)
        $oMaterials = new Catalog_Materials();
        $materials = $oMaterials->imageExtToData(
            $oMaterials->getWhtKeys(
                '*',
                '`parent_id` = 0',
                'order'
            )
        );

        // Группы линковок и серии для них
        $oLinkage = new Catalog_Series_Linkage();
        $linkages = $oLinkage->get(
            '*',
            '',
            'order'
        );
        $seriesByCats4Links = self::seriesByCats4Links(0);

        // Группы теговых страниц
        $pgCatsIds = $oCategories->getChainFields(self::$catId, 'id');
        $pgCatsIds[] = 0;
        $oPagesGroups = new Catalog_Pages_Groups();
        $pagesGroups = $oPagesGroups->get(
            'id, name',
            '`category_id` IN (' . implode(',', $pgCatsIds) . ')',
            'order'
        );
        $oPages = new Catalog_Pages();
        foreach ($pagesGroups as $n => &$pg) {
            $pg['pages'] = $oPages->get(
                'id, name',
                '`group_id` = ' . $pg['id'],
                'order'
            );
            if (!count($pg['pages'])) {
                unset($pagesGroups[$n]);
            }
        }
        unset($pg);

        // Список доп. курсов валют
        $oUsdCourses = new Catalog_UsdCourses();
        self::$usdCourses = $oUsdCourses->getWhtKeys(
            '*',
            '',
            'name'
        );

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'init' => $init,
            'catId' => self::$catId,
            'catsIds' => $catsIds,

            'suppliers' => $suppliers,
            'markers' => $markers,
            //'filters'	=> $filters,
            //'optionsNames'	=> $optionsNames,
            'optionsValues' => $optionsValues,
            'materials' => $materials,
            'linkages' => $linkages,
            'seriesByCats4Links' => $seriesByCats4Links,
            'items' => $items,
            'itemsOnPhoto' => $itemsOnPhoto,
            'itemsInSetAmounts' => $itemsInSetAmounts,
            'photo0' => $photo0,
            'groups' => $groups,

            'pagesGroups' => $pagesGroups,

            'usdCourses' => self::$usdCourses,

            'is_admin' => $pageInf['admin'],
        ));

        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        $frm->setInit($init);
        $r = $frm->run('mSeriesEdit::save', 'mSeriesEdit::check');

        return $r;
    }


    /** Получаем URL для возврата к списку серий
     * @static
     * @return string
     */
    static function retURL()
    {
        if (isset($_GET['ret']) && trim($_GET['ret'])) {
            return $_GET['ret'];
        } else {
            return Url::a('admin-catalog-series') . '?category_id=' . self::$catId;
        }
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        // Проверка уникальности URL
        $_POST['url'] = mb_strtolower(trim($_POST['url']));
        $_POST['category_id'] = intval($_POST['category_id']);
        $oSeries = new Catalog_Series();
        if ($oSeries->getCount(
            '`url` = \'' . MySQL::mres(
                $_POST['url']
            ) . '\' AND `category_id` = ' . $_POST['category_id'] . ' AND `id` != ' . intval(self::$pId)
        )) {
            return array(
                array(
                    'name' => 'url',
                    'msg' => 'Указанный URL используется для другой серии.'
                )
            );
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        $rate = abs($newData['rate']);
        if ($rate > 5) {
            $rate = 5;
        }


        // Данные для сохранения
        $save = array(
            'name' => $newData['name'],
            //'comment'	=> $newData['comment'],
            'url' => $newData['url'],
            'in_stock' => intval($newData['in_stock']) ? 1 : 0,
            // 'bilet_status'		=> $newData['bilet_status'],
            'out_of_production' => intval($newData['out_of_production']) ? 1 : 0,
            'rate' => $rate,

            'title' => $newData['title'],
            'h1' => $newData['h1'],
            'kwrd' => $newData['kwrd'],
            'dscr' => $newData['dscr'],
            'text' => $newData['text'],
            'video' => $newData['video'],
            'in_cats_list' => intval($newData['in_cats_list']) ? 1 : 0,

            'delivery_time' => $newData['delivery_time'],
            'price_search_group_id' => intval($newData['price_search_group_id']),
            //'usd_course'	=> abs($newData['usd_course']),

            //'extra_charge_action'	=> $newData['extra_charge_action'],
            'extra_charge_action' => Catalog_Series::EXTRA_ACTION_REPL_ALL,
            //'import_extra_formula'	=> $newData['import_extra_formula'],
            'import_extra_formula' => Catalog_Series::IMPORT_FORMULA_OUT,

            'marker_id' => intval($newData['marker_id']),
            'supplier_id' => intval($newData['supplier_id']),
            'category_id' => intval($newData['category_id'])
        );

        if (isset($newData['admin_comment'])) {
            $admin_comment = $newData['admin_comment'];
            $admin_comment = trim(strip_tags($admin_comment));
            $save['admin_comment'] = $admin_comment;
        }

        if (abs($newData['usd_course_id']) == -1) {
            $save['usd_course_id'] = 0;
            $save['usd_course'] = abs($newData['usd_course']);
        } elseif (abs($newData['usd_course_id']) == 0) {
            $save['usd_course_id'] = 0;
            $save['usd_course'] = 0;
        } else {
            $save['usd_course_id'] = intval($newData['usd_course_id']);
            $save['usd_course'] = self::$usdCourses[$save['usd_course_id']]['course'];
        }

        $oldExtraCharge = Catalog::percent2num($initData['extra_charge'], Catalog::PC_INCREASE);
        $newExtraCharge = Catalog::percent2num($newData['extra_charge'], Catalog::PC_INCREASE);

        $oSeries = new Catalog_Series();

        if (self::$pId) {
            // Редактирование
            $oSeries->upd(
                self::$pId,
                array(
                    'admin_notes' => $newData['admin_notes']
                )
            );

            // Автозаметки
            self::autoNotesOnEdit($initData, $newData, $save);

            // Сохранение
            $edit = true;
            $oSeries->upd(self::$pId, $save);

            if ($oldExtraCharge != $newExtraCharge) {
                $oSeries->updateExtraCharge(
                    self::$pId,
                    $newExtraCharge
                );
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_EXTRA, self::$pId);
            }

            $msg = 'Изменения сохранены.';
        } else {
            // Добавление
            $edit = false;
            $save['extra_charge'] = $newExtraCharge;

            self::$pId = $oSeries->add($save);

            $msg = 'Серия успешно добавлена.';

            // Автозаметка
            $oSeries->makeNotes(Catalog_Series::NOTE_SERIES_CREATE, self::$pId);
        }

        // Сохраняем характеристики серии
        $oSeriesOptions = new Catalog_Series_Options();
        if ($edit) {
            $oSeriesOptions->delCond('`series_id` = ' . self::$pId);
        }
        foreach ($newData['options'] as $o) {
            $n = trim($o['name']);
            $v = trim($o['value']);
            if ($n !== '' && $v !== '') {
                $oSeriesOptions->add(array(
                    'series_id' => self::$pId,
                    'name' => $n,
                    'value' => $v
                ));
            }
        }

        // Сохраняем материалы
        $oSeries2Materials = new Catalog_Series_2Materials();
        if (!is_array($newData['materials'])) {
            $newData['materials'] = array();
        }
        $newMatIds = array_unique(array_map('intval', $newData['materials']));
        if (count($newMatIds)) {
            if ($edit) {
                $oSeries2Materials->delCond(
                    '`series_id` = ' . self::$pId . ' AND `material_id` NOT IN (' . implode(',', $newMatIds) . ')'
                );
                $existsMatIds = $oSeries2Materials->getCol(
                    'material_id',
                    '`series_id` = ' . self::$pId . ' AND `material_id` IN (' . implode(',', $newMatIds) . ')'
                );
                $existsMatIds = array_map('intval', $existsMatIds);
            } else {
                $existsMatIds = array();
            }
            foreach ($newMatIds as $mId) {
                if ($mId && !in_array($mId, $existsMatIds)) {
                    $oSeries2Materials->add(array(
                        'series_id' => self::$pId,
                        'material_id' => $mId
                    ));
                }
            }
        } elseif ($edit) {
            $oSeries2Materials->delCond('`series_id` = ' . self::$pId);
        }

        // Сохраняем линковки
        $oSeriesLinkage2Series = new Catalog_Series_Linkage_2Series();
        if ($edit) {
            $oSeriesLinkage2Series->delCond('`series1_id` = ' . self::$pId);
            $oSeriesLinkage2Series->delCond('`series2_id` = ' . self::$pId);    // Для двусторонней связи
        }
        foreach ($newData['linkage'] as $lId => $sIds) {
            foreach ($sIds as $sId) {
                $oSeriesLinkage2Series->add(array(
                    'linkage_id' => $lId,
                    'series1_id' => self::$pId,
                    'series2_id' => intval($sId)
                ));
                $oSeriesLinkage2Series->add(array(    // Для двусторонней связи
                    'linkage_id' => $lId,
                    'series1_id' => intval($sId),
                    'series2_id' => self::$pId
                ));
            }
        }

        // Сохраняем товары "В базовом комплекте"
        if ($edit) {
            $oSetItems = new Catalog_Series_SetItems();
            $oSetItems->delCond(
                '`series_id` = ' . self::$pId
            );

            $newData['items-set'] = is_null($newData['items-set'])
                ? []
                : array_unique(array_map('intval', $newData['items-set']));

            foreach ($newData['items-set'] as $iId) {
                $onPhoto = intval($newData['items-on-photo'][$iId]) ? 1 : 0;
                if ($onPhoto) {
                    $onPhotoX = intval($newData['items-on-photo-x'][$iId]);
                    $onPhotoY = intval($newData['items-on-photo-y'][$iId]);
                } else {
                    $onPhotoX = 0;
                    $onPhotoY = 0;
                }
                $amount = abs(intval($newData['items-in-set-amounts'][$iId]));
                if (!$amount) {
                    $amount = 1;
                }

                $oSetItems->add(array(
                    'series_id' => self::$pId,
                    'item_id' => $iId,
                    'amount' => $amount,
                    'on_photo' => $onPhoto,
                    'on_photo_x' => $onPhotoX,
                    'on_photo_y' => $onPhotoY
                ));
            }
        }

        // Сохраняем связь с теговыми страницами
        $oSeries2Pages = new Catalog_Series_2Pages();
        if ($edit) {
            $oSeries2Pages->delCond('`series_id` = ' . self::$pId);
        }
        $pagesIds = $newData['pages'] ? array_unique(array_map('intval', $newData['pages'])) : [];
        foreach ($pagesIds as $pgId) {
            if ($pgId) {
                $oSeries2Pages->add(array(
                    'series_id' => self::$pId,
                    'page_id' => $pgId
                ));
            }
        }


        // Сохраняем пометки, какие группы товаров нужно выводить заголовками H2 и к каким группам надо дописать название серии
        $oItemsGroupsOptions = new Catalog_Items_Groups_Options();
        if ($edit) {
            $oItemsGroupsOptions->delCond('`series_id` = ' . self::$pId);
        }
        $oItemsGroups = new Catalog_Items_Groups();
        $gIds = is_array($newData['groups-h2']) ? array_unique(array_map('intval', $newData['groups-h2'])) : array();
        foreach ($gIds as $gId) {
            $oItemsGroupsOptions->add(array(
                'group_id' => $gId,
                'series_id' => self::$pId,
                'h2' => 1
            ));
        }


        // Ключевые слова
        $oSeries->updateKeywords(self::$pId);

        Pages::flash($msg, false, self::retURL());
    }


    /** генерация автозаметок при редактировании товара
     * @static
     * @param $initData
     * @param $newData
     * @param $saveData
     */
    static function autoNotesOnEdit($initData, $newData, $saveData)
    {
        $oSeries = new Catalog_Series();

        // Отслеживанием изменение обычных параметров серии
        $note = false;
        $flds = array(
            'name',
            'title',
            'h1',
            'dscr',
            'kwrd',
            'text',
            'video',
            'url',
            'delivery_time',
            'supplier_id',
            'category_id',
            'import_extra_formula',
            'price_search_group_id'
        );
        foreach ($flds as $fld) {
            if ((string)$initData[$fld] !== (string)$saveData[$fld]) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_SERIES_CHANGE, self::$pId);
                $note = true;
                break;
            }
        }
        if (!$note) {
            // Проверяем изменения в составе материалов
            $oSeries2Materials = new Catalog_Series_2Materials();
            $oldMatsIds = $oSeries2Materials->getCol('material_id', '`series_id` = ' . self::$pId);
            $oldMatsIds = array_map('intval', $oldMatsIds);
            sort($oldMatsIds);

            $newMatsIds = array_unique(array_map('intval', $newData['materials']));
            sort($newMatsIds);

            if (implode('-', $oldMatsIds) !== implode('-', $newMatsIds)) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_SERIES_CHANGE, self::$pId);
            }
        }

        // Проверяем изменения курса валют
        if ((float)$initData['usd_course'] != (float)$saveData['usd_course']) {
            // Автозаметка
            $oSeries->makeNotes(Catalog_Series::NOTE_SERIES_USD, self::$pId);
        }
    }


    /**
     * @param $suppId
     * @return array
     */
    static function seriesByCats4Links($suppId)
    {
        $suppId = intval($suppId);

        $oSeries = new Catalog_Series();
        $tmp = $oSeries->get(
            'id, name, category_id',
            '`id` != ' . self::$pId . ($suppId ? ' AND `supplier_id` = ' . $suppId : '') . ' AND `out_of_production` = 0',
            'order'
        );
        $seriesByCats4Links = array();
        foreach ($tmp as $s) {
            if (!is_array($seriesByCats4Links[$s['category_id']])) {
                $seriesByCats4Links[$s['category_id']] = array();
            }
            $seriesByCats4Links[$s['category_id']][] = $s;
        }
        return $seriesByCats4Links;
    }
}
