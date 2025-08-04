<?php

/**
 * Теговые страницы для категорий
 * Теговая страница - страница со списком товаров категории, подходящая под заданную комбинацию фильтров и имеющую свои метатеги и заголовки
 *
 * @author    Seka
 */

class Catalog_Pages extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_pages';

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
            $oPagesFilters = new Catalog_Pages_Filters();
            $oPagesFilters->delCond('`page_id` IN (' . implode(',', $ids) . ')');

            $oSeries2Pages = new Catalog_Series_2Pages();
            $oSeries2Pages->delCond('`page_id` IN (' . implode(',', $ids) . ')');

            $oItemsLinks = new Catalog_Categories_ItemsLinks();
            $oItemsLinks->delCond('`page_id` IN (' . implode(',', $ids) . ')');
        }

        return $result;
    }


    /** Получаем полный URL теговой страницы
     * @static
     * @param int $pageId
     * @return    bool|string        false, если страница не найдена
     */
    static function a($pageId)
    {
        static $cache = array();
        static $o;
        if (!$o) {
            $o = new self();
        }

        $pageId = intval($pageId);

        if (!isset($cache[$pageId])) {
            $page = $o->getRow('url, group_id', '`id` = ' . $pageId);
            if ($page) {
                $oGroups = new Catalog_Pages_Groups();
                $catId = intval(
                    $oGroups->getCell(
                        'category_id',
                        '`id` = ' . $page['group_id']
                    )
                );
                if ($catId) {
                    $cache[$pageId] = Catalog_Categories::a($catId) . $page['url'] . '/';
                } else {
                    $cache[$pageId] = false;
                }
            } else {
                $cache[$pageId] = false;
            }
        }
        return $cache[$pageId];
    }


    /**
     * @param int $catId
     * @return    array
     */
    function getPagesForCat($catId)
    {
        $catId = intval($catId);
        static $cache;
        if (!$cache) {
            $cache = array();
        }

        if (!isset($cache[$catId])) {
            $oPagesGroups = new Catalog_Pages_Groups();
            $pagesGroups = $oPagesGroups->get(
                '*',
                '`category_id` = ' . $catId,
                'order'
            );
            foreach ($pagesGroups as $n => &$pg) {
                $pg['pages'] = $this->get(
                    'id, name',
                    '`group_id` = ' . $pg['id'],
                    'order'
                );
                if (!count($pg['pages'])) {
                    unset($pagesGroups[$n]);
                }
            }
            unset($pg);

            $cache[$catId] = $pagesGroups;
        }

        return $cache[$catId];
    }


    /**
     * @param int $pageId
     * @return    array
     */
    function getSeriesIds($pageId)
    {
        $pageId = intval($pageId);

        static $cache = array();

        if (isset($cache[$pageId])) {
            return $cache[$pageId];
        }

        // Добавляем прямые указания на связи серий с теговыми страницами
        $oSeries2Pages = new Catalog_Series_2Pages();
        $sIdsByPage = $oSeries2Pages->getCol('series_id', '`page_id` = ' . $pageId);
        //$sIds = array_unique(array_merge($sIdsByFilters, $sIdsByPage));
        $sIds = $sIdsByPage;

        $cache[$pageId] = $sIds;
        return $sIds;
    }


    /**
     * @param int   $catId
     * @param array $filter
     * @return    array|bool
     */
    function searchByFilter($catId, $filter)
    {
        $catId = intval($catId);
        $pagesGroups = $this->getPagesForCat($catId);

        $clear = array();
        foreach ($pagesGroups as $pg) {
            if (isset($filter[$pg['id']])) {
                $values = array();
                foreach ($pg['pages'] as $p) {
                    if (in_array($p['id'], $filter[$pg['id']])) {
                        $values[] = $p['id'];
                    }
                }
                if (count($values)) {
                    $clear[$pg['id']] = $values;
                }
            }
        }
        $filter = $clear;

        if (!count($filter)) {
            return false;
        }

        $seriesIds = false;
        foreach ($filter as $pgId => $pIds) {
            $sIds4PG = array();
            foreach ($pIds as $pId) {
                $sIds4PG = array_merge(
                    $sIds4PG,
                    $this->getSeriesIds($pId)
                );
            }
            if ($seriesIds === false) {
                $seriesIds = $sIds4PG;
            } else {
                $seriesIds = array_intersect($seriesIds, $sIds4PG);
            }
        }

        return $seriesIds;
    }


    /**
     * @param int   $catId
     * @param array $filter
     * @return    array|bool
     */
    function itemsByFilter($catId, $filter)
    {
        $catId = intval($catId);
        $pagesGroups = $this->getPagesForCat($catId);

        $clear = array();
        foreach ($pagesGroups as $pg) {
            if (isset($filter[$pg['id']])) {
                $values = array();
                foreach ($pg['pages'] as $p) {
                    if (in_array($p['id'], $filter[$pg['id']])) {
                        $values[] = $p['id'];
                    }
                }
                if (count($values)) {
                    $clear[$pg['id']] = $values;
                }
            }
        }
        $filter = $clear;

        if (!count($filter)) {
            return false;
        }

        $oItemsLinks = new Catalog_Categories_ItemsLinks();
        $itemsIds = false;
        foreach ($filter as $pgId => $pIds) {
            $iIds4PG = array();
            foreach ($pIds as $pId) {
                $iIds4PG = array_merge(
                    $iIds4PG,
                    $oItemsLinks->getItemsIds($catId, $pId)
                );
            }
            if ($itemsIds === false) {
                $itemsIds = $iIds4PG;
            } else {
                $itemsIds = array_intersect($itemsIds, $iIds4PG);
            }
        }

        return $itemsIds;
    }


    /** Генерация заголовков и метатегов для теговой страницы
     * Если для серии заданы отдельные заголовки/метатеги, то они приоритетнее и не будут генерится
     * Иначе происходит их генерация по шаблонам, заданным для категории
     * @param int $pageId
     * @return    array
     * array(
     *        'title'    => ...,
     *        'h1'    => ...,
     *        'dscr'    => ...,
     *        'kwrd'    => ...
     * )
     */
    function generateHeadersAndMeta($pageId)
    {
        $result = array(
            'title' => '',
            'h1' => '',
            'dscr' => '',
            'kwrd' => '',
            'text' => '' // тэговые страницы - добавляем текст
        );

        $pageId = intval($pageId);
        $page = $this->getRow(
            'name, title, h1, dscr, kwrd, text, group_id',
            '`id` = ' . $pageId
        );
        if (!$page) {
            return $result;
        }

        $oPagesGroups = new Catalog_Pages_Groups();
        $group = $oPagesGroups->getRow('*', '`id` = ' . $page['group_id']);
        if (!$group) {
            return $result;
        }

        if (trim($page['title']) !== '') {
            $result['title'] = $page['title'];
        }
        if (trim($page['h1']) !== '') {
            $result['h1'] = $page['h1'];
        }
        if (trim($page['dscr']) !== '') {
            $result['dscr'] = $page['dscr'];
        }
        if (trim($page['kwrd']) !== '') {
            $result['kwrd'] = $page['kwrd'];
        }
        if (trim($page['text']) !== '') {  // тэговые страницы
            $result['text'] = $page['text'];
        }
        if ($result['title'] !== '' && $result['h1'] !== '' && $result['dscr'] !== '' && $result['kwrd'] !== '') {
            // Все параметры заполнены
            return $result;
        }

        $oCategories = new Catalog_Categories();
        $cat = $oCategories->getRow(
            'pattern_page_title, pattern_page_h1, pattern_page_dscr, pattern_page_kwrd',
            '`id` = ' . $group['category_id']
        );
        foreach ($result as $f => &$r) {
            if ($r === '' && trim($cat['pattern_page_' . $f]) !== '') {
                $r = $cat['pattern_page_' . $f];

                $r = str_replace('[name]', $page['name'], $r);

                if (mb_strpos($r, '[price-min]') !== false) {
                    if (!isset($priceMin)) {
                        // Один раз вычисляем мин. цену для теговой стр.
                        $sIds = $this->getSeriesIds($pageId);

                        $oSeries = new Catalog_Series();
                        $priceMin = $oSeries->getCell(
                            'min(IF((price_search_min OR price_search_max), price_search_min, price_min)) AS `page_min_price`',
                            '`id` IN (' . implode(',', $sIds) . ')'
                        );
                        $priceMin = Catalog::priceFormat($priceMin);
                    }
                    $r = str_replace('[price-min]', $priceMin, $r);
                }
            }
        }
        unset($r);

        if ($result['h1'] === '') {
            $result['h1'] = $page['name'];
        }
        if ($result['title'] === '') {
            $result['title'] = $page['name'];
        }

        return $result;
    }
}
