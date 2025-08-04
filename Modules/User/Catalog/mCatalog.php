<?php

/** Каталог: категории, серии, товары
 * @author    Seka
 */

class mCatalog
{

    static int $output = OUTPUT_DEFAULT;

    const SERIES_IN_CAT = 5;

    /**
     * Параметр для метода error404()
     * @see    mCatalog::error404()
     */
    const ERR_404_CATEGORY = 1;    // Не найдена категория каталога
    const ERR_404_SERIES = 2;    // Не найдена серия
    const ERR_404_ITEM = 3;    // Не найден товар

    /**
     *
     */
    const SERIES_ON_PAGE_DEFAULT = 30;

    /**
     * @var array
     */
    static array $seriesOnPage = [
        30,
        60,
        90
    ];

    /**
     *
     */
    const SERIES_SORT_DISCOUNT = 'discount';
    const SERIES_SORT_PRICE = 'price';
    const SERIES_SORT_RATE = 'rate';

    /**
     *
     */
    const SERIES_DIR_UP = 'up';
    const SERIES_DIR_DOWN = 'down';

    /**
     * @var int
     */
    static
        $catId = 0,
        $seriesId = 0,
        $itemId = 0,
        $pageId = 0;

    static $isDiscountPage = false;

    private static array $pageInf = [];

    private static CSRF $csrf;

    public function __construct()
    {
        require_once _ROOT . '/Classes/CSRF.class.php';

        self::$csrf = new CSRF();
        self::$csrf->new();
    }


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(array $pageInf = [])
    {

        list($token, $token_expire) = self::$csrf->get();

        $pageInf['csrf_token'] = $token;

        list (
            self::$catId,
            self::$seriesId,
            self::$itemId,
            self::$pageId,
            self::$isDiscountPage) = Catalog::detectByUrl();

        if (isset($_POST['csrf_token'])) {
            self::saveComment($_POST);
        }

        $oCategories = new Catalog_Categories();

        if (self::$catId === false) {
            // Категория не найдена
            return self::error404($pageInf, self::ERR_404_CATEGORY);
        } elseif (
            self::$catId === 0 ||
            (
                !self::$pageId &&
                intval($oCategories->getCell('has_subcats', '`id` = ' . self::$catId))
            )
        ) {
            // Список категорий каталога
            return self::categoriesListPage($pageInf);
        } else/*if(self::$catId)*/ {
            // Категория найдена, содержит серии

            if (self::$seriesId === false) {
                // Серия не найдена
                return self::error404($pageInf, self::ERR_404_SERIES);
            } elseif (self::$seriesId === 0) {
                // Список серий
                return self::seriesListPage($pageInf);
            } else/*if(self::$seriesId)*/ {
                // Серия найдена

                if (self::$itemId === false) {
                    // Товар не найден
                    return self::error404($pageInf, self::ERR_404_ITEM);
                } elseif (self::$itemId === 0) {
                    // Страница серии со списком товаров
                    return self::seriesOnePage($pageInf);
                } else/*if(self::$itemId)*/ {
                    // Страница товара
                    return self::itemOnePage($pageInf);
                }
            }
        }
    }

    public static function getPageInf(): array
    {
        return self::$pageInf;
    }

