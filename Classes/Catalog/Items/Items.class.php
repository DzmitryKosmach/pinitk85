<?php

/**
 * Товары в сериях мебели
 *
 * @author    Seka
 */

class Catalog_Items extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_items';

    /**
     * @var string
     */
    static $imagePath = '/Item/';

    /**
     * Путь к файлу с шаблоном для отображения одного товара в списке товаров
     * @var string
     * Используется в других шаблонах таким образом: include(_ROOT . Catalog_Items::$tpl)
     * Перед инклюдом шаблона вся информация о товаре должна находится в переменной $i
     * Также в шаблоне используются переменные (т.е. они должны существовать до инклюда):
     * $seriesInf - инфа о серии (id, category_id, url)
     * $materials - дерево материалов $oMaterials->getTree($seriesId)
     */
    static $tpl = '/Skins/html/User/Catalog/CatalogItem.htm';

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /** Переопределим метод для присвоения order
     * Также при добавлении товаров в таблицу нужно пересчитать мин. цены соответствующих серий
     * @param array  $data
     * @param string $method
     * @return    int
     * @see    DbList::addArr()
     */
    function addArr($data = array(), $method = self::INSERT)
    {
        $res = parent::addArr($data, $method);

        $itemsIds = $this->getCol('id', '`order` = 0');
        foreach ($itemsIds as $itemId) {
            $this->calcDeferred($itemId);
        }

        $this->setOrderValue();

        return $res;
    }


    /** При изменении currency, price, extra_charge, discount или series_id - нужно пересчитать его мин. и макс. цены
     * При измении series_id, in_series_set или price_min - нужно пересчитать мин. цены для серий
     * @param string $cond
     * @param array  $updArr
     * @todo    Наличие одного из отслеживаемых полей в $updArr фактически не означает изменение его значения,
     * @todo    стоило бы сделать сравнение старых данных с новыми, чтобы точно определить, были ли они изменены
     */
    function updCond($cond = '', $updArr = array())
    {
        $calcItems = isset($updArr['currency']) || isset($updArr['price']) || isset($updArr['extra_charge']) || isset($updArr['discount']) || isset($updArr['series_id']);
        $calcSeries = isset($updArr['series_id']) || /*isset($updArr['in_series_set']) || isset($updArr['in_series_set_amount']) ||*/
            isset($updArr['price_min']);
        if ($calcItems || $calcSeries) {
            $itemsIds = $seriesIds = array();
            foreach ($this->get('id, series_id', $cond) as $i) {
                $itemsIds[] = $i['id'];
                $seriesIds[] = $i['series_id'];
            }
        } else {
            $itemsIds = array();
            $seriesIds = array();
        }

        parent::updCond($cond, $updArr);

        if ($calcItems && count($itemsIds)) {
            foreach ($itemsIds as $itemId) {
                $this->calcDeferred($itemId);
            }
        }
        if ($calcSeries && count($seriesIds)) {
            $oCrossItems = new Catalog_Series_CrossItems();
            $seriesIds = array_merge(
                $seriesIds,
                $this->getCol('series_id', $cond),
                $oCrossItems->getCol(
                    'where_series_id',
                    '`item_id` IN (' . implode(',', $itemsIds) . ')'
                )
            );
            $seriesIds = array_unique($seriesIds);
            $oSeries = new Catalog_Series();
            foreach ($seriesIds as $seriesId) {
                $oSeries->calcDeferred($seriesId);
            }
        }
    }


    /**
     * @param string $cond
     * @return bool
     * @see    DbList::delCond()
     */
    function delCond($cond = '')
    {
        $ids = $seriesIds = array();
        foreach ($this->get('id, series_id', $cond) as $item) {
            $ids[] = $item['id'];
            $seriesIds[] = $item['series_id'];
        }

        $result = parent::delCond($cond);

        if (count($ids)) {
            // Удаляем зависимые данные
            $oItems2Materials = new Catalog_Items_2Materials();
            $oItems2Materials->delCond('`item_id` IN (' . implode(',', $ids) . ')');

            $oItemsLinks = new Catalog_Categories_ItemsLinks();
            $oItemsLinks->delCond('`item_id` IN (' . implode(',', $ids) . ')');

            $oCrossItems = new Catalog_Series_CrossItems();
            $oCrossItems->delCond('`item_id` IN (' . implode(',', $ids) . ')');

            $oSetItems = new Catalog_Series_SetItems();
            $oSetItems->delCond('`item_id` IN (' . implode(',', $ids) . ')');
            /*$oItemsLinksSel = new Catalog_Categories_ItemsLinks_Selection();
            $oItemsLinksSel->delCond('`item_id` IN (' . implode(',', $ids) . ')');*/
        }
        if (count($seriesIds)) {
            // Пересчитываем мин. цены для серий, к которым относились удалённые товары
            $seriesIds = array_unique($seriesIds);
            $oSeries = new Catalog_Series();
            foreach ($seriesIds as $seriesId) {
                $oSeries->calcDeferred($seriesId);
            }
        }

        $this->imageDel($ids);
        return $result;
    }


    /** Получаем полный URL страницы товара
     * @static
     * @param array $seriesInf
     * @param array $itemInf
     * @return    string|bool
     */
    static function a($seriesInf = array(), $itemInf = array())
    {
        static $cache = array();
        static $o;
        if (!$o) {
            $o = new self();
        }


        $itemId = intval($itemInf['id']);


        $seriesUrl = Catalog_Series::a($seriesInf);
        if ($seriesUrl) {
            //$cache[$itemId] = $seriesUrl . $itemInf['url'] . '/';
            $cache[$itemId] = $seriesUrl . $itemInf['id'] . '/';
        } else {
            $cache[$itemId] = $cache[$itemId];
        }

        return $cache[$itemId];
    }


    function details($items)
    {
        if (!is_array($items) || !count($items)) {
            return array();
        }

        $itemsIds = array();
        $seriesIds = array();
        foreach ($items as $i) {
            $itemsIds[] = intval($i['id']);
            $seriesIds[] = intval($i['series_id']);
        }
        $itemsIds = array_values(array_unique($itemsIds));
        $seriesIds = array_values(array_unique($seriesIds));

        // Поставщики серий заданных товаров
        $oSeries = new Catalog_Series();
        $suppliers = $oSeries->getHash(
            'id, supplier_id',
            '`id` IN (' . implode(',', $seriesIds) . ')'
        );

        // Материалы для всех заданных товаров
        $oItems2Materials = new Catalog_Items_2Materials();
        /*
        $tmp = $oItems2Materials->get(
            '`' . Catalog_Items_2Materials::$tab . '`.*',
            'ci.id IN (' . implode(',', $itemsIds) . ')',
            '',
            0,
            'JOIN `' . Catalog_Items::$tab . '` AS ci ON (`' . Catalog_Items_2Materials::$tab . '`.item_id = ci.id)'
        );
        */
        $tmp = $oItems2Materials->get(
            '`' . Catalog_Items_2Materials::$tab . '`.*',
            'ci.id IN (' . implode(',', $itemsIds) . ')',
            'cm.`order`',
            0,
            'JOIN `' . Catalog_Items::$tab . '` AS ci ON (`' . Catalog_Items_2Materials::$tab . '`.item_id = ci.id)' .
            ' JOIN `' . Catalog_Materials::$tab . '` AS cm ON (`' . Catalog_Items_2Materials::$tab . '`.material_id = cm.id)'
        );
        $items2materials = array();
        foreach ($tmp as $m) {
            if (!is_array($items2materials[$m['item_id']])) {
                $items2materials[$m['item_id']] = array();
            }
            $items2materials[$m['item_id']][] = $m;
        }
        unset($tmp);

        // Данные о дилере
        $dealer = Dealers_Security::getCurrent();
        if ($dealer) {
            $dealerExtras = Dealers_Extra::getForDealer($dealer['id']);
        }

        foreach ($items as &$i) {
            $i['image-name'] = $this->photoName($i);

            $itemPrice = $i['price'];
            if ($i['currency'] === Catalog::USD) {
                $itemPrice = $itemPrice * Catalog_Series::usdCourse($i['series_id']);
            }
            if (abs($i['discount']) != 1) {
                $itemPriceOld = $itemPrice * $i['extra_charge'];
            } else {
                $itemPriceOld = 0;
            }
            $itemPrice = $itemPrice * $i['extra_charge'] * $i['discount'];

            $i['materials'] = isset($items2materials[$i['id']]) ? $items2materials[$i['id']] : array();
            foreach ($i['materials'] as &$m) {
                if ((float)$m['price']) {
                    if ($m['currency'] === Catalog::USD) {
                        $m['price'] = $m['price'] * Catalog_Series::usdCourse($i['series_id']);
                    }
                    if (abs($i['discount']) != 1) {
                        $m['price-old'] = $m['price'] * $i['extra_charge'];
                    } else {
                        $m['price-old'] = 0;
                    }
                    $m['price'] = $m['price'] * $i['extra_charge'] * $i['discount'];
                } else {
                    $m['price'] = $itemPrice;
                    $m['price-old'] = $itemPriceOld;
                }

                if ($dealer && $dealer['show_in_price']) {
                    $extra = 1;
                    if (isset($dealerExtras[$suppliers[$i['series_id']]])) {
                        $extra = $dealerExtras[$suppliers[$i['series_id']]];
                    }
                    $m['price-in'] = $m['price'] / ($i['extra_charge'] * $i['discount']) * $extra;
                } else {
                    $m['price-in'] = 0;
                }
            }
            unset($m);
        }
        unset($i);

        return $items;
    }


    /**
     * @param array $itemInf
     * @return    array
     */
    function photoName($itemInf)
    {
        static $series;
        if (!$series) {
            $series = array();
        }

        $sId = $itemInf['series_id'];
        if (!isset($series[$sId])) {
            static $oSeries;
            if (!$oSeries) {
                $oSeries = new Catalog_Series();
            }
            $series[$sId] = $oSeries->getRow(
                'name, category_id',
                '`id` = ' . $sId
            );
        }

        static $oCategories;
        if (!$oCategories) {
            $oCategories = new Catalog_Categories();
        }
        $photoName = $oCategories->nameArr($series[$sId]['category_id']);
        $photoName[] = $series[$sId]['name'];
        $photoName[] = $itemInf['art'];
        return $photoName;
    }


    /** Вычиляем и сохраняем мин. и макс. цены на товар
     * @param int $itemId
     */
    function calcMinMaxPrice($itemId)
    {
        $itemId = intval($itemId);

        // Находим данные о товаре
        $itemInf = $this->getRow(
            'currency, price, extra_charge, discount, series_id',
            '`id` = ' . $itemId
        );
        if (!$itemInf) {
            return;
        }

        $usdCourse = Catalog_Series::usdCourse($itemInf['series_id']);

        // Основная цена товара
        $basePrice = round(
            $itemInf['currency'] == Catalog::RUB ? ($itemInf['price']) : ($itemInf['price'] * $usdCourse),
            Catalog::PRICES_DECIMAL
        );

        // Цены по материалам
        $oItems2Materials = new Catalog_Items_2Materials();
        $matPrices = $oItems2Materials->get(
            'material_id, currency, price',
            '`item_id` = ' . $itemId
        );

        // Собираем все цены вместе
        $prices = array();
        foreach ($matPrices as $mp) {
            if ((float)$mp['price']) {
                $p = round(
                    $mp['currency'] == Catalog::RUB ? ($mp['price']) : ($mp['price'] * $usdCourse),
                    Catalog::PRICES_DECIMAL
                );
            } else {
                $p = $basePrice;
            }
            $prices[] = array(
                'material_id' => $mp['material_id'],
                'price' => $p
            );
        }

        if (!count($prices)) {
            // Товар без материалов, только основная цена
            $this->upd(
                $itemId,
                array(
                    'price_min' => $basePrice * $itemInf['extra_charge'] * $itemInf['discount'],
                    'price_max' => $basePrice * $itemInf['extra_charge'] * $itemInf['discount'],
                    'price_min_material_id' => 0
                )
            );
        } else {
            // Находим мин. и макс. цены, определяем, с каким материалом будет мин. цена
            $min = 100000000;
            $max = 0;
            $minMId = 0;
            foreach ($prices as $p) {
                if ($p['price'] < $min) {
                    $min = $p['price'];
                    $minMId = $p['material_id'];
                }
                if ($p['price'] > $max) {
                    $max = $p['price'];
                }
            }
            $this->upd(
                $itemId,
                array(
                    'price_min' => $min * $itemInf['extra_charge'] * $itemInf['discount'],
                    'price_max' => $max * $itemInf['extra_charge'] * $itemInf['discount'],
                    'price_min_material_id' => $minMId
                )
            );
        }
    }


    /** Сюда записываются ID товаров, для которых нужно в самом конце работы скрипта выполнить calcMinMaxPrice()
     * @see    Catalog_Items::calcMinMaxPrice()
     * @see    Catalog_Items::calcDeferred()
     * @var array
     */
    static $calcDeferredItemsIds = array();

    /** Отложенный вызов calcMinMaxPrice()
     * Запуск пересчёта цен будет вызван в самом конце работы скрипта (при помощи register_shutdown_function())
     * При этом для каждого товара calcMinMaxPrice() выполнится не более 1-го раза
     * @param int $itemId
     * @see    Catalog_Items::calcMinMaxPrice()
     * @see    Catalog_Items::$calcDeferredItemsIds
     * @see    register_shutdown_function()
     */
    function calcDeferred($itemId)
    {
        if (!count(self::$calcDeferredItemsIds)) {
            register_shutdown_function(array($this, 'calcDeferredRun'));
        }
        self::$calcDeferredItemsIds[] = intval($itemId);
    }

    function calcDeferredRun()
    {
        if (!count(self::$calcDeferredItemsIds)) {
            return;
        }
        self::$calcDeferredItemsIds = array_map('intval', array_unique(self::$calcDeferredItemsIds));
        foreach (self::$calcDeferredItemsIds as $itemId) {
            $this->calcMinMaxPrice($itemId);
        }
    }


    /** Генерация URL'а для товара
     * @static
     * @param int    $id
     * @param string $name
     * @param string $art
     * @return    string
     */
    static function generateUrl($id, $name, $art)
    {
        $id = intval($id);
        static $o;
        if (!$o) {
            $o = new self();
        }

        $name = trim($name);
        $art = trim($art);
        if ($name === '') {
            // Имя передано пустым, получаем его из БД
            $name = $o->getCell('name', '`id` = ' . $id);
        }
        if ($art === '') {
            // Артикул передан пустым, получаем его из БД
            $art = $o->getCell('art', '`id` = ' . $id);
        }

        $url = $url0 = stringToUrl($name . ' ' . $art);
        $n = 0;
        while ($o->getCount('`url` = \'' . MySQL::mres($url) . '\' AND `id` != ' . $id) > 0) {
            $n++;
            $url = $url0 . '-' . $n;
        }
        return $url;
    }


    /** Генерация заголовков, метатегов и текста описания для страницы товара
     * Если для товара заданы отдельные заголовки/метатеги/описание, то они приоритетнее и не будут генерится
     * Иначе происходит их генерация по шаблонам, заданным для категории
     * @param int $itemId
     * @return    array
     * array(
     *        'title'    => ...,
     *        'h1'    => ...,
     *        'dscr'    => ...,
     *        'kwrd'    => ...,
     *        'text'    => ...
     * )
     */
    function generateHeadersAndMeta($itemId)
    {
        $result = array(
            'title' => '',
            'h1' => '',
            'dscr' => '',
            'kwrd' => '',
            'text' => ''
        );

        $itemId = intval($itemId);
        $item = $this->getRow(
            'name, art, size, price_min, price_max, series_id, title, h1, dscr, kwrd, text',
            '`id` = ' . $itemId
        );
        if (!$item) {
            return $result;
        }

        if (trim($item['title']) !== '') {
            $result['title'] = $item['title'];
        }
        if (trim($item['title']) !== '') {
            $result['h1'] = $item['h1'];
        }
        if (trim($item['title']) !== '') {
            $result['dscr'] = $item['dscr'];
        }
        if (trim($item['title']) !== '') {
            $result['kwrd'] = $item['kwrd'];
        }
        if (trim($item['text']) !== '') {
            $result['text'] = $item['text'];
        }
        if ($result['title'] !== '' && $result['h1'] !== '' && $result['dscr'] !== '' && $result['kwrd'] !== '' && $result['text'] !== '') {
            // Все параметры заполнены
            return $result;
        }

        $oSeries = new Catalog_Series();
        $series = $oSeries->getRow(
            'name, category_id',
            '`id` = ' . $item['series_id']
        );
        if (!$series) {
            return $result;
        }

        $oCategories = new Catalog_Categories();
        $cat = $oCategories->getRow(
            'pattern_items_title, pattern_items_h1, pattern_items_dscr, pattern_items_kwrd, pattern_items_text',
            '`id` = ' . $series['category_id']
        );
        if (!$cat) {
            return $result;
        }

        foreach ($result as $f => &$r) {
            if ($r === '' && trim($cat['pattern_items_' . $f]) !== '') {
                $r = $cat['pattern_items_' . $f];
                $r = str_replace('[catname]', Catalog_Categories::name1($series['category_id']), $r);
                $r = str_replace('[series-name]', $series['name'], $r);
                $r = str_replace('[name]', $item['name'], $r);
                $r = str_replace('[art]', $item['art'], $r);
                $r = str_replace('[size]', $item['size'], $r);
                $r = str_replace('[price-min]', Catalog::priceFormat($item['price_min'], ','), $r);
                $r = str_replace('[price-max]', Catalog::priceFormat($item['price_max'], ','), $r);

                if (preg_match('/\[series-filter-[0-9]+\]/ius', $r)) {
                    // Значения фильтров серии
                    if (!isset($filters2Values)) {
                        $oFiltersValues = new Catalog_Filters_Values();
                        $filters2Values = $oFiltersValues->getHash(
                            '`' . Catalog_Filters_Values::$tab . '`.`filter_id`, `' . Catalog_Filters_Values::$tab . '`.`value`',
                            'cs2f.`series_id` = ' . $item['series_id'],
                            'cs2f.`id`',
                            0,
                            '
								JOIN `' . Catalog_Series_2Filters::$tab . '` AS cs2f
								ON (`' . Catalog_Filters_Values::$tab . '`.`id` = cs2f.`value_id`)
							'
                        );
                    }

                    // Находим все варианты ID фильтра и далаем замены
                    preg_match_all('/\[series-filter-[0-9]+\]/ius', $r, $matches);
                    foreach ($matches[0] as $fId) {
                        $fId = intval(str_replace(array('[series-filter-', ']'), '', $fId));
                        $r = str_replace(
                            '[series-filter-' . $fId . ']',
                            trim($filters2Values[$fId]),
                            $r
                        );
                    }
                }

                if (preg_match('/\[materials-[0-9]+\]/ius', $r)) {
                    // Первые X материалов
                    if (!isset($materials)) {
                        $oItems2Materials = new Catalog_Items_2Materials();
                        $materials = $oItems2Materials->getCol(
                            '`cm`.name',
                            '`item_id` = ' . $itemId,
                            '`cm`.order',
                            0,
                            'JOIN `' . Catalog_Materials::$tab . '` AS `cm` ON (`cm`.`id` = `' . Catalog_Items_2Materials::$tab . '`.`material_id`)'
                        );
                    }

                    // Находим все варианты икса (т.е. все количества, используемые в шаблоне) и далаем замены
                    preg_match_all('/\[materials-[0-9]+\]/ius', $r, $matches);
                    foreach ($matches[0] as $cnt) {
                        $cnt = intval(str_replace(array('[materials-', ']'), '', $cnt));
                        $r = str_replace(
                            '[materials-' . $cnt . ']',
                            implode(
                                ', ',
                                $cnt == 0 ? $materials : array_slice($materials, 0, $cnt)
                            ),
                            $r
                        );
                    }
                }

                if ($f == 'text') {
                    $r = nl2br($r);
                }

                $result[$f] = $r;
            }
        }
        unset($r);

        if (trim($result['h1']) === '') {
            $result['h1'] = $item['name'] . ' ' . $item['art'];
        }
        if (trim($result['title']) === '') {
            $result['title'] = $item['name'] . ' ' . $item['art'];
        }
        return $result;
    }
}
