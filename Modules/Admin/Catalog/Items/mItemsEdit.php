<?php

/** Админка: добавление / редактирование товара в серии мебели
 * @author    Seka
 */


class mItemsEdit extends Admin
{
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
     * @var array
     */
    static $itemsLinks = array();


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();

        $oItems = new Catalog_Items();

        if (self::$pId = intval($_GET['id'])) {
            // Редактирование
            $init = $oItems->imageExtToData(
                $oItems->getRow(
                    '*',
                    '`id` = ' . self::$pId
                )
            );
            if ($init === false) {
                Pages::flash('Запрошенный для редактирования товар не найден.', true, Url::a('admin-catalog-series'));
            }
            self::$seriesId = $init['series_id'];

            $init['volume'] = abs($init['volume']);
            $init['weight'] = abs($init['weight']);

            $init['extra_charge'] = Catalog::num2percent($init['extra_charge'], Catalog::PC_INCREASE);
            $init['discount'] = Catalog::num2percent($init['discount'], Catalog::PC_DECREASE);

            // Получаем связи с материалами
            $oItems2Materials = new Catalog_Items_2Materials();
            $init['materials'] = $oItems2Materials->getWhtKeys(
                '*',
                '`item_id` = ' . self::$pId,
                '',
                0,
                '',
                '',
                'material_id'
            );

            $init['assembly_var'] = 0;
            if ($init['free_assembly']) {
                $init['assembly_var'] = 1;
            }
            if ($init['no_assembly']) {
                $init['assembly_var'] = 2;
            }

            // Категории и теговые страницы, где отображается товар
            $oItemsLinks = new Catalog_Categories_ItemsLinks();
            self::$itemsLinks = $oItemsLinks->getWhtKeys(
                'id, category_id, page_id',
                '`item_id` = ' . self::$pId
            );
            foreach (self::$itemsLinks as &$l) {
                $l = $l['category_id'] . '-' . $l['page_id'];
            }
            unset($l);
            $init['itemslinks'] = self::$itemsLinks;
        } else {
            // Добавление
            self::$seriesId = intval($_GET['s']);

            $init = array(
                'in_ym' => 1,
                'currency' => Catalog::RUB,
                'price' => '0.00',
                'extra_charge' => '0',
                'discount' => '0',
                'materials' => array(),

                /*'on_series_photo_x'	=> 200,
                'on_series_photo_y'	=> 130*/
            );
        }

        // Находим серию
        $oSeries = new Catalog_Series();
        $seriesInf = $oSeries->getRow('*', '`id` = ' . self::$seriesId);
        if (!$seriesInf) {
            Pages::flash(
                'Запрошенная для редактирования серия мебели не найдена.',
                true,
                Url::a('admin-catalog-series')
            );
            exit;
        }

        // 1-я фотография серии для расстановки на ней точек
        /*$oPhotos = new Catalog_Series_Photos();
        $photo0 = $oPhotos->imageExtToData($oPhotos->getRow(
            '*',
            '`series_id` = ' . self::$seriesId,
            'order'
        ));*/

        // Материалы серии
        $oSeries2Materials = new Catalog_Series_2Materials();
        $mIds = $oSeries2Materials->getCol(
            'material_id',
            '`series_id` = ' . self::$seriesId
        );
        if (count($mIds)) {
            $oMaterials = new Catalog_Materials();
            $materials = $oMaterials->imageExtToData(
                $oMaterials->get(
                    '*',
                    '`id` IN (' . implode(',', $mIds) . ') AND `parent_id` = 0',
                    'order'
                )
            );
            // Поставщики (для отображения в списке материалов)
            $oSuppliers = new Catalog_Suppliers();
            $suppliers = $oSuppliers->getHash(
                'id, name',
                '',
                '`name` ASC'
            );

            // Исходные значения для формы
            foreach ($materials as $m) {
                if (!isset($init['materials'][$m['id']])) {
                    $init['materials'][$m['id']] = array(
                        'price' => '0.00',
                        'currency' => Catalog::RUB
                    );
                }
            }
        } else {
            $materials = array();
            $suppliers = array();
        }

        // Группы товаров
        $oItemsGroups = new Catalog_Items_Groups();
        $groups = $oItemsGroups->getForCat($seriesInf['category_id']);

        // Серии металлической мебели
        $oMetallSeries = new Catalog_MetallSeries();
        $metallSeries = $oMetallSeries->get(
            '*',
            '',
            'name'
        );