    /** Выводим сообщение о 404-й ошибке
     * @static
     * @param array $pageInf
     * @param int   $errType self::ERR_404_CATEGORY, self::ERR_404_SERIES или self::ERR_404_ITEM
     * @return    string
     */
    static function error404(&$pageInf, $errType = self::ERR_404_CATEGORY)
    {
        header('HTTP/1.0 404', true, 404);

        // Вывод страницы
        $oPages = new Pages();
        $pageInf = $oPages->getRow('*', '`alias` = \'error-404\'');
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf
        ));
    }


    /** Устанавливаем для страницы тайтл, h1 и метатеги из массива $source, который может категорией, серией или товаром
     * @static
     * @param array $pageInf
     * @param array $source
     * @param int   $pageNum
     * @param bool  $isDiscountPage
     */
    static function setPageInf($pageInf, $source, $pageNum = 1, $isDiscountPage = false)
    {
        $pageNum = intval($pageNum);

        $pageInf['canonical'] = null;

        if ($pageNum > 1) {
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $pageInf['canonical'] = str_replace('/page_' . $pageNum . '/', '/', $actual_link);
        }

        if ($isDiscountPage) {
            if (trim($source['discounts_title']) !== '') {
                $source['title'] = $source['discounts_title'];
            }
            if (trim($source['discounts_h1']) !== '') {
                $source['h1'] = $source['discounts_h1'];
            }
            if (trim($source['discounts_dscr']) !== '') {
                $source['dscr'] = $source['discounts_dscr'];
            }
            if (trim($source['discounts_kwrd']) !== '') {
                $source['kwrd'] = $source['discounts_kwrd'];
            }
        }

        if (trim($source['title']) !== '') {

            $pageInf['title'] = $source['title'];

            if ($pageNum <= 1) {
                $pageInf['title'] = preg_replace('/\{[^}]*\}/iu', '', $pageInf['title']);
            } else {
                $pageInf['title'] = str_replace('{', '', $pageInf['title']);
                $pageInf['title'] = str_replace('}', '', $pageInf['title']);
                $pageInf['title'] = str_replace('%%%', $pageNum, $pageInf['title']);
            }
        }

        if (!is_null($source['h1']) && trim($source['h1']) !== '') {
            $pageInf['header'] = $source['h1'];
        }

        if (!is_null($source['dscr']) && trim($source['dscr']) !== '') {
            $pageInf['dscr'] = ($pageNum <= 1000) ? $source['dscr'] : '';
        }

        if (!is_null($source['kwrd']) && trim($source['kwrd']) !== '') {
            $pageInf['kwrd'] = ($pageNum <= 1000) ? $source['kwrd'] : '';
        }

        if (!is_null($source['text']) && trim($source['text']) !== '') {
            $pageInf['text'] = $source['text'];
        }

        return $pageInf;
    }


    /** Выводим страницу со списком категорий/подкатегорий каталога
     * @static
     * @param array $pageInf
     * @return    string
     */
    public static function categoriesListPage(array $pageInf): string
    {
        $oCategories = new Catalog_Categories();

        if (self::$catId) {
            // Параметры родительской категории

            $catInf = $oCategories->getRow(
                '*',
                '`id` = ' . self::$catId
            );

            if (trim($catInf['title']) === '') {
                $catInf['title'] = $catInf['name'];
            }
            $pageInf = self::setPageInf($pageInf, $catInf);

            // Теговые страницы
            $oPages = new Catalog_Pages();
            $pagesGroups = $oPages->getPagesForCat(self::$catId);
        } else {
            $catInf = [];
            $pagesGroups = [];
        }

        // Категории для указанного родителя
        $categories = $oCategories->imageExtToData(
            $oCategories->get(
                'id, name',
                '`parent_id` = ' . self::$catId,
                'order'
            )
        );

        $oSeries = new Catalog_Series();
        $oSeries->setTab($oSeries::$tab);
        $categories = $oSeries->getForCats($categories, self::SERIES_IN_CAT);

        // Хлебные крошки
        $breadcrumbs = BreadCrumbs::forCategory($catInf);

        $tpl = Pages::tplFile($pageInf, 'toplevel');

        self::$pageInf = $pageInf;

        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'breadcrumbs' => $breadcrumbs,
            'catInf' => $catInf,
            'categories' => $categories,
            'pagesGroups' => $pagesGroups
        ));
    }


    /** Выводим страницу со списком серий в категории
     * @static
     * @param array $pageInf
     * @return    string
     */
    static function seriesListPage(&$pageInf)
    {
        $oCategories = new Catalog_Categories();
        $catsIds = array(self::$catId);

        // Теговые страницы
        $oPages = new Catalog_Pages();
        $pagesGroups = $oPages->getPagesForCat(self::$catId);

        // ТЭГИ
        // Список групп
        $oGroups = new Catalog_Pages_Groups();
        $groups = $oGroups->get(
            '*',
            '`category_id` =' . $catsIds[0],
            'order'
        );

        $pages = array();

        foreach ($groups as $g) {
            $oPages = new Catalog_Pages();
            $temp = $oPages->get(
                '*',
                '`group_id` = ' . $g['id'],
                'order'
            );
            for ($i = 0; $i < count($temp); $i++) {
                array_push($pages, $temp[$i]);
            };
        }
        // ТЭГИ

        if (self::$pageId) {
            unset($_GET['f']);
            unset($_GET['price']);
            unset($_GET['instock']);
            //unset($_GET['onpage']);
            $pageText = trim($oPages->getCell('text', '`id` = ' . self::$pageId));
        } else {
            $pageText = '';
        }

        $oItemsLinks = new Catalog_Categories_ItemsLinks();
        if (!intval($oItemsLinks->getCount('`category_id` = ' . self::$catId))) {
            // Страница со списком серий
            $pageWithSeries = true;

            $search = array(
                '`out_of_production` = 0'
            );
            if (self::$pageId) {
                // Находим серии для теговой страницы
                $catsIds = $oCategories->getFinishIds(self::$catId);
                $catsIds[] = 0;
                $search[] = '`category_id` IN (' . implode(',', $catsIds) . ')';

                $sIds = $oPages->getSeriesIds(self::$pageId);
                $sIds[] = 0;
                $search[] = '`id` IN (' . implode(',', $sIds) . ')';
            } else {
                // Обычный поиск по категории
                $search[] = '`category_id` = ' . self::$catId;

                // Акции и скидки
                if (self::$isDiscountPage) {
                    $search[] = '(`marker_id` != 0 OR `price_min_old` > 0)';
                }

                // Поиск по цене
                if (intval($_GET['price']['from']) || intval($_GET['price']['to'])) {
                    $search[] = '`price_search_group_id` != 0';
                }
                if (intval($_GET['price']['to'])) {
                    $search[] = 'IF((price_search_min OR price_search_max), price_search_min, price_min) <= ' . intval(
                            $_GET['price']['to']
                        );
                    if (!intval($_GET['price']['from'])) {
                        $search[] = 'IF((price_search_min OR price_search_max), price_search_min, price_min) > 0';
                    }
                }
                if (intval($_GET['price']['from'])) {
                    $search[] = 'IF((price_search_min OR price_search_max), price_search_min, price_min) >= ' . intval(
                            $_GET['price']['from']
                        );
                }
                // Только в наличии
                if (intval($_GET['instock'])) {
                    $search[] = '`in_stock` = 1';
                }

                // Поиск по новым фильтрам (на основе теговых страниц)
                if (isset($_GET['f']) && is_array($_GET['f']) && count($_GET['f'])) {
                    $sIdsByFilters = $oPages->searchByFilter(self::$catId, $_GET['f']);
                    if (is_array($sIdsByFilters)) {
                        $sIdsByFilters[] = 0;
                        $search[] = '`id` IN (' . implode(',', $sIdsByFilters) . ')';
                    }
                }
            }
            //print implode(' AND ', $search); exit;
            if (intval($_GET['getcnt'])) {
                // Запрашивается общее к-во серий, подходящих под фильтр
                self::$output = OUTPUT_FRAME;
                $oSeries = new Catalog_Series();
                $seriesCnt = $oSeries->getCount(implode(' AND ', $search));
                return $seriesCnt;    //
            }

            // Сортировка серий
            $sortFld = isset($_GET['sort']) ? trim($_GET['sort']) : '';
            $sortDir = isset($_GET['direction']) ? trim($_GET['direction']) : '';

            if ($sortFld === self::SERIES_SORT_DISCOUNT) {
                $orderBy = '
					IF(
						`price_search_min` OR `price_search_max`,
						IF(
							`price_search_min_old`,
							(`price_search_min_old` / `price_search_min`),
							0
						),
						IF(
							`price_min_old`,
							(`price_min_old` / `price_min`),
							0
						)
					) DESC,
					`order` ASC
				';
            } elseif ($sortFld === self::SERIES_SORT_PRICE) {
                if ($sortDir === self::SERIES_DIR_DOWN) {
                    $orderBy = '
						IF(
							`price_search_min` OR `price_search_max`,
							`price_search_min`,
							`price_min`
						) DESC,
						`order` ASC
					';
                } else {
                    $orderBy = '
						IF(
							`price_search_min` OR `price_search_max`,
							`price_search_min`,
							`price_min`
						) ASC,
						`order` ASC
					';
                }
            } elseif ($sortFld === self::SERIES_SORT_RATE) {
                if ($sortDir === self::SERIES_DIR_UP) {
                    $orderBy = '`rate` ASC, `order` ASC';
                } else {
                    $orderBy = '`rate` DESC, `order` ASC';
                }
            } else {
                $orderBy = 'order';
            }

            // Получаем список серий
            $oSeries = new Catalog_Series();
            $onPage = isset($_GET['onpage']) ? intval($_GET['onpage']) : 0;

            if ($onPage === -1) {
                $series = $oSeries->get(
                    '*',
                    implode(' AND ', $search),
                    $orderBy
                );
                $toggle = '';
                $pgNum = 1;
                $seriesCnt = count($series);
            } else {

                if (!in_array($onPage, self::$seriesOnPage)) {
                    $onPage = self::SERIES_ON_PAGE_DEFAULT;
                }

                list($series, $toggle, $pgNum, $seriesCnt) = $oSeries->getByPage(
                    intval($_GET['page']),
                    $onPage,
                    '*',
                    implode(' AND ', $search),
                    $orderBy
                );
            }

            $series = $oSeries->details($series, true);

            // Общие параметры категории
            if (self::$isDiscountPage) {
                $commonCond = '`category_id` IN (' . implode(
                        ',',
                        $catsIds
                    ) . ') AND (`marker_id` != 0 OR `price_min_old` > 0) AND `out_of_production` = 0';
            } else {
                $commonCond = '`category_id` IN (' . implode(',', $catsIds) . ') AND `out_of_production` = 0';
            }
            // К-во серий
            $seriesInCatCnt = $oSeries->getCount($commonCond);
            // Диапазон цен
            $minMaxPrices = $oSeries->getRow(
                '
					MIN(IF((price_search_min OR price_search_max), price_search_min, price_min)) AS `min`,
					MAX(IF((price_search_min OR price_search_max), price_search_min, price_min)) AS `max`
				',
                '
					(' . $commonCond . ') AND
					`price_search_group_id` != 0 AND
					IF((price_search_min OR price_search_max), price_search_min, price_min) > 0
				',
                '',
                '',
                'category_id'
            );
            $minMaxPrices['min'] = abs($minMaxPrices['min']);
            $minMaxPrices['max'] = abs($minMaxPrices['max']);
        } else {
            // Страница со списком товаров (не серий)
            $pageWithSeries = false;

            $search = array();

            $itemsIds = $oItemsLinks->getItemsIds(self::$catId, self::$pageId);    // ***
            $itemsIds[] = 0;

            if (!self::$pageId) {
                // Обычный поиск по категории (если self::$pageId != 0, то это теговая страница, и это случай покрывается строкой ***)
                $noTagPageItemsIds = $itemsIds;

                // Акции и скидки
                if (self::$isDiscountPage) {
                    $search[] = '`' . Catalog_Items::$tab . '`.`discount` != 1';
                }
                // Поиск по цене
                if (intval($_GET['price']['to'])) {
                    $search[] = '`' . Catalog_Items::$tab . '`.`price_min` <= ' . intval($_GET['price']['to']);
                    if (!intval($_GET['price']['from'])) {
                        $search[] = '`' . Catalog_Items::$tab . '`.`price_min` > 0';
                    }
                }
                if (intval($_GET['price']['from'])) {
                    $search[] = '`' . Catalog_Items::$tab . '`.`price_min` >= ' . intval($_GET['price']['from']);
                }
                // Только в наличии
                if (intval($_GET['instock'])) {
                    $search[] = '`' . Catalog_Series::$tab . '`.`in_stock` = 1';
                }

                // Поиск по новым фильтрам (на основе теговых страниц)
                if (isset($_GET['f']) && is_array($_GET['f']) && count($_GET['f'])) {
                    $iIdsByFilters = $oPages->itemsByFilter(self::$catId, $_GET['f']);
                    if (is_array($iIdsByFilters)) {
                        $iIdsByFilters[] = 0;
                        $itemsIds = array_intersect($itemsIds, $iIdsByFilters);
                    }
                }
            } else {
                $noTagPageItemsIds = $oItemsLinks->getItemsIds(self::$catId);
                $noTagPageItemsIds[] = 0;
            }

            // Только те серии, которые не сняты с производства.
            $search[] = '`' . Catalog_Series::$tab . '`.`out_of_production` = 0';

            //
            $search[] = '`' . Catalog_Items::$tab . '`.`id` IN (' . implode(',', $itemsIds) . ')';

            $oItems = new Catalog_Items();

            if (intval($_GET['getcnt'])) {
                // Запрашивается общее к-во товаров, подходящих под фильтр
                self::$output = OUTPUT_FRAME;
                $itemsCnt = $oItems->query(
                    '
					SELECT COUNT(*) AS `cnt`
					FROM `' . Catalog_Items::$tab . '`
					JOIN `' . Catalog_Series::$tab . '` ON (`' . Catalog_Items::$tab . '`.`series_id` = `' . Catalog_Series::$tab . '`.`id`)
					WHERE ' . implode(' AND ', $search)
                );
                $itemsCnt = intval($itemsCnt[0]['cnt']);
                return $itemsCnt;
            }

            // Сортировка товаров
            $sortFld = isset($_GET['sort']) ? trim($_GET['sort']) : '';
            $sortDir = isset($_GET['direction']) ? trim($_GET['direction']) : '';
            if ($sortFld === self::SERIES_SORT_DISCOUNT) {
                $orderBy = '
					`' . Catalog_Items::$tab . '`.`discount` ASC,
					`' . Catalog_Series::$tab . '`.`order` ASC,
					`' . Catalog_Items::$tab . '`.`order` ASC
				';
            } elseif ($sortFld === self::SERIES_SORT_PRICE) {
                if ($sortDir === self::SERIES_DIR_DOWN) {
                    $orderBy = '
						`' . Catalog_Items::$tab . '`.`price_min` DESC,
						`' . Catalog_Series::$tab . '`.`order` ASC,
						`' . Catalog_Items::$tab . '`.`order` ASC
					';
                } else {
                    $orderBy = '
						`' . Catalog_Items::$tab . '`.`price_min` ASC,
						`' . Catalog_Series::$tab . '`.`order` ASC,
						`' . Catalog_Items::$tab . '`.`order` ASC
					';
                }
            } else/*if($sortFld === self::SERIES_SORT_RATE){
				if($sortDir === self::SERIES_DIR_UP){
					$orderBy = '`rate` ASC, `order` ASC';
				}else{
					$orderBy = '`rate` DESC, `order` ASC';
				}
			}else*/ {
                $orderBy = '
					`' . Catalog_Series::$tab . '`.`order` ASC,
					`' . Catalog_Items::$tab . '`.`order` ASC
				';
            }

            // Получаем список товаров
            $onPage = isset($_GET['onpage']) ? intval($_GET['onpage']) : 0;

            if ($onPage === -1) {
                $items = $oItems->get(
                    '
						`' . Catalog_Items::$tab . '`.*,
						`' . Catalog_Series::$tab . '`.`category_id`,
						`' . Catalog_Series::$tab . '`.`url` AS `s_url`
					',
                    implode(' AND ', $search),
                    $orderBy,
                    0,
                    '
						JOIN `' . Catalog_Series::$tab . '` ON (`' . Catalog_Items::$tab . '`.`series_id` = `' . Catalog_Series::$tab . '`.`id`)
					'
                );
                $toggle = '';
                $pgNum = 1;
                $itemsCnt = count($items);
            } else {
                if (!in_array($onPage, self::$seriesOnPage)) {
                    $onPage = self::SERIES_ON_PAGE_DEFAULT;
                }
                list($items, $toggle, $pgNum, $itemsCnt) = $oItems->getByPage(
                    intval($_GET['page']),
                    $onPage,
                    '
						`' . Catalog_Items::$tab . '`.*,
						`' . Catalog_Series::$tab . '`.`category_id`,
						`' . Catalog_Series::$tab . '`.`url` AS `s_url`
					',
                    implode(' AND ', $search),
                    $orderBy,
                    '
						JOIN `' . Catalog_Series::$tab . '` ON (`' . Catalog_Items::$tab . '`.`series_id` = `' . Catalog_Series::$tab . '`.`id`)
					'
                );
            }

            $items = $oItems->imageExtToData($items);
            $items = $oItems->details($items);

            // Общие параметры категории
            if (self::$isDiscountPage) {
                $commonCond = '`id` IN (' . implode(
                        ',',
                        $noTagPageItemsIds
                    ) . ')'/* AND `items_links_exclude` = 0*/ . ' AND `discount` != 1';
            } else {
                $commonCond = '`id` IN (' . implode(',', $noTagPageItemsIds) . ')'/* AND `items_links_exclude` = 0'*/
                ;
            }
            // К-во товаров
            $itemsInCatCnt = $oItems->getCount($commonCond);
            // Диапазон цен
            $minMaxPrices = $oItems->getRow(
                '
					MIN(`price_min`) AS `min`,
					MAX(`price_min`) AS `max`
				',
                '(' . $commonCond . ') AND `price_min` > 0'
            );

            // Данные материалов
            $seriesIds = array();
            foreach ($items as $i) {
                $seriesIds[] = $i['series_id'];
            }
            if (count($seriesIds)) {
                $seriesIds = array_unique($seriesIds);
                $oMaterials = new Catalog_Materials();
                $materials = $oMaterials->getTree($seriesIds);
            } else {
                $materials = array();
            }
        }

        // Параметры категории
        $catInf = $oCategories->getRow(
            '*',
            '`id` = ' . self::$catId
        );
        if (self::$pageId) {
            $tagPageInfo = $oPages->generateHeadersAndMeta(self::$pageId);
            $pageInf = self::setPageInf($pageInf, $tagPageInfo, $pgNum);
        } else {
            if (trim($catInf['title']) === '') {
                $catInf['title'] = $catInf['name'];
            }
            $pageInf = self::setPageInf($pageInf, $catInf, $pgNum, self::$isDiscountPage);
        }

        // Категории для родителя текущей категории
        if ($catInf['parent_id']) {
            $categories = $oCategories->get(
                'id, name',
                '`parent_id` = ' . $catInf['parent_id'],
                'order'
            );
        } else {
            $categories = array();
        }

        // Хлебные крошки
        $breadcrumbs = BreadCrumbs::forCategory($catInf, self::$pageId, self::$isDiscountPage);

        self::$pageInf = $pageInf;

        $tpl = Pages::tplFile($pageInf, 'category');
        $tplVars = array(
            'pageWithSeries' => $pageWithSeries,

            'pageInf' => $pageInf,
            'categories' => $categories,
            'breadcrumbs' => $breadcrumbs,
            'catInf' => $catInf,
            'isDiscountPage' => self::$isDiscountPage,
            'pagesGroups' => $pagesGroups,
            'currentPageId' => self::$pageId,
            'currentPageText' => $pageText,

            'toggle' => $toggle,
            'minMaxPrices' => $minMaxPrices,

            'pages' => $pages, // ТЭГИ
            'tagPageInfo' => $tagPageInfo, // ТЭГИ
        );

        if ($pageWithSeries) {
            $tplVars['series'] = $series;
            $tplVars['seriesCnt'] = $seriesCnt;
            $tplVars['seriesInCatCnt'] = $seriesInCatCnt;
        } else {
            $tplVars['items'] = $items;
            $tplVars['itemsCnt'] = $itemsCnt;
            $tplVars['itemsInCatCnt'] = $itemsInCatCnt;
            $tplVars['materials'] = $materials;
        }
        //dd($tpl);
        return pattExeP(fgc($tpl), $tplVars);
    }


    /** Выводим страницу серии со списком товаров
     * @static
     * @param array $pageInf
     * @return    string
     */
    static function seriesOnePage(&$pageInf)
    {
        // Параметры категории
        $oCategories = new Catalog_Categories();
        $catInf = $oCategories->getRow(
            '*',
            '`id` = ' . self::$catId
        );

        if (trim($catInf['title']) === '') {
            $catInf['title'] = $catInf['name'];
        }

        $pageInf = self::setPageInf($pageInf, $catInf);

        // Категории для родителя текущей категории
        if ($catInf['parent_id']) {
            $categories = $oCategories->get(
                'id, name',
                '`parent_id` = ' . $catInf['parent_id'],
                'order'
            );
        } else {
            $categories = array();
        }

        Catalog_Recently::add(self::$seriesId);

        // Параметры серии
        $oSeries = new Catalog_Series();
        $seriesInf = $oSeries->getRow(
            '*',
            '`id` = ' . self::$seriesId
        );
        $seriesInf = $oSeries->details(array($seriesInf));
        $seriesInf = $seriesInf[0];

        $pageInf = self::setPageInf(
            $pageInf,
            $oSeries->generateHeadersAndMeta(self::$seriesId)
        );

        // Хлебные крошки
        $breadcrumbs = BreadCrumbs::forSeries($catInf, $seriesInf);

        // Опции серии
        $oSeriesOptions = new Catalog_Series_Options();
        $options = $oSeriesOptions->get(
            'name, value',
            '`series_id` = ' . self::$seriesId,
            'order'
        );

        // Серии, из которых взяты доп.товары
        $oItems = new Catalog_Items();
        $oCrossItems = new Catalog_Series_CrossItems();
        $crossItemsIds = $oCrossItems->getItemsIds(self::$seriesId, false);
        $allSeriesIds = array();
        if (count($crossItemsIds)) {
            $allSeriesIds = array_unique(
                $oItems->getCol(
                    'series_id',
                    '`id` IN (' . implode(',', $crossItemsIds) . ')'
                )
            );
        }
        $allSeriesIds[] = self::$seriesId;

        // Дерево материалов
        $oMaterials = new Catalog_Materials();
        $materials = $oMaterials->getTree($allSeriesIds);
        $currentSeriesMaterialsIds = array_keys($oMaterials->getTree(self::$seriesId));
        foreach ($materials as &$m) {
            $m['current-series'] = false;
        }
        unset($m);
        foreach ($currentSeriesMaterialsIds as $mid) {
            $materials[$mid]['current-series'] = true;
        }

        // Фотографии серии
        $oPhotos = new Catalog_Series_Photos();
        $photos = $oPhotos->imageExtToData(
            $oPhotos->get(
                '*',
                '`series_id` = ' . self::$seriesId,
                'order'
            )
        );

        foreach ($photos as &$p) {
            $p['name'] = $oCategories->nameArr(self::$catId);
            $p['name'][] = $seriesInf['name'];
        }
        unset($p);
        if (count($photos)) {
            Catalog::sharePhotoSeries($photos[0]);
        }

        // Отзывы
        /*
        $oReviews = new Reviews();
        $reviews = $oReviews->imageExtToData(
            $oReviews->get(
                '*',
                '`object` = \'' . Reviews::OBJ_SERIES . '\' AND `object_id` = ' . self::$seriesId . ' AND `approved` = 1',
                '`date` DESC, `id` DESC'
            )
        );*/

        // Линковки для серии
        $oLinkage = new Catalog_Series_Linkage();
        $linkages = $oLinkage->get(
            '*',
            '',
            'order'
        );
        $oSeriesLinkage2Series = new Catalog_Series_Linkage_2Series();
        foreach ($linkages as $ln => &$l) {
            $sIds = array_unique(
                array_merge(
                    $oSeriesLinkage2Series->getCol(
                        'series2_id',
                        '`series1_id` = ' . self::$seriesId . ' AND `linkage_id` = ' . $l['id']
                    ),
                    $oSeriesLinkage2Series->getCol(
                        'series1_id',
                        '`series2_id` = ' . self::$seriesId . ' AND `linkage_id` = ' . $l['id']
                    )
                )
            );
            if (!count($sIds)) {
                unset($linkages[$ln]);
                continue;
            }
            $series = $oSeries->get(
                '*',
                '`id` IN (' . implode(',', $sIds) . ') AND `out_of_production` = 0',
                'RAND()',
                self::SERIES_IN_CAT
            );
            if (!count($series)) {
                unset($linkages[$ln]);
                continue;
            }
            $l['series'] = $oSeries->details($series, true);
        }
        unset($l);

        // Данные о дилере
        $dealer = Dealers_Security::getCurrent();
        $dealerExtra = 1;
        if ($dealer) {
            $extras = Dealers_Extra::getForDealer($dealer['id']);
            if (isset($extras[$seriesInf['supplier_id']])) {
                $dealerExtra = $extras[$seriesInf['supplier_id']];
            }
        }

        $itemsCnt =
            $oItems->getCount('`series_id` = ' . self::$seriesId) +
            $oCrossItems->getCount('`where_series_id` = ' . self::$seriesId);

        if (1 != $itemsCnt) {
            // В СЕРИИ БОЛЬШЕ ОДНОГО ТОВАРА (или товаров 0)
            // Товары в базовом комплекте
            $oSetItems = new Catalog_Series_SetItems();
            $tmp = $oSetItems->get(
                '*',
                '`series_id` = ' . self::$seriesId
            );
            $setItems = array();
            $seriesSetItemsIds = array();
            foreach ($tmp as $i) {
                $seriesSetItemsIds[$i['item_id']] = 1;
                $setItems[$i['item_id']] = $i;
            }
            $seriesSet = array();

            // Группы товаров
            $oItemsGroups = new Catalog_Items_Groups();
            $itemsGroups = $oItemsGroups->getForSeries(self::$seriesId, false, true);
            $itemsGroupsIds = array_keys($itemsGroups);
            $itemsGroupsIds[] = '-1';
            array_unshift($itemsGroups, array(    // Чтобы выбрать также товары без группы
                'id' => 0,
                'name' => ''
            ));

            // Товары по группам
            $crossSeriesIds = array();
            foreach ($itemsGroups as $gn => &$g) {
                $crossItemsIds = $oCrossItems->getItemsIds(self::$seriesId, $g['id']);
                $crossItemsIds[] = 0;

                if ($g['id'] != 0) {
                    $items = $oItems->imageExtToData(
                        $oItems->get(
                            '*',
                            '
							`id` IN (' . implode(',', $crossItemsIds) . ') OR
							(`series_id` = ' . self::$seriesId . ' AND `group_id` = ' . $g['id'] . ')
						',
                            'order'
                        )
                    );
                } else {
                    $items = $oItems->imageExtToData(
                        $oItems->get(
                            '*',
                            '
							`id` IN (' . implode(',', $crossItemsIds) . ') OR
							(`series_id` = ' . self::$seriesId . ' AND `group_id` NOT IN (' . implode(
                                ',',
                                $itemsGroupsIds
                            ) . '))
						',
                            'order'
                        )
                    );
                }
                if (!count($items)) {
                    unset($itemsGroups[$gn]);
                    continue;
                }
                $items = $oItems->details($items);
                foreach ($items as $i) {
                    if (isset($seriesSetItemsIds[$i['id']])) {
                        $tmp = $i;
                        $tmp['in_series_set_amount'] = $setItems[$i['id']]['amount'];
                        $tmp['on_series_photo'] = $setItems[$i['id']]['on_photo'];
                        $tmp['on_series_photo_x'] = $setItems[$i['id']]['on_photo_x'];
                        $tmp['on_series_photo_y'] = $setItems[$i['id']]['on_photo_y'];
                        $seriesSet[] = $tmp;
                    }

                    $crossSeriesIds[] = $i['series_id'];
                }
                $g['items'] = $items;
            }
            unset($g);

            $crossSeriesIds = array_unique($crossSeriesIds);
            if (count($crossSeriesIds)) {
                $crossSeries = $oSeries->getWhtKeys(
                    'id, category_id, url',
                    '`id` IN (' . implode(',', $crossSeriesIds) . ')'
                );
            } else {
                $crossSeries = array();
            }

            $comments = self::getSeriesComments(self::$seriesId);

            self::$pageInf = $pageInf;

            $tpl = Pages::tplFile($pageInf, 'series');

            return pattExeP(
                fgc($tpl),
                [
                    'pageInf' => $pageInf,
                    'categories' => $categories,
                    'breadcrumbs' => $breadcrumbs,
                    'catInf' => $catInf,
                    'seriesInf' => $seriesInf,
                    'options' => $options,
                    'materials' => $materials,
                    'photos' => $photos,
                    'seriesSet' => $seriesSet,
                    'itemsGroups' => $itemsGroups,
                    'crossSeries' => $crossSeries,
                    //'reviews' => $reviews,
                    'linkages' => $linkages,
                    'dealer' => $dealer,
                    'dealerExtra' => $dealerExtra,
                    'comments' => $comments,
                ]
            );

        } else {

            // В СЕРИИ РОВНО ОДИН ТОВАР
            if ($oItems->getCount('`series_id` = ' . self::$seriesId)) {
                $iId = $oItems->getCell(
                    'id',
                    '`series_id` = ' . self::$seriesId
                );
            } else {
                // Товар на самом деле из другой серии
                $_gi = $oCrossItems->getItemsIds(self::$seriesId, false);
                $iId = array_shift($_gi);
            }

            $itemInf = $oItems->imageExtToData(
                $oItems->getRow(
                    '*',
                    '`id` = ' . $iId
                )
            );

            $_details = $oItems->details(array($itemInf));

            $itemInf = array_shift($_details);

            self::$pageInf = $pageInf;

            $comments = self::getSeriesComments(self::$seriesId);

            $tpl = Pages::tplFile($pageInf, 'series_single_item');

            return pattExeP(
                fgc($tpl),
                [
                    'pageInf' => $pageInf,
                    'categories' => $categories,
                    'breadcrumbs' => $breadcrumbs,
                    'catInf' => $catInf,
                    'seriesInf' => $seriesInf,
                    'options' => $options,
                    'materials' => $materials,
                    'photos' => $photos,
                    'itemInf' => $itemInf,
                    'reviews' => $reviews,
                    'linkages' => $linkages,
                    'dealer' => $dealer,
                    'dealerExtra' => $dealerExtra,
                    'comments' => $comments,
                ]
            );
        }
    }


    /** Выводим страницу одного товара с материалами
     * @static
     * @param array $pageInf
     * @return    string
     */
    static function itemOnePage(&$pageInf)
    {
        // Если этот товар единственный в серии, редирект на стр. серии
        $oItems = new Catalog_Items();
        /*if(1 == $oItems->getCount('`series_id` = ' . self::$seriesId)){
            $oSeries = new Catalog_Series();
            $seriesInf = $oSeries->getRow('*', '`id` = ' . self::$seriesId);
            header('Location: ' . Catalog_Series::a($seriesInf), true, 301);
            exit;
        }*/

        // Эта страница может открываться только для отображения в попапе
        $ajax = intval($_GET['ajax']) != 0;
        if (!$ajax) {
            return self::error404($pageInf, self::ERR_404_ITEM);
        }
        self::$output = OUTPUT_FRAME;

        // Параметры категории
        $oCategories = new Catalog_Categories();
        $catInf = $oCategories->getRow(
            '*',
            '`id` = ' . self::$catId
        );
        if (trim($catInf['title']) === '') {
            $catInf['title'] = $catInf['name'];
        }
        $pageInf = self::setPageInf($pageInf, $catInf);

        // Категории для родителя текущей категории
        if ($catInf['parent_id']) {
            $categories = $oCategories->get(
                'id, name',
                '`parent_id` = ' . $catInf['parent_id'],
                'order'
            );
        } else {
            $categories = array();
        }

        // Параметры серии
        $oSeries = new Catalog_Series();
        $seriesInf = $oSeries->getRow(
            '*',
            '`id` = ' . self::$seriesId
        );
        $seriesInf = array_shift($oSeries->details(array($seriesInf)));
        $seriesHeadersAndMeta = $oSeries->generateHeadersAndMeta(self::$seriesId);
        $pageInf = self::setPageInf(
            $pageInf,
            $seriesHeadersAndMeta
        );

        // Параметры товара
        $itemInf = $oItems->imageExtToData(
            $oItems->getRow(
                '*',
                '`id` = ' . self::$itemId
            )
        );
        $headersAndMeta = $oItems->generateHeadersAndMeta(self::$itemId);
        $itemInf['text'] = $headersAndMeta['text'];
        $pageInf = self::setPageInf(
            $pageInf,
            $headersAndMeta
        );
        if ($itemInf['_img_ext']) {
            Catalog::sharePhotoSeries($itemInf);

            $itemInf['image-name'] = $oCategories->nameArr(self::$catId);
            $itemInf['image-name'][] = $seriesInf['name'];
            $itemInf['image-name'][] = $itemInf['art'];
        }


        // Хлебные крошки
        $breadcrumbs = BreadCrumbs::forItem($catInf, $seriesInf, $itemInf);

        // Данные о дилере
        $dealer = Dealers_Security::getCurrent();
        $dealerExtra = 1;
        if ($dealer) {
            $extras = Dealers_Extra::getForDealer($dealer['id']);
            if (isset($extras[$seriesInf['supplier_id']])) {
                $dealerExtra = $extras[$seriesInf['supplier_id']];
            }
        }

        // Базовая цена товара
        $itemPrice = $itemInf['price'];
        if ($itemInf['currency'] === Catalog::USD) {
            $itemPrice = $itemPrice * Catalog_Series::usdCourse(self::$seriesId);
        }
        if (abs($itemInf['discount']) != 1) {
            $itemPriceOld = $itemPrice * $itemInf['extra_charge'];
        } else {
            $itemPriceOld = 0;
        }
        $itemPrice = $itemPrice * $itemInf['extra_charge'] * $itemInf['discount'];

        // Материалы товара
        $oItems2Materials = new Catalog_Items_2Materials();
        $itemInf['materials'] = $oItems2Materials->get(
            '*',
            '`item_id` = ' . self::$itemId
        );
        foreach ($itemInf['materials'] as &$m) {
            if ((float)$m['price']) {
                if ($m['currency'] === Catalog::USD) {
                    $m['price'] = $m['price'] * Catalog_Series::usdCourse(self::$seriesId);
                }
                if (abs($itemInf['discount']) != 1) {
                    $m['price-old'] = $m['price'] * $itemInf['extra_charge'];
                } else {
                    $m['price-old'] = 0;
                }
                $m['price'] = $m['price'] * $itemInf['extra_charge'] * $itemInf['discount'];
            } else {
                $m['price'] = $itemPrice;
                $m['price-old'] = $itemPriceOld;
            }

            if ($dealer && $dealer['show_in_price']) {
                $m['price-in'] = $m['price'] / ($itemInf['extra_charge'] * $itemInf['discount']) * $dealerExtra;
            } else {
                $m['price-in'] = 0;
            }
        }
        unset($m);

        // Дерево материалов
        $oMaterials = new Catalog_Materials();
        $materials = $oMaterials->getTree(self::$seriesId);

        $ajax = intval($_GET['ajax']) != 0;

        if ($ajax) {
            self::$output = OUTPUT_FRAME;
        }

        self::$pageInf = $pageInf;

        $tpl = Pages::tplFile($pageInf, 'item');
        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'categories' => $categories,
            'breadcrumbs' => $breadcrumbs,
            'catInf' => $catInf,
            'seriesInf' => $seriesInf,
            'seriesHeadersAndMeta' => $seriesHeadersAndMeta,
            'itemInf' => $itemInf,
            'materials' => $materials,

            'ajax' => $ajax,

            'dealer' => $dealer,
            'dealerExtra' => $dealerExtra
        ));
    }

    /**
     * Массив комментариев к серии
     * Кол-во комментариев ограничено 50 записью.
     *
     * @param int $seriesId ID серии
     * @return array
     */
    private static function getSeriesComments(int $seriesId): array
    {
        $reviews = new Reviews();

        return $reviews->get(
            'id, date, name, text, rate',
            '`object_id`=' . $seriesId . ' and approved = 1',
            '`date` DESC',
            50);
    }


    /**
     * Сохранения отзыва.
     *
     * В функции использован "базовый набор" для защиты формы от примитивного спама.
     *
     * @param array $data
     * @return void
     */
    private static function saveComment(array $data): void
    {
        $name = isset($data['REVIEW_AUTHOR']) ? trim($data['REVIEW_AUTHOR']) : '';
        $email = isset($data['REVIEW_AUTHOR_EMAIL']) ? trim($data['REVIEW_AUTHOR_EMAIL']) : '';
        $comment = isset($data['REVIEW_TEXT_comment']) ? trim($data['REVIEW_TEXT_comment']) : '';
        $rating = isset($data['REVIEW_TEXT_rate']) ? intval($data['REVIEW_TEXT_rate']) : 0;
        $seriesId = isset($data['series']) ? intval($data['series']) : 0;

        $csrfToken = isset($data['csrf_token']) ? trim($data['csrf_token']) : '';

        // Откуда пришли
        $backUrl = !$_SERVER['REQUEST_URI'] ? '' : $_SERVER['REQUEST_URI'] . "#review";

        // Если пришли как-то анонимно, то такие нам не нужны
        if (!$backUrl) {
            header('Location: /');
        }

        if ($rating < 0 || $rating > 5) {
            $rating = 0;
        }

        $name = self::clearValue($name);
        $email = self::clearValue($email);
        $comment = self::clearValue($comment);

        $_SESSION['review_name'] = $name;
        $_SESSION['review_email'] = $email;
        $_SESSION['review_comment'] = $comment;
        $_SESSION['review_rating'] = $rating;


        // Проверка токена
        if( !$csrfToken || self::$csrf->isExpired() || !self::$csrf->isValid($csrfToken)) {
            self::$csrf->drop();
            self::flash('Данные устарели. Пожалуйста, попробуйте еще раз.', true, $backUrl);
        }

        // Валидация данных
        if (!$seriesId) {
            self::flash('Извините, но мы не можем добавить комментарий к данной серии товара.', true, $backUrl);
        }

        if (!$name || strlen($name) > 192) {
            self::flash('Пожалуйста, представьтесь.', true, $backUrl);
        }

        if (strpos(' '. $name, 'http')) {
            self::flash('Пожалуйста, представьтесь.', true, $backUrl);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::flash('Пожалуйста, укажите корректный электронный адрес.', true, $backUrl);
        }

        if ($rating == 0) {
            // Без рейтинга нельзя добавить
            self::flash('Пожалуйста, поставьте оценку товару.', true, $backUrl);
        }

        if (strlen($comment) < 16) {
            self::flash('Пожалуйста, напишите более информативный отзыв.', true, $backUrl);
        }

        if (strpos(' '. $comment, 'http')) {
            self::flash('Пожалуйста, не пишите ссылки в отзыве.', true, $backUrl);
        }

        if ($name == $comment) {
            self::flash('Пожалуйста, уточните ваш комментарий', true, $backUrl);
        }

        if (self::checkBadWords($comment)) {
            self::flash(
                'Ваш комментарий похож на спам. Измените его так, чтобы в нем не было рекламы.',
                true,
                $backUrl
            );
        }

        if (!self::hasRussianLetters($comment)) {
            self::flash(
                'Напишите ваш комментарий на русском языке.',
                true,
                $backUrl
            );
        }

        $oReviews = new Reviews();
        $oReviews->add(array(
            'object_id' => $seriesId,
            'date' => date('Y-m-d H:i:s'),
            'name' => $name,
            'email' => $email,
            'text' => $comment,
            'rate' => $rating,
            'object' => 'series',
            'ip' => $_SERVER['REMOTE_ADDR'],
            'approved' => 0
        ));

        Reviews::notice();

        self::$csrf->drop();

        self::flash(
            'Ваш отзыв успешно отправлен. Он будет опубликован после проверки модератором.',
            false,
            $backUrl
        );

        exit();
    }

    /**
     * Проверка текста на наличия в нем запрещенных слов.
     *
     * @param string $text
     * @return bool
     */
    private static function checkBadWords(string $text): bool
    {
        // Словарь стоп-слов, которые попадают под фильтр
        $words = [
            'fuck',
            'bitch',
            'пезд',
            'porno',
            '1хбет',
            '1xbet',
            'промоакци',
            'секс',
            'sex',
            'эротик',
            'cialis',
            'buy',
            'рассылк',
            '(точка)',
            '[точка]',
            'депозит',
            'букмекер',
            'video',
        ];

        $text = mb_strtolower($text);
        foreach ($words as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    private static function hasRussianLetters(string $input): bool
    {
        return (bool) preg_match('/[а-яё]/iu', $input);
    }

    /**
     * Это почти копия Pages::flash()
     *
     * Отдельная функция из-за того, что результат обработки события происходит в основной функции и там, где
     * это совсем не нужно.
     *
     * @param $text
     * @param $err
     * @param $backUrl
     * @return void
     */
    private static function flash($text, $err = false, $backUrl = false)
    {
        if (trim($text) !== '') {
            $class = $err ? 'errortext' : 'notetext';
            $_SESSION['review_flash_msg'] = '<div class="' . $class . '">'. $text . '</div>';
            $_SESSION['review_flash_status'] = $err;
        }

        if (!$backUrl) {
            $backUrl = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '/';
        }

        header('Location: ' . $backUrl);
        exit;
    }

    private static function clearValue(string $value): string
    {

        $value = strip_tags($value);
        $value = str_replace('<?', '', $value);
        $value = str_replace('?>', '', $value);
        $value = str_replace('`', '', $value);
        $value = str_replace("'", '', $value);
        //$value = str_replace('', '', $value);
        $value = htmlentities($value);
        $value = htmlspecialchars($value);
        $value = trim($value);

        return $value;
    }
}
