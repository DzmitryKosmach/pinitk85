<?php

/** Админка: Настройка отображения элементов серий прямо на странице категории
 * @author    Seka
 */


class mItemsLinks extends Admin
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
     * @var    ExtDbList
     */
    static $oLinksObj;

    /**
     * @var string
     */
    static $okMsg = 'Изменения сохранены';

    /**
     * @var string
     */
    static $ret = '';

    /**
     * @var string
     */
    static $placeCond = '';

    /**
     * @var array
     */
    static $placeData = array();

    /**
     * Можно выбирать только конкрнетные товары (не группы)
     * @var bool
     */
    static $itemsOnly = false;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();

        $pageId = intval($_GET['p']);
        $categoryId = intval($_GET['c']);
        $seriesId = intval($_GET['s']);
        $seriesGroupId = intval($_GET['g']);

        if ($pageId) {
            // Теговая страница
            $oPages = new Catalog_Pages();
            $pgInf = $oPages->getRow('*', '`id` = ' . $pageId);
            if (!$pgInf) {
                Pages::flash('Запрошенная теговая страница не найдена.', true, Url::a('admin-catalog-categories'));
                exit;
            }

            // Данные группы
            $oGroups = new Catalog_Pages_Groups();
            $pgGroupInf = $oGroups->getRow('*', '`id` = ' . $pgInf['group_id']);
            if (!$pgGroupInf) {
                Pages::flash(
                    'Запрошенная группа теговых страниц не найдена.',
                    true,
                    Url::a('admin-catalog-categories')
                );
                exit;
            }

            $categoryId = $pgGroupInf['category_id'];

            // Данные категории
            $oCategories = new Catalog_Categories();
            $categoryInf = $oCategories->getRow('*', '`id` = ' . $categoryId);
            if (!$categoryInf) {
                Pages::flash('Запрошенная категория не найдена.', true, Url::a('admin-catalog-categories'));
                exit;
            }
            /*if(intval($categoryInf['has_subcats'])){
                Pages::flash('В категории с подкатегориями нельзя отображать элементы серий.', true, Url::a('admin-catalog-categories'));
                exit;
            }*/

            // Описываем всё необходимое для настройки отображения товаров на ТЕГОВОЙ СТРАНИЦЕ
            $htmlh1 =
                'Настройка отображения элементов серий на теговой странице
				<b>&laquo;' . $pgGroupInf['name'] . ': ' . $pgInf['name'] . '&raquo;</b>
				в категории
				<b>&laquo;' . Catalog_Categories::name($categoryInf['id']) . '&raquo;</b>';
            $htmlComment =
                'Товары из выбранных ниже групп будут отображаться на данной теговой странице (как серии).<br>Одновременно эти же товары будут отображаться и на странице категории, к которой относится данная теговая страница.<br><br>
				<span class="red">Внимание! На странице категории (или на теговой странице) могут отображаться либо серии, либо товары.<br> Соответственно, если ниже будут выбраны какие-либо группы товаров, то серии в этой категории больше отображаться не будут.</span>';
            self::$okMsg = 'Настройки отображения элементов серий на теговой странице сохранены';
            self::$ret = Url::buildUrl(Url::a('admin-catalog-categories-groups-pages'), array(
                'g' => $pgInf['group_id']
            ));
            self::$placeCond = '`category_id` = ' . $categoryId . ' AND `page_id` = ' . $pageId;
            self::$placeData = array(
                'category_id' => $categoryId,
                'page_id' => $pageId
            );
        } elseif ($categoryId) {
            // Страница категории каталога
            $oCategories = new Catalog_Categories();
            $categoryInf = $oCategories->getRow('*', '`id` = ' . $categoryId);
            if (!$categoryInf) {
                Pages::flash('Запрошенная категория не найдена.', true, Url::a('admin-catalog-categories'));
                exit;
            }
            if (intval($categoryInf['has_subcats'])) {
                Pages::flash(
                    'В категории с подкатегориями нельзя отображать элементы серий.',
                    true,
                    Url::a('admin-catalog-categories')
                );
                exit;
            }

            // Описываем всё необходимое для настройки отображения товаров на СТРАНИЦЕ КАТЕГОРИИ
            $htmlh1 =
                'Настройка отображения элементов серий на странице категории
				<b>&laquo;' . Catalog_Categories::name($categoryInf['id']) . '&raquo;</b>';
            $htmlComment =
                'Товары из выбранных ниже групп будут отображаться на странице категории (как серии).<br>Для того, что эти или другие товары отображались на теговых страницах категории, необходимо сделать<br>соответствующие настройки для теговых страниц.<br><br>
				<span class="red">Внимание! На странице категории (или на теговой странице) могут отображаться либо серии, либо товары.<br> Соответственно, если ниже будут выбраны какие-либо группы товаров, то серии в этой категории больше отображаться не будут.</span>';
            self::$okMsg = 'Настройки отображения элементов серий на странице категории сохранены';
            self::$ret = Url::buildUrl(Url::a('admin-catalog-categories'), array(
                'p' => $categoryInf['parent_id']
            ));
            self::$placeCond = '`category_id` = ' . $categoryId . ' AND `page_id` = 0';
            self::$placeData = array(
                'category_id' => $categoryId,
                'page_id' => 0
            );
        } elseif ($seriesId) {
            // Страница серии
            self::$itemsOnly = true;

            $oSeries = new Catalog_Series();
            $seriesInf = $oSeries->getRow('*', '`id` = ' . $seriesId);
            if (!$seriesInf) {
                Pages::flash('Не найдена серия для настройки списка товаров.', true, Url::a('admin-catalog-series'));
                exit;
            }
            if ($seriesGroupId) {
                $oItemsGroups = new Catalog_Items_Groups();
                $seriesGroupInf = $oItemsGroups->getRow('*', '`id` = ' . $seriesGroupId);
                if (!$seriesGroupInf) {
                    Pages::flash(
                        'Не найдена группа товаров в серии для настройки списка товаров.',
                        true,
                        Url::a('admin-catalog-series')
                    );
                    exit;
                }
            }

            // Описываем всё необходимое для настройки отображения товаров на СТРАНИЦЕ КАТЕГОРИИ
            $htmlh1 =
                'Настройка отображения в серий
				<b>&laquo;' . $seriesInf['name'] . '&raquo;</b>
				' . ($seriesGroupId ? 'в группе <b>&laquo;' . $seriesGroupInf['name'] . '&raquo;</b>' : '') . '
				товаров из других серий';
            $htmlComment =
                'Товары из выбранных ниже групп будут отображаться на странице текущей серии, как её собственные товары.<br>При этом данные товары по-прежнему относятся к другим сериям и редактируются в других сериях.';
            self::$okMsg = 'Настройки отображения элементов из других серий на странице серии сохранены';
            self::$ret = Url::buildUrl(Url::a('admin-catalog-series-crossitems'), array(
                's' => $seriesId
            ));
            self::$placeCond = '`where_series_id` = ' . $seriesId . ' AND `where_group_id` = ' . $seriesGroupId;
            self::$placeData = array(
                'where_series_id' => $seriesId,
                'where_group_id' => $seriesGroupId
            );
        } else {
            exit('Error');
        }

        // Определяем класс объекта, управляющего линковкой товаров
        if ($pageId || $categoryId) {
            // Стр. категории или теговая стр.
            self::$oLinksObj = new Catalog_Categories_ItemsLinks();
        } elseif ($seriesId) {
            // Стр. серии
            self::$oLinksObj = new Catalog_Series_CrossItems();
        }

        if ($cId = intval($_GET['load-series'])) {
            // Получение списка серий в категории
            return self::loadSeries($cId);
        }
        if ($sId = intval($_GET['load-groups'])) {
            // Получение списка групп товаров в серии
            return self::loadGroups($sId);
        }
        if (($sId = intval($_GET['load-items'])) && ($gId = intval($_GET['group']))) {
            // Получение списка товаров в серии/группе
            return self::loadItems($sId, $gId);
        }

        // Какие категории и серии нужно сразу отображать развёрнутыми
        $oSeries = new Catalog_Series();
        $oItems = new Catalog_Items();

        // Серии, из которых выбраны группы товаров
        if (!self::$itemsOnly) {
            $seriesIds1 = array_unique(
                self::$oLinksObj->getCol(
                    'series_id',
                    self::$placeCond
                )
            );
        } else {
            $seriesIds1 = array();
        }

        // Серии, из которых выбраны сами товары
        $itemsIds = array_unique(
            self::$oLinksObj->getCol(
                'item_id',
                self::$placeCond
            )
        );
        $itemsIds[] = 0;

        $seriesIds2 = array_unique(
            $oItems->getCol(
                'series_id',
                '`id` IN (' . implode(',', $itemsIds) . ')'
            )
        );
        $activeSeries = array_values(array_unique(array_merge($seriesIds1, $seriesIds2)));

        // Категории выбранных серий
        if (count($activeSeries)) {
            $activeCategories = array_values(
                array_unique(
                    $oSeries->getCol(
                        'category_id',
                        '`id` IN (' . implode(',', $activeSeries) . ')'
                    )
                )
            );
        } else {
            $activeCategories = array();
        }

        // Все категории каталога
        $oCategories = new Catalog_Categories();
        $categories = $oCategories->getFloatTree(
            'id',
            1000
        );

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'htmlh1' => $htmlh1,
            'htmlComment' => $htmlComment,
            'itemsOnly' => self::$itemsOnly,

            'categories' => $categories,
            'activeCategories' => $activeCategories,
            'activeSeries' => $activeSeries,

            'retUrl' => self::$ret
        ));

        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        return $frm->run('mItemsLinks::save');
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        // Прямые связи с товарами обрабатываются только для товаров, галочки для которых были отображены в форме
        $displayedItems = array_map('intval', explode(',', $newData['displayed-items']));
        $displayedItems[] = 0;
        $displayedItems = array_unique($displayedItems);

        $old = self::$oLinksObj->getWhtKeys(
            '*',
            '
				(' . self::$placeCond . ') AND
				`item_id` IN (' . implode(',', $displayedItems) . ')
			'
        );

        foreach ($old as $ln => &$l) {
            if ($l['item_id']) {
                $l = 'i-' . $l['item_id'];
            } elseif ($l['group_id']) {
                $l = 'g-' . $l['series_id'] . '-' . $l['group_id'];
            } else {
                unset($old[$ln]);
            }
        }
        unset($l);

        $new = $newData['links'];

        if (!is_array($new)) {
            $new = array();
        }

        $new = array_map('trim', $new);

        // Удаляем линки, которые были, но убрались
        foreach ($old as $lId => $l) {
            if (!in_array($l, $new)) {
                self::$oLinksObj->del($lId);
            }
        }

        // Создаём линки, которых не было, но появились
        foreach ($new as $l) {
            if (!in_array($l, $old)) {
                if (strpos($l, 'g-') !== false) {
                    // Группа
                    $l = str_replace('g-', '', $l);
                    list($sId, $gId) = array_map('intval', explode('-', $l));
                    $iId = 0;
                } else {
                    // Товар
                    $l = str_replace('i-', '', $l);
                    $iId = intval($l);
                    $sId = $gId = 0;
                }

                if (!self::$itemsOnly) {
                    self::$oLinksObj->add(
                        array_merge(
                            self::$placeData,
                            array(
                                'series_id' => $sId,
                                'group_id' => $gId,
                                'item_id' => $iId
                            )
                        )
                    );
                } else {
                    self::$oLinksObj->add(
                        array_merge(
                            self::$placeData,
                            array(
                                'item_id' => $iId
                            )
                        )
                    );
                }
            }
        }

        Pages::flash(
            self::$okMsg,
            false,
            self::$ret
        );
    }


    /**
     * Получение списка серий в категории
     * @param int $catId
     * @return    array
     */
    static function loadSeries($catId)
    {
        self::$output = OUTPUT_JSON;
        $catId = intval($catId);
        $oSeries = new Catalog_Series();
        return $oSeries->get(
            'id, name',
            '`category_id` = ' . $catId . ' AND `out_of_production` = 0',
            'order'
        );
    }


    /**
     * Получение списка групп товаров в серии
     * @param int $seriesId
     * @return    array
     */
    static function loadGroups($seriesId)
    {
        self::$output = OUTPUT_JSON;
        $seriesId = intval($seriesId);

        $oItemsGroups = new Catalog_Items_Groups();
        $oItems = new Catalog_Items();

        $groups = $oItemsGroups->getForSeries($seriesId, true);

        // Определяем, какие группы выбраны
        $selected = array();
        if (!self::$itemsOnly && count($groups)) {
            $selected = self::$oLinksObj->getCol(
                'group_id',
                '
					(' . self::$placeCond . ') AND
					`series_id` = ' . $seriesId . ' AND
					`group_id` IN (' . implode(',', array_keys($groups)) . ')
				'
            );
        }

        // Определяем, в каких группах выбраны товары
        $contains = array();
        if (count($groups)) {
            $item2gr = $oItems->getHash(
                'id, group_id',
                '`series_id` = ' . $seriesId . ' AND `group_id` IN (' . implode(',', array_keys($groups)) . ')'
            );
            if (count($item2gr)) {
                $itemIds = self::$oLinksObj->getCol(
                    'item_id',
                    '
						(' . self::$placeCond . ') AND
						`item_id` IN (' . implode(',', array_keys($item2gr)) . ')
					'
                );
                foreach ($itemIds as $iId) {
                    $contains[] = $item2gr[$iId];
                }
                $contains = array_unique($contains);
            }
        }

        foreach ($groups as $gId => &$g) {
            $g['selected'] = in_array($gId, $selected);
            $g['contains'] = in_array($gId, $contains);
        }
        unset($g);

        return array_values($groups);
    }


    /**
     * @param int $seriesId
     * @param int $groupId
     * @return    array
     */
    static function loadItems($seriesId, $groupId)
    {
        self::$output = OUTPUT_JSON;
        $seriesId = intval($seriesId);
        $groupId = intval($groupId);

        $oItems = new Catalog_Items();
        $items = $oItems->imageExtToData(
            $oItems->getWhtKeys(
                'id, name, art',
                '`series_id` = ' . $seriesId . ' AND `group_id` = ' . $groupId/* . ' AND `items_links_exclude` = 0'*/,
                'order'
            )
        );

        if (count($items)) {
            $selected = self::$oLinksObj->getCol(
                'item_id',
                '
					(' . self::$placeCond . ') AND
					`item_id` IN (' . implode(',', array_keys($items)) . ')
				'
            );
        } else {
            $selected = array();
        }
        foreach ($items as $iId => &$i) {
            $i['selected'] = in_array($iId, $selected);
        }
        unset($i);

        return array_values($items);
    }
}