        // Теговые страницы
        $oCategories = new Catalog_Categories();
        $oPagesGroups = new Catalog_Pages_Groups();
        $oPages = new Catalog_Pages();
        $categories = $oCategories->getFloatTree('id');
        foreach ($categories as $cn => &$c) {
            $c['pages-groups'] = $oPagesGroups->get(
                'id, name',
                '`category_id` = ' . $c['id'],
                'order'
            );
            foreach ($c['pages-groups'] as $pgn => &$pg) {
                $pg['pages'] = $oPages->get(
                    'id, name',
                    '`group_id` = ' . $pg['id'],
                    'order'
                );
                if (!count($pg['pages'])) {
                    unset($c['pages-groups'][$pgn]);
                }
            }
            unset($pg);
            if (!count($c['pages-groups'])) {
                unset($categories[$cn]);
            }
        }
        unset($c);
        //print_array($categories);exit;

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'init' => $init,
            'seriesInf' => $seriesInf,
            //'photo0'	=> $photo0,

            'groups' => $groups,
            'materials' => $materials,
            'suppliers' => $suppliers,
            'metallSeries' => $metallSeries,
            'categories' => $categories
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        $frm->setInit($init);
        return $frm->run('mItemsEdit::save', 'mItemsEdit::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        // Проверка уникальности URL
        /*$_POST['url'] = mb_strtolower(trim($_POST['url']));
        $oItems = new Catalog_Items();
        if($oItems->getCount('`url` = \'' . MySQL::mres($_POST['url']) . '\' AND `series_id` = ' . intval(self::$seriesId) . ' AND `id` != ' . intval(self::$pId))){
            return array(array(
                'name' => 'url',
                'msg'  => 'Указанный URL используется для другого товара.'
            ));
        }*/

        // Проверка файла картинки
        $imgCheck = Form::checkUploadedImage(
            self::$pId,
            'image',
            3200,
            2400,
            true
        );
        if ($imgCheck !== true) {
            return array($imgCheck);
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        /*onPhoto = 0; $onPhotoX = 0; $onPhotoY = 0;
        if(intval($newData['in_series_set'])){
            $onPhoto = intval($newData['on_series_photo']) ? 1 : 0;
            if($onPhoto){
                $onPhotoX = intval($newData['on_series_photo_x']);
                $onPhotoY = intval($newData['on_series_photo_y']);
            }
        }*/

        $save = array(
            'name' => $newData['name'],
            'title' => $newData['title'],
            'h1' => $newData['h1'],
            'dscr' => $newData['dscr'],
            'kwrd' => $newData['kwrd'],
            'text' => $newData['text'],
            //'url'	=> trim($newData['url']),

            'art' => $newData['art'],
            'size' => $newData['size'],
            'volume' => str_replace(',', '.', $newData['volume']),
            'weight' => str_replace(',', '.', $newData['weight']),

            'in_ym' => intval($newData['in_ym']) ? 1 : 0,

            /*'in_series_set'			=> intval($newData['in_series_set']) ? 1 : 0,
            'in_series_set_amount'	=> abs(intval($newData['in_series_set_amount'])),
            'on_series_photo'	=> $onPhoto,
            'on_series_photo_x'	=> $onPhotoX,
            'on_series_photo_y'	=> $onPhotoY,*/
            'group_id' => intval($newData['group_id']),
            'series_id' => self::$seriesId,

            'currency' => $newData['currency'] === Catalog::USD ? Catalog::USD : Catalog::RUB,
            'price' => str_replace(',', '.', $newData['price']),
            'extra_charge' => Catalog::percent2num($newData['extra_charge'], Catalog::PC_INCREASE),
            'discount' => Catalog::percent2num($newData['discount'], Catalog::PC_DECREASE),

            'free_assembly' => intval($newData['assembly_var']) == 1 ? 1 : 0,
            'no_assembly' => intval($newData['assembly_var']) == 2 ? 1 : 0,
            'price_delivery' => str_replace(',', '.', $newData['price_delivery']),
            'price_assembly' => str_replace(',', '.', $newData['price_assembly']),
            'price_unloading' => str_replace(',', '.', $newData['price_unloading']),

            //'items_links_exclude'	=> intval($newData['items_links_exclude']) ? 1 : 0
        );
        if (!$save['in_series_set_amount']) {
            $save['in_series_set_amount'] = 1;
        }

        if (intval($newData['is_metal'])) {
            // Опции металлической мебели
            $save['is_metal'] = 1;
            $save['metall_series_id'] = intval($newData['metall_series_id']);
            $save['is_hanger'] = 0;
            $save['is_accessories'] = 0;
        } else {
            // Опции НЕметаллической мебели
            $save['is_metal'] = 0;
            $save['metall_series_id'] = 0;
            $save['is_hanger'] = intval($newData['is_hanger']) ? 1 : 0;
            $save['is_accessories'] = intval($newData['is_accessories']) ? 1 : 0;
        }


        $oItems = new Catalog_Items();

        if (self::$pId) {
            // Редактирование

            // Автозаметки
            self::autoNotesOnEdit($initData, $newData, $save);

            // Сохранение
            $oItems->upd(self::$pId, $save);

            $msg = 'Изменения сохранены.';
        } else {
            // Добавление
            self::$pId = $oItems->add($save);

            $msg = 'Товар успешно добавлен.';

            // Автозаметка
            $oSeries = new Catalog_Series();
            $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_CREATE, 0, self::$pId);
        }

        // Сохраняем связи с материалами
        $oItems2Materials = new Catalog_Items_2Materials();
        $oItems2Materials->delCond('`item_id` = ' . self::$pId);
        foreach ($newData['materials'] as $m) {
            if (intval($m['material_id'])) {
                $c = $m['currency'];
                if ($c !== Catalog::RUB && $c !== Catalog::USD) {
                    $c = Catalog::RUB;
                }
                $oItems2Materials->add(array(
                    'material_id' => $m['material_id'],
                    'item_id' => self::$pId,
                    'currency' => $c,
                    'price' => $m['price']
                ));
            }
        }

        // Save image
        if ($_FILES['image']['name']) {
            $oItems->imageSave(
                self::$pId,
                $_FILES['image']['tmp_name']
            );
        }

        // ItemsLinks
        //self::$itemsLinks
        $oItemsLinks = new Catalog_Categories_ItemsLinks();
        $new = $newData['itemslinks'];
        if (!is_array($new)) {
            $new = array();
        }
        $new = array_map('trim', $new);

        // Удаляем линки, которые были, но убрались
        foreach (self::$itemsLinks as $lId => $l) {
            if (!in_array($l, $new)) {
                $oItemsLinks->del($lId);
            }
        }

        // Создаём линки, которых не было, но появились
        foreach ($new as $l) {
            if (!in_array($l, self::$itemsLinks)) {
                list($catId, $pageId) = array_map('intval', explode('-', $l));
                $oItemsLinks->add(array(
                    'category_id' => $catId,
                    'page_id' => $pageId,
                    'series_id' => 0,
                    'group_id' => 0,
                    'item_id' => self::$pId
                ));
            }
        }

        Pages::flash(
            $msg,
            false,
            Url::buildUrl(Url::a('admin-catalog-items'), array(
                's' => self::$seriesId,
                'ret' => $_GET['ret']
            ))
        );
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

        // Отслеживанием изменение обычных параметров товара
        $note = false;
        $flds = array(
            'name',
            'title',
            'h1',
            'dscr',
            'kwrd',
            'text',
            'url',
            'art',
            'size',
            'volume',
            'weight',
            'is_metal',
            'metall_series_id',
            'is_hanger',
            'is_accessories',
            'group_id'
        );
        foreach ($flds as $fld) {
            if ((string)$initData[$fld] !== (string)$saveData[$fld]) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_CHANGE, 0, self::$pId);
                $note = true;
                break;
            }
        }
        if (!$note) {
            // Проверяем, не изменился ли набор материалов
            $oItems2Materials = new Catalog_Items_2Materials();
            $oldMatsIds = $oItems2Materials->getCol('material_id', '`item_id` = ' . self::$pId);
            $oldMatsIds = array_map('intval', $oldMatsIds);
            sort($oldMatsIds);

            $newMatsIds = array();
            foreach ($newData['materials'] as $m) {
                if (intval($m['material_id'])) {
                    $newMatsIds[] = intval($m['material_id']);
                }
            }
            sort($newMatsIds);

            if (implode('-', $oldMatsIds) !== implode('-', $newMatsIds)) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_CHANGE, 0, self::$pId);
            }
        }

        // Отслеживанием изменение цены, наценки, скидки
        $oItems = new Catalog_Items();
        $oldData = $oItems->getRow(
            'currency, price, extra_charge, discount',
            '`id` = ' . self::$pId
        );
        $priceNote = false;
        if ($oldData) {
            if (
                $saveData['currency'] != $oldData['currency'] ||
                (float)$saveData['price'] != (float)$oldData['price']
            ) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_PRICE, 0, self::$pId);
                $priceNote = true;
            }
            if ((float)$saveData['extra_charge'] != (float)$oldData['extra_charge']) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_EXTRA, 0, self::$pId);
            }
            if ((float)$saveData['discount'] != (float)$oldData['discount']) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_DISCOUNT, 0, self::$pId);
            }
        }
        if (!$priceNote) {
            // Проверяем, не изменились цены на материалы
            $oItems2Materials = new Catalog_Items_2Materials();
            $tmp = $oItems2Materials->get('*', '`item_id` = ' . self::$pId);
            $oldMatsPrices = array();
            foreach ($tmp as $m) {
                $oldMatsPrices[intval($m['material_id'])] = array(
                    $m['currency'],
                    (float)$m['price']
                );
            }
            ksort($oldMatsPrices);

            $newMatsPrices = array();
            foreach ($newData['materials'] as $m) {
                if (intval($m['material_id'])) {
                    $newMatsPrices[intval($m['material_id'])] = array(
                        $m['currency'],
                        (float)$m['price']
                    );
                }
            }
            ksort($newMatsPrices);

            if (serialize($oldMatsPrices) !== serialize($newMatsPrices)) {
                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_PRICE, 0, self::$pId);
            }
        }
    }
}
