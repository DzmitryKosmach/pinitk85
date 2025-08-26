<?php

/**
 * Серии мебели
 *
 * @author    Seka
 */

class Catalog_Series extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series';

    /**
     * Путь к файлу с шаблоном для отображения одной серии в списке серий
     * @var string
     * Используется в других шаблонах таким образом: include(_ROOT . Catalog_Series::$tpl)
     * Перед инклюдом шаблона вся информация о серии должна находится в переменной $s
     */
    static $tpl = '/Skins/html/User/Catalog/SeriesItem.htm';

    /**
     * Сколько маленьких квадратиков с материалами для каждой серии отображается в списке серий
     */
    const MATERIALS_PREVIEW_CNT = 4;

    /**
     * У серий есть параметр extra_charge - общая наценка на товары серии.
     * У каждого товара она своя, но можно задать её сразу для всех товаров серии - здесь хранится последнее уставновленное значение
     */

    /**
     * Что делать при изменении extra_charge для серии
     * Запоминается в поле extra_charge_action
     */
    const EXTRA_ACTION_NONE = 'none';            // ничего
    const EXTRA_ACTION_REPL_PART = 'repl-part';    // установить такую же наценку для товаров, у которых она совпадала с прежним значением extra_charge серии
    const EXTRA_ACTION_REPL_ALL = 'repl-all';    // установить такую же наценку для всех товаров серии

    /**
     * При импорте прайса:
     */
    const IMPORT_FORMULA_EXTRA = 'extra';    // вычисляется наценка (по вход./выход. ценам)
    const IMPORT_FORMULA_OUT = 'out';        // вычисляется выход.цена (по вход.цене и наценке)

    /**
     * Возможные заметки для автоматического добавления
     * @see    Catalog_Series::makeNotes()
     */
    const NOTE_SERIES_CREATE = 1;
    const NOTE_SERIES_CHANGE = 2;
    const NOTE_SERIES_USD = 9;
    const NOTE_ITEM_CREATE = 3;
    const NOTE_ITEM_REMOVE = 4;
    const NOTE_ITEM_CHANGE = 5;
    const NOTE_ITEM_PRICE = 6;
    const NOTE_ITEM_EXTRA = 7;
    const NOTE_ITEM_DISCOUNT = 8;

    /** Тексты сообщений, кот. будут записаны в заметки
     * @var array
     */
    protected static $notesMessages = array(
        self::NOTE_SERIES_CREATE => 'Серия создана',
        self::NOTE_SERIES_CHANGE => 'Изм. параметр серии',
        self::NOTE_SERIES_USD => 'Изм. курса У.Е. серии',
        self::NOTE_ITEM_CREATE => 'Добавлен товар',
        self::NOTE_ITEM_REMOVE => 'Удалён товар',
        self::NOTE_ITEM_CHANGE => 'Изм. параметры товара(ов)',
        self::NOTE_ITEM_PRICE => 'Изм. вход. цена на товар(ы)',
        self::NOTE_ITEM_EXTRA => 'Изм. наценка на товар(ы)',
        self::NOTE_ITEM_DISCOUNT => 'Изм. скидка на товар(ы)'
    );

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /** Переопределим метод для присвоения order
     * @param array  $data
     * @param string $method
     * @return    int
     * @see    DbList::addArr()
     */
    function addArr($data = array(), $method = self::INSERT)
    {
        $res = parent::addArr($data, $method);
        $this->setOrderValue();
        return $res;
    }


    /** При изменении usd_course - нужно пересчитать мин. и макс. цены для всех товаров в серии
     * @param string $cond
     * @param array  $updArr
     */
    function updCond($cond = '', $updArr = array())
    {
        $calcItems = isset($updArr['usd_course']) || isset($updArr['price_search_group_id']);
        if ($calcItems) {
            $seriesIds = $this->getCol('id', $cond);
        } else {
            $seriesIds = array();
        }

        parent::updCond($cond, $updArr);

        if ($calcItems && count($seriesIds)) {
            self::usdCourse(0, true);    // Сбрасывавем кеш метода получения курса валют для серии

            $oItems = new Catalog_Items();
            $itemsIds = $oItems->getCol(
                'id',
                '`series_id` in (' . implode(',', $seriesIds) . ')'
            );
            foreach ($itemsIds as $itemId) {
                $oItems->calcDeferred($itemId);
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
        $ids = $this->getCol('id', $cond);
        $result = parent::delCond($cond);

        if (count($ids)) {
            // Удаляем зависимые данные
            $oItems = new Catalog_Items();
            $oItems->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oPhotos = new Catalog_Series_Photos();
            $oPhotos->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oSeriesOptions = new Catalog_Series_Options();
            $oSeriesOptions->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oSeries2Filters = new Catalog_Series_2Filters();
            $oSeries2Filters->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oSeries2Materials = new Catalog_Series_2Materials();
            $oSeries2Materials->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oSeriesLinkage2Series = new Catalog_Series_Linkage_2Series();
            $oSeriesLinkage2Series->delCond(
                '`series1_id` IN (' . implode(',', $ids) . ') OR `series2_id` IN (' . implode(',', $ids) . ')'
            );

            //$oItemsGroups = new Catalog_Items_Groups0();
            //$oItemsGroups->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oReviews = new Reviews();
            $oReviews->delCond(
                '`object` = \'' . Reviews::OBJ_SERIES . '\' AND `object_id` IN (' . implode(',', $ids) . ')'
            );

            $oSeries2Pages = new Catalog_Series_2Pages();
            $oSeries2Pages->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oItemsGroupsOptions = new Catalog_Items_Groups_Options();
            $oItemsGroupsOptions->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oItemsLinks = new Catalog_Categories_ItemsLinks();
            $oItemsLinks->delCond('`series_id` IN (' . implode(',', $ids) . ')');

            $oCrossItems = new Catalog_Series_CrossItems();
            $oCrossItems->delCond('`where_series_id` IN (' . implode(',', $ids) . ')');

            $oSetItems = new Catalog_Series_SetItems();
            $oSetItems->delCond('`series_id` IN (' . implode(',', $ids) . ')');
        }

        return $result;
    }


    /** Получаем полный URL страницы серии
     * @static
     * @param array $seriesInf
     * @return    string|bool
     */
    static function a($seriesInf = array())
    {
        if (!isset($seriesInf['id']) || !isset($seriesInf['category_id']) || !isset($seriesInf['url'])) {
            return false;
        }

        static $cache = array();
        static $o;
        if (!$o) {
            $o = new self();
        }

        $seriesId = intval($seriesInf['id']);

        $catUrl = Catalog_Categories::a($seriesInf['category_id']);
        if ($catUrl) {
            $cache[$seriesId] = $catUrl . $seriesInf['url'] . '/';
        }

        return $cache[$seriesId];
    }


    /** Курс валют для указанной серии
     * @static
     * @param int  $seriesId 0, чтобы получить общий курс по сайту
     * @param bool $clearCache Сбросить кеш предыдущих результатов выполнения данного метода
     * @return    number
     */
    static function usdCourse($seriesId = 0, $clearCache = false)
    {
        static $cache = array();
        static $o;
        if (!$o) {
            $o = new self();
        }
        static $globalUsdCourse;
        if (!$globalUsdCourse) {
            $globalUsdCourse = abs(Options::name('global-usd-course'));
        }

        $seriesId = intval($seriesId);

        if (!isset($cache[$seriesId])) {
            if ($seriesId) {
                $seriesCourse = abs($o->getCell('usd_course', '`id` = ' . $seriesId));
                $cache[$seriesId] = $seriesCourse ? $seriesCourse : $globalUsdCourse;
            } else {
                $cache[$seriesId] = $globalUsdCourse;
            }
        }
        return $cache[$seriesId];
    }


    /** Вычиляем и сохраняем мин. цену на серию
     * Если в серии есть товары, составляющие её базовый комлект, то определяется сумма мин. цен этих товаров
     * Иначе берётся минимальная цена самого дешёвого товара в серии
     * @param int $seriesId
     */
    function calcMinPrice($seriesId)
    {
        $seriesId = intval($seriesId);

        $oItems = new Catalog_Items();

        // Вычисляем мин. и макс поисковые цены (т.е. цены среди товаров, входящих в группу, специально указанную в серии - price_search_group_id)
        $groupId = intval($this->getCell('price_search_group_id', '`id` = ' . $seriesId));

        $oCrossItems = new Catalog_Series_CrossItems();
        $crossItemsIds = $oCrossItems->getItemsIds($seriesId, $groupId);
        $crossItemsIds[] = 0;

        $tmp = $oItems->getRow(
            '
				`price_min`,
				IF(`discount` != 1 AND `discount` != 0, `price_min`/`discount`, 0) AS price_min_old
			',
            '
				(
					(`series_id` = ' . $seriesId . ' AND `group_id` = ' . $groupId . ') OR
					`id` IN (' . implode(',', $crossItemsIds) . ')
				) AND
				`price_min` > 0
			',
            '`price_min` ASC'
        );
        $searchPricesMin = round($tmp['price_min'], Catalog::PRICES_DECIMAL);
        $searchPricesMinOld = round($tmp['price_min_old'], Catalog::PRICES_DECIMAL);
        $searchPricesMax = round(
            $oItems->getCell(
                'price_min`',
                '
				(
					(`series_id` = ' . $seriesId . ' AND `group_id` = ' . $groupId . ') OR
					`id` IN (' . implode(',', $crossItemsIds) . ')
				) AND
				`price_min` > 0
			',
                '`price_min` DESC'
            ),
            Catalog::PRICES_DECIMAL
        );

        $this->upd(
            $seriesId,
            array(
                //'price_min'		=> $minPrice['price_min'],
                //'price_min_old'	=> $minPrice['price_min_old'],
                'price_search_min' => $searchPricesMin,
                'price_search_max' => $searchPricesMax,
                'price_search_min_old' => $searchPricesMinOld
            )
        );
    }


    /** Сюда записываются ID серий, для которых нужно в самом конце работы скрипта выполнить calcMinPrice()
     * @see    Catalog_Items::calcMinPrice()
     * @see    Catalog_Items::calcDeferred()
     * @var array
     */
    static $calcDeferredSeriesIds = array();

    /** Отложенный вызов calcMinPrice()
     * Запуск пересчёта цен будет вызван в самом конце работы скрипта (при помощи register_shutdown_function())
     * При этом для каждой серии calcMinPrice() выполнится не более 1-го раза
     * @param int $seriesId
     * @see    Catalog_Items::calcMinMaxPrice()
     * @see    Catalog_Items::$calcDeferredItemsIds
     * @see    register_shutdown_function()
     */
    function calcDeferred($seriesId)
    {
        if (!count(self::$calcDeferredSeriesIds)) {
            register_shutdown_function(array($this, 'calcDeferredRun'));
        }
        self::$calcDeferredSeriesIds[] = intval($seriesId);
    }

    function calcDeferredRun()
    {
        if (!count(self::$calcDeferredSeriesIds)) {
            return;
        }
        self::$calcDeferredSeriesIds = array_map('intval', array_unique(self::$calcDeferredSeriesIds));
        foreach (self::$calcDeferredSeriesIds as $seriesId) {
            $this->calcMinPrice($seriesId);
        }
    }

    /**
     * Gets paginated series list with pagination HTML
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string $fields Fields to select
     * @param string $where WHERE clause
     * @param string $order ORDER BY clause
     * @return array [$items, $toggle, $page, $total]
     */

    public function getByPage(
        int $pgNum = 1,
        int $pgSize = 8,
        string $fields = '',
        string $cond = '',
        string $order = '`id` ASC',
        string $joins = '',
        string $group = ''
    ): array {
        $page = max(1, intval($pgNum));
        $perPage = intval($pgSize) > 0 ? intval($pgSize) : 8; // <= вот так

        $total = $this->getCount($cond);
        $totalPages = ceil($total / $perPage);

        $limit = (($page - 1) * $perPage) . ',' . $perPage;
        $items = $this->get($fields, $cond, $order, $limit, $joins, $group);

        // Получаем базовый URL без page_N
        $requestUri = $_SERVER['REQUEST_URI'];
        $baseUrl = preg_replace('#/page_\d+/?#', '/', $requestUri);
        $baseUrl = rtrim($baseUrl, '/') . '/';

        ob_start();
        // Передаем переменные для пагинации
        $currentPage = $page;
        include(Config::path('skins') . '/html/Components/pagination.htm');
        $toggle = ob_get_clean();

        return array($items, $toggle, $page, $total);
    }



    /** Изменять значение extra_charge для серии нужно этим методом - здесь сразу же обновится наценка для товаров серии в соответствии с extra_charge_action
     * @param int   $seriesId
     * @param float $newExtraCharge
     */
    function updateExtraCharge($seriesId, $newExtraCharge)
    {
        $seriesId = intval($seriesId);
        $seriesInf = $this->getRow(
            'extra_charge, extra_charge_action',
            '`id` = ' . $seriesId
        );
        if (!$seriesInf || round($seriesInf['extra_charge'], Catalog::MULTIPLERS_DECIMAL) == round(
            $newExtraCharge,
            Catalog::MULTIPLERS_DECIMAL
        )) {
            return;
        }

        $this->upd(
            $seriesId,
            array(
                'extra_charge' => $newExtraCharge
            )
        );
        if ($seriesInf['extra_charge_action'] === self::EXTRA_ACTION_NONE) {
            return;
        }

        $oItems = new Catalog_Items();
        if ($seriesInf['extra_charge_action'] === self::EXTRA_ACTION_REPL_ALL) {
            $oItems->updCond(
                '`series_id` = ' . $seriesId . ' AND `extra_charge` != ' . $newExtraCharge,
                array(
                    'extra_charge' => $newExtraCharge
                )
            );
        } elseif ($seriesInf['extra_charge_action'] === self::EXTRA_ACTION_REPL_PART) {
            $oItems->updCond(
                '`series_id` = ' . $seriesId . ' AND `extra_charge` = ' . $seriesInf['extra_charge'] . ' AND `extra_charge` != ' . $newExtraCharge,
                array(
                    'extra_charge' => $newExtraCharge
                )
            );
        }
    }


    /**
     * Для массива серий получаем их:
     *        - 1-е фотографии;
     *        - значения фильтров, которые нужно отобразить в списке серий
     *        - первые MATERIALS_PREVIEW_CNT материалов
     *        - подпись к поисковой цене
     *        - среднюю оценку из отзывов, если они есть
     *
     * Каждой серии добавляются параметры:
     * '1st_photo': false или массив, представляющий собой одну запись из таблицы фотографий;
     * //'filters_values': массив значений фильтров, которые нужно отобразить. Каждое значение - простая строка.
     * 'tags': массив тегов (строк), которые нужно отображать в списке серий
     * 'materials: материалы
     *
     * @param array $series
     * @param bool  $getMaterials
     * @return    array    Изменённый массив $series
     *
     */
    public function details(array $series, bool $getMaterials = false): array
    {
        if (!count($series)) {
            return $series;
        }

        $oCategories = new Catalog_Categories();

        $sIds = array();
        $sKeys = array();

        foreach ($series as $i => $s) {

            $series[$i]['id'] = $sIds[] = intval($s['id']);
            $sKeys[$s['id']] = $i;

            $series[$i]['1st_photo'] = false;
            //$s['filters_values'] = array();
            $series[$i]['tags'] = array();
        }

        if (!count($sIds)) {
            return $series;
        }

        // Получаем 1-е фотографии
        $oPhotos = new Catalog_Series_Photos();
        $photos = $oPhotos->get(
            '`' . Catalog_Series_Photos::$tab . '`.*',
            '`series_id` IN (' . implode(',', $sIds) . ')',
            '',
            0,
            '
				JOIN (
					SELECT MIN(`order`) AS `order`
					FROM `' . Catalog_Series_Photos::$tab . '`
					GROUP BY `series_id`
				) AS `tmptab` ON (`' . Catalog_Series_Photos::$tab . '`.order = `tmptab`.order)
			'
        );
        //dd($oPhotos::$imagePath);
        $photos = $oPhotos->imageExtToData($photos);

        foreach ($photos as $p) {
            $sk = $sKeys[$p['series_id']];

            $p['name'] = $oCategories->nameArr($series[$sk]['category_id']);
            $p['name'][] = $series[$sk]['name'];

            $series[$sk]['1st_photo'] = $p;
        }

        // Получаем теги
        $oPages = new Catalog_Pages();
        $tags = $oPages->get(
            '`' . Catalog_Pages::$tab . '`.name, `sc2p`.series_id',
            '`sc2p`.series_id IN (' . implode(',', $sIds) . ')',
            '`cpg`.order ASC, `catalog_pages`.order ASC',
            0,
            '
				JOIN `' . Catalog_Pages_Groups::$tab . '` AS `cpg`
					ON (`' . Catalog_Pages::$tab . '`.group_id = `cpg`.id AND `cpg`.in_series_list = 1)
				JOIN `' . Catalog_Series_2Pages::$tab . '` AS `sc2p`
					ON (`' . Catalog_Pages::$tab . '`.id = `sc2p`.page_id)
			'
        );

        foreach ($tags as $t) {
            $sk = $sKeys[$t['series_id']];
            $series[$sk]['tags'][] = $t['name'];
        }

        // Получаем маркеры
        $oMarkers = new Catalog_Markers();
        foreach ($series as $i => $s) {
            $tmp = $oMarkers->getForSeries($s['id']);
            //dp($tmp);
            $series[$i]['marker'] = $oMarkers->getForSeries($s['id']);
        }

        // Получаем первые N материалов
        if ($getMaterials) {
            // TODO: Это довольно тяжёлая часть кода (в плане нагрузки на сервер), может быть, стоит что-то здесь закешировать
            $oSeries2Materials = new Catalog_Series_2Materials();
            $tmp = $oSeries2Materials->get(
                'series_id, material_id',
                '`series_id` IN (' . implode(',', $sIds) . ')'
            );
            $mIdsByS = array();
            foreach ($tmp as $ms) {
                $mIdsByS[$ms['series_id']][] = $ms['material_id'];
            }
            $mIds = array();
            foreach ($mIdsByS as &$m) {
                if (count($m) > self::MATERIALS_PREVIEW_CNT) {
                    $m = array_slice($m, 0, self::MATERIALS_PREVIEW_CNT);
                }
                $mIds = array_merge($mIds, $m);
            }
            unset($m);
            if (count($mIds)) {
                $mIds = array_unique($mIds);
                $oMaterials = new Catalog_Materials();
                $materials = $oMaterials->imageExtToData(
                    $oMaterials->getWhtKeys(
                        '*',
                        '`id` IN (' . implode(',', $mIds) . ')',
                        'order'
                    )
                );
            }
            foreach ($series as &$s) {
                $s['materials'] = array();
                if (isset($mIdsByS[$s['id']])) {
                    foreach ($mIdsByS[$s['id']] as $mId) {
                        if (isset($materials[$mId])) {
                            $s['materials'][] = $materials[$mId];
                        }
                    }
                }
            }
            unset($s);
        }

        // Получаем подпись к цене для категорий серий
        $cIds = array();
        foreach ($series as $s) {
            if ($cId = intval($s['category_id'])) {
                $cIds[] = $cId;
            }
        }
        if (count($cIds)) {
            $cIds = array_unique($cIds);
            $oCategories = new Catalog_Categories();
            $priceSearchTitles = $oCategories->getHash(
                'id, price_search_title_series',
                '`id` IN (' . implode(',', $cIds) . ')'
            );
        } else {
            $priceSearchTitles = array();
        }
        foreach ($series as &$s) {
            $cId = intval($s['category_id']);
            $s['price_search_title'] = isset($priceSearchTitles[$cId]) ? $priceSearchTitles[$cId] : '';
        }
        unset($s);

        // Получаем среднюю оценку из отзывов
        $oReviews = new Reviews();

        $rates = $oReviews->getHash(
            'object_id, AVG(`rate`)',
            '
				`object` = \'' . Reviews::OBJ_SERIES . '\' AND
				`object_id` IN (' . implode(',', $sIds) . ') AND
				`rate_allow` = 1
			',
            '',
            0,
            '',
            'object_id'
        );
        #dd('reviews');
        foreach ($series as &$s) {
            if (isset($rates[$s['id']])) {
                $s['rate'] = round($rates[$s['id']]);
            }
        }
        unset($s);

        return $series;
    }


    /**
     * Получаем по несколько серий для каждой переданной категории
     * В первую очередь выбираются серии с отметкой `in_cats_list` (они сортируются по `order`)
     * Если их недостаточно, то выбираются также серии без этой отметки (они сортируются случайно)
     *
     * @param array $categories
     * @param int   $seriesInCatCnt Сколько серий для каждой кат. получить
     * @return    array    Исходный массив $categories, к каждому элементу которого добавлен элемент ['series'], содержащий серии
     */
    public function getForCats(array $categories, int $seriesInCatCnt = 3): array
    {
        $oCategories = new Catalog_Categories();
        $oItems = new Catalog_Items();
        $oItemsLinks = new Catalog_Categories_ItemsLinks();
        $oMaterials = new Catalog_Materials();

        foreach ($categories as &$c) {

            $c['series'] = [];
            $c['items'] = [];
            $c['items-materials'] = [];

            $oCategories->setTab($oCategories::$tab);
            $cIds = $oCategories->getFinishIds($c['id']);

            if (count($cIds)) {
                //if(!intval($oItemsLinks->getCount('`category_id` IN (' . implode(',', $cIds) . ')'))){

                $this->table(self::$tab);
                $_count = $this->getCount('`category_id` IN (' . implode(',', $cIds) . ') AND `out_of_production` = 0');

                if ($_count) {
                    // Категория содержит серии
                    $c['series'] = $this->get(
                        '*',
                        '`category_id` IN (' . implode(
                            ',',
                            $cIds
                        ) . ') AND `in_cats_list` = 1 AND `out_of_production` = 0',
                        'order',
                        $seriesInCatCnt
                    );

                    if (count($c['series']) < $seriesInCatCnt) {
                        $c['series'] = array_merge(
                            $c['series'],
                            $this->get(
                                '*',
                                '`category_id` IN (' . implode(
                                    ',',
                                    $cIds
                                ) . ') AND `in_cats_list` = 0 AND `out_of_production` = 0',
                                'RAND()',
                                $seriesInCatCnt - count($c['series'])
                            )
                        );
                    }
                    $c['series'] = $this->details($c['series']);
                } elseif (intval($oItemsLinks->getCount('`category_id` IN (' . implode(',', $cIds) . ')'))) {
                    // Категория содержит товары
                    $itemsIds = array(0);
                    foreach ($cIds as $cId) {
                        $itemsIds = array_merge(
                            $itemsIds,
                            $oItemsLinks->getItemsIds($cId)
                        );
                    }
                    $itemsIds = array_unique($itemsIds);
                    $items = $oItems->get(
                        '
							`' . Catalog_Items::$tab . '`.*,
							`' . Catalog_Series::$tab . '`.`category_id`,
							`' . Catalog_Series::$tab . '`.`url` AS `s_url`
						',
                        '`' . Catalog_Items::$tab . '`.`id` IN (' . implode(',', $itemsIds) . ')',
                        '
							`' . Catalog_Series::$tab . '`.`order` ASC,
							`' . Catalog_Items::$tab . '`.`order` ASC
						',
                        $seriesInCatCnt,
                        '
							JOIN `' . Catalog_Series::$tab . '` ON (`' . Catalog_Items::$tab . '`.`series_id` = `' . Catalog_Series::$tab . '`.`id`)
						'
                    );
                    $items = $oItems->imageExtToData($items);
                    $items = $oItems->details($items);

                    // Данные материалов
                    $seriesIds = array();
                    foreach ($items as $i) {
                        $seriesIds[] = $i['series_id'];
                    }
                    if (count($seriesIds)) {
                        $seriesIds = array_unique($seriesIds);
                        $materials = $oMaterials->getTree($seriesIds);
                    } else {
                        $materials = array();
                    }

                    $c['items'] = $items;
                    $c['items-materials'] = $materials;
                }
            }
        }
        unset($c);

        return $categories;
    }


    /** Обновляем ключевые слова для поиска для серии
     * @param int $seriesId
     */
    function updateKeywords($seriesId)
    {
        $seriesId = intval($seriesId);

        // Серия
        $seriesInf = $this->getRow(
            'name, category_id',
            '`id` = ' . $seriesId
        );
        if (!$seriesInf) {
            return;
        }

        // Категория
        $oCategories = new Catalog_Categories();
        $catsNames = $oCategories->getChainFields($seriesInf['category_id'], 'name');
        if (!$catsNames) {
            return;
        }

        // Значения фильтров
        $oFiltersValues = new Catalog_Filters_Values();
        $filtersValues = $oFiltersValues->getCol(
            '`' . Catalog_Filters_Values::$tab . '`.`value`',
            'cs2f.`series_id` = ' . $seriesId,
            'cs2f.`id`',
            0,
            '
				JOIN `' . Catalog_Series_2Filters::$tab . '` AS cs2f
					ON (`' . Catalog_Filters_Values::$tab . '`.`id` = cs2f.`value_id`)
			'
        );

        // Значения опций
        $oOptions = new Catalog_Series_Options();
        $options = $oOptions->getCol(
            'value',
            '`series_id` = ' . $seriesId
        );

        $keywords = $seriesInf['name'] .
            ' ' . implode(' ', $catsNames) .
            ' ' . implode(' ', $filtersValues) .
            ' ' . implode(' ', $options);

        $oWordforms = new Wordforms();
        $keywords = $oWordforms->formatKeywords($keywords, true);

        $this->upd(
            $seriesId,
            array(
                'keywords' => $keywords
            )
        );
    }


    /** Получаем ID серии по ID товара
     * @static
     * @param $itemId
     * @return mixed
     */
    static function seriesIdByItem($itemId)
    {
        $itemId = intval($itemId);
        static $oItems;
        if (!$oItems) {
            $oItems = new Catalog_Items();
        }
        static $cache;
        if (!$cache) {
            $cache = array();
        }

        if (!isset($cache[$itemId])) {
            $cache[$itemId] = intval($oItems->getCell('series_id', '`id` = ' . $itemId));
        }
        return $cache[$itemId];
    }


    /** Автоматической добавление сообщения в заметки по серии
     * @param int $note Тип сообщения (константы Catalog_Series::NOTE_...)
     * @param int $seriesId ID серии
     * @param int $itemId ID товара
     * Нужно указать либо ID серии, либо ID товара (по которому будет вычислено ID серии)
     */
    function makeNotes($note, $seriesId = 0, $itemId = 0)
    {
        if (!isset(self::$notesMessages[$note])) {
            return;
        }

        $seriesId = intval($seriesId);
        if (!$seriesId) {
            $seriesId = self::seriesIdByItem($itemId);
            if (!$seriesId) {
                return;
            }
        }

        // Проверяем, не создавалась ли эта же заметка для этой же серии в пределах текущего запуска скрипта
        static $noted;
        if (!$noted) {
            $noted = array();
        }
        if (isset($noted[$note][$seriesId])) {
            return;
        }
        $noted[$note][$seriesId] = 1;

        //
        $msg = date('j.m.Y') . ': ' . self::$notesMessages[$note];
        $note = $this->getCell('admin_notes', '`id` = ' . $seriesId);
        if ($note === false) {
            return;
        }
        //print $seriesId . ': ' . $msg; exit;

        // Проверяем, не совпадает ли новове сообщение с последним сообщением в заметках
        $lastMsg = trim(array_pop(explode("\n", $note)));
        if ($lastMsg === $msg) {
            return;
        }

        // Записываем сообщение в заметки для серии
        if (trim($note) !== '') {
            $note .= "\n" . $msg;
        } else {
            $note = $msg;
        }
        $this->upd($seriesId, array('admin_notes' => $note));
    }


    /** Генерация заголовков и метатегов для страницы серии
     * Если для серии заданы отдельные заголовки/метатеги, то они приоритетнее и не будут генерится
     * Иначе происходит их генерация по шаблонам, заданным для категории
     * @param int $seriesId
     * @return    array
     * array(
     *        'title'    => ...,
     *        'h1'    => ...,
     *        'dscr'    => ...,
     *        'kwrd'    => ...
     * )
     */
    function generateHeadersAndMeta($seriesId)
    {
        $result = array(
            'title' => '',
            'h1' => '',
            'dscr' => '',
            'kwrd' => ''
        );

        $seriesId = intval($seriesId);
        $series = $this->getRow(
            'name, price_min, category_id, title, h1, dscr, kwrd',
            '`id` = ' . $seriesId
        );
        if (!$series) {
            return $result;
        }

        if (trim($series['title']) !== '') {
            $result['title'] = $series['title'];
        }
        if (trim($series['h1']) !== '') {
            $result['h1'] = $series['h1'];
        }
        if (trim($series['dscr']) !== '') {
            $result['dscr'] = $series['dscr'];
        }
        if (trim($series['kwrd']) !== '') {
            $result['kwrd'] = $series['kwrd'];
        }
        if ($result['title'] !== '' && $result['h1'] !== '' && $result['dscr'] !== '' && $result['kwrd'] !== '') {
            // Все параметры заполнены
            return $result;
        }

        $oCategories = new Catalog_Categories();
        $cat = $oCategories->getRow(
            'pattern_series_title, pattern_series_h1, pattern_series_dscr, pattern_series_kwrd',
            '`id` = ' . $series['category_id']
        );
        if (!$cat) {
            return $result;
        }
        foreach ($result as $f => &$r) {
            if (trim($r) === '' && trim($cat['pattern_series_' . $f]) !== '') {
                $r = $cat['pattern_series_' . $f];
                $r = str_replace('[catname]', Catalog_Categories::name1($series['category_id']), $r);
                $r = str_replace('[name]', $series['name'], $r);
                $r = str_replace('[price]', Catalog::priceFormat($series['price_min'], ','), $r);

                /*if(preg_match('/\[filter-[0-9]+\]/ius', $r)){
                    // Значения фильтров серии
                    if(!isset($filters2Values)){
                        $oFiltersValues = new Catalog_Filters_Values();
                        $filters2Values = $oFiltersValues->getHash(
                            '`' . Catalog_Filters_Values::$tab . '`.`filter_id`, `' . Catalog_Filters_Values::$tab . '`.`value`',
                            'cs2f.`series_id` = ' . $seriesId,
                            'cs2f.`id`',
                            0,
                            '
                                JOIN `' . Catalog_Series_2Filters::$tab . '` AS cs2f
                                ON (`' . Catalog_Filters_Values::$tab . '`.`id` = cs2f.`value_id`)
                            '
                        );
                    }

                    // Находим все варианты ID фильтра и далаем замены
                    preg_match_all('/\[filter-[0-9]+\]/ius', $r, $matches);
                    foreach($matches[0] as $fId){
                        $fId = intval(str_replace(array('[filter-', ']'), '', $fId));
                        $r = str_replace(
                            '[filter-' . $fId . ']',
                            trim($filters2Values[$fId]),
                            $r
                        );
                    }
                }*/

                if (preg_match('/\[items-[0-9]+\]/ius', $r)) {
                    // Первые X товаров, входящих в базовый комплект
                    if (!isset($items)) {
                        // Получаем все товары "в комплекте", если ещё этого не делали
                        $oItems = new Catalog_Items();
                        $items = $oItems->get(
                            'name, art',
                            '`series_id` = ' . $seriesId/* . ' AND `in_series_set` = 1'*/,
                            'order'
                        );
                        foreach ($items as &$i) {
                            $i = $i['name'] . ' ' . $i['art'];
                        }
                        unset($i);
                    }

                    // Находим все варианты икса (т.е. все количества, используемые в шаблоне) и далаем замены
                    preg_match_all('/\[items-[0-9]+\]/ius', $r, $matches);
                    foreach ($matches[0] as $cnt) {
                        $cnt = intval(str_replace(array('[items-', ']'), '', $cnt));
                        $r = str_replace(
                            '[items-' . $cnt . ']',
                            implode(
                                ', ',
                                $cnt == 0 ? $items : array_slice($items, 0, $cnt)
                            ),
                            $r
                        );
                    }
                }

                if (preg_match('/\[materials-[0-9]+\]/ius', $r)) {
                    // Первые X материалов
                    if (!isset($materials)) {
                        $oSeries2Materials = new Catalog_Series_2Materials();
                        $materials = $oSeries2Materials->getCol(
                            '`cm`.name',
                            '`series_id` = ' . $seriesId,
                            '`cm`.order',
                            0,
                            'JOIN `' . Catalog_Materials::$tab . '` AS `cm` ON (`cm`.`id` = `' . Catalog_Series_2Materials::$tab . '`.`material_id`)'
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
            }
        }
        unset($r);

        if ($result['h1'] === '') {
            $result['h1'] = $series['name'];
        }
        if ($result['title'] === '') {
            $result['title'] = $series['name'];
        }

        return $result;
    }
}
