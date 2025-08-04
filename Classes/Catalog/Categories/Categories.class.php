<?php

/**
 * Категории каталога
 *
 * @author    Seka
 */

class Catalog_Categories extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_categories';

    /**
     * @var string
     */
    static $imagePath = '/Categories/';

    /**
     * Макс. уровень вложенности категорий
     * 2 - есть категории и подкатегории, а дальше уже товары
     */
    const MAX_DEEP_LEVEL = 4;

    /** Для вычисления полных URL'ов и названий категорий, в этот массив мы изначально загрузим данные по всем категориям, чтобы сократить к-во запросов к БД
     * @var array|bool
     * @see    Catalog_Categories::getChainFields()
     */
    protected static $mainData = false;

    /**
     * @return string
     */
    public static function getTab()
    {
        return self::$tab;
    }

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
            // Удаляем зависимые данные
            $this->imageDel($ids);

            $this->delCond('`parent_id` IN (' . implode(',', $ids) . ')');

            $oSeries = new Catalog_Series();
            $oSeries->delCond('`category_id` IN (' . implode(',', $ids) . ')');

            $oCategories2Filters = new Catalog_Categories_2Filters();
            $oCategories2Filters->delCond('`category_id` IN (' . implode(',', $ids) . ')');

            $oPagesGroups = new Catalog_Pages_Groups();
            $oPagesGroups->delCond('`category_id` IN (' . implode(',', $ids) . ')');

            $oItemsGroups = new Catalog_Items_Groups();
            $oItemsGroups->delCond('`category_id` IN (' . implode(',', $ids) . ')');

            $oOpts4Series = new Catalog_Categories_Opts4Series();
            $oOpts4Series->delCond('`category_id` IN (' . implode(',', $ids) . ')');

            $oItemsLinks = new Catalog_Categories_ItemsLinks();
            $oItemsLinks->delCond('`category_id` IN (' . implode(',', $ids) . ')');
        }

        return $result;
    }


    /** Получаем запрошенное поле для заданной категории и всех её родителей в виде цепочки (массива, от верхнего уровня до заданной категории)
     * @param int    $catId
     * @param string $field
     * @return    array|bool
     * @see    Catalog_Categories::$mainData
     */
    function getChainFields($catId, $field)
    {
        if (self::$mainData == false) {
            self::$mainData = $this->getWhtKeys('id, name, url, parent_id');
        }
        $catId = intval($catId);

        if (!$catId) {
            return false;
        }
        if (!isset(self::$mainData[$catId])) {
            return false;
        }    // Запрошенной категории не существует
        if (!isset(self::$mainData[$catId][$field])) {
            return false;
        }    // Запрошенное поле не входит в перечень возвращаемых данным методом

        $result = array(self::$mainData[$catId][$field]);

        if (self::$mainData[$catId]['parent_id']) {
            // Получаем продолжение цепочки для родительской категории
            $parentRes = $this->getChainFields(self::$mainData[$catId]['parent_id'], $field);
            if ($parentRes === false) {
                return $parentRes;
            }    // Ошибка в структуре родительской категории

            $result = array_merge($parentRes, $result);
        }

        return $result;
    }


    /** Получаем полный URL страницы категории каталога
     * @static
     * @param int  $catId
     * @param bool $discounts
     * @return    bool|string        false, если категория не найдена
     */
    static function a($catId, $discounts = false)
    {
        static $cache = array();
        static $o;
        if (!$o) {
            $o = new self();
        }

        $catId = intval($catId);

        if (!isset($cache[$catId])) {
            $url = $o->getChainFields($catId, 'url');
            if (is_array($url)) {
                //$url = Url::a(Catalog::CATALOG_PAGE_ID) . implode('/', $url) . '/';
                $url = '/' . implode('/', $url) . '/';
            }
            $cache[$catId] = $url;
        }

        $url = $cache[$catId];
        if ($discounts) {
            $url .= Catalog::DISCOUNTS_URL . '/';
        }

        $oDomains = new Catalog_Domains();
        $url = $oDomains->correctUrlForDisplay($url);

        return $url;
    }


    /** Получаем полное название категории каталога
     * @static
     * @param int    $catId
     * @param string $separator
     * @return    bool|string
     */
    static function name($catId, $separator = ' / ')
    {
        static $cache = array();

        if (isset($cache[$catId][$separator])) {
            return $cache[$catId][$separator];
        }

        static $o;
        if (!$o) {
            $o = new self();
        }
        $name = $o->getChainFields($catId, 'name');
        if (is_array($name)) {
            $name = implode($separator, $name);
        }

        $cache[$catId][$separator] = $name;
        return $name;
    }


    /** Быстрый способ получить один любой параметр категории
     * @static
     * @param int    $catId
     * @param string $p
     * @return    mixed
     */
    static function property($catId, $p)
    {
        static $cache = array();

        $catId = intval($catId);
        $p = trim($p);
        if (isset($cache[$catId][$p])) {
            return $cache[$catId][$p];
        }

        $o = new self();

        $cache[$catId][$p] = $o->getCell($p, '`id` = ' . $catId);
        return $cache[$catId][$p];
    }


    /** Быстрый способ получить название категории
     * @static
     * @param int $catId
     * @return    bool|string
     */
    static function name1($catId)
    {
        return self::property($catId, 'name');
    }


    /** Получаем полное название категории каталога в виде массива (род.категория1, род.категория2, категория)
     * @static
     * @param int $catId
     * @return    array
     */
    static function nameArr($catId)
    {
        static $cache = array();

        if (isset($cache[$catId])) {
            return $cache[$catId];
        }

        static $o;
        if (!$o) {
            $o = new self();
        }
        $name = $o->getChainFields($catId, 'name');
        if (!is_array($name)) {
            $name = array();
        }

        $cache[$catId] = $name;
        return $name;
    }


    /** Получаем категории каталога в виде дерева
     * @param string $flds Какие поля получать из БД
     * @param int    $levels К-во требуемых уровней вложенности
     * @param int    $startId Начальная корневая категория (0 - корень каталога)
     * @param string $cond Дополнительное условие выбора категорий (например, только активные)
     * @return    array
     */
    function getTree($flds, $levels = 3, $startId = 0, $cond = '')
    {
        $categories = $this->get(
            $flds . ', has_subcats, pattern_series_dscr, pattern_items_dscr',
            '`parent_id` = ' . intval($startId) . (trim($cond) !== '' ? ' AND (' . $cond . ')' : ''),
            'order'
        );

        if ($levels > 1) {
            // Рекурсивно получаем подкатегории следующего уровня вложенности
            foreach ($categories as &$c) {
                $c['sub'] = $c['has_subcats'] ? $this->getTree($flds, $levels - 1, $c['id'], $cond) : array();
            }
            unset($c);
        }
        return $categories;
    }


    /** Получаем ID конечных категорий, дочерних для заданной
     * @param int  $catId
     * @param bool $getAllNodes false - получаем только конечные категории, true - получаем все категории на пути к конечным
     * @return    array
     * @see        Catalog_Categories::recurseFinishIds()
     */
    function getFinishIds($catId, $getAllNodes = false)
    {
        $catId = intval($catId);
        $result = $this->recurseFinishIds($this->getTree('id', 1000, $catId), $getAllNodes);

        if ($getAllNodes || ($catId && intval($this->getCell('has_subcats', '`id` = ' . $catId)) === 0)) {
            $result[] = $catId;
        }
        return $result;
    }


    /** Возвращает дерево категорий в виде плоского массива
     * Т.е. для обхода дерева не требуется рекурсия
     * @param string $flds Какие поля получать из БД
     * @param int    $levels К-во требуемых уровней вложенности
     * @param int    $startId Начальная корневая категория (0 - корень каталога)
     * @param string $cond Доп. условие выбора категорий (например, только активные)
     * @return    array    Массив блоков
     */
    function getFloatTree($flds, $levels = 3, $startId = 0, $cond = '')
    {
        if (!function_exists('_categoriesTreeToFloat')) {
            function _categoriesTreeToFloat($tree, $level)
            {
                $float = array();
                foreach ($tree as $b) {
                    $sub = isset($b['sub']) ? $b['sub'] : false;
                    unset($b['sub']);

                    $b['level'] = $level;
                    $b['sub_cnt'] = $sub ? count($sub) : 0;

                    $float[] = $b;

                    if ($sub && count($sub)) {
                        $float = array_merge($float, _categoriesTreeToFloat($sub, $level + 1));
                    }
                }
                return $float;
            }
        }
        return _categoriesTreeToFloat(
            $this->getTree($flds, $levels, $startId, $cond),
            $this->getDeepLevel($startId) + 1
        );
    }


    /** Рекурсивно крутим дерево и собираем ID конечных категорий (с товарами)
     * Этот метод идёт как дополнение к getFinishIds() и вызывается только из него
     * @param array $catThree
     * @param bool  $getAllNodes false - собираем только конечные категории, true - собираем все категории на пути к конечным
     * @return    array
     * @see        Catalog_Categories::getFinishIds()
     */
    protected function recurseFinishIds($catThree, $getAllNodes = false)
    {
        $result = array();
        foreach ($catThree as $c) {
            if ($c['has_subcats']) {
                if ($getAllNodes) {
                    $result[] = $c['id'];
                }
                $result = array_merge($result, $this->recurseFinishIds($c['sub'], $getAllNodes));
            } else {
                $result[] = $c['id'];
            }
        }
        return array_unique($result);
    }


    /** Получаем уровень вложенности категории
     * 0 - корень каталога, 1 - кат. верхнего уровня
     * @param int $catId
     * @return    int
     */
    function getDeepLevel($catId)
    {
        $catId = intval($catId);
        return $catId ? count($this->getChainFields($catId, 'id')) : 0;
    }


    /** Обновляем ключевые слова для всех серий в категории
     * @param int $catId
     */
    function updateKeywords4Series($catId)
    {
        $catId = intval($catId);
        $cIds = $this->getFinishIds($catId);
        if (!count($cIds)) {
            return;
        }

        $oSeries = new Catalog_Series();
        $sIds = $oSeries->getCol(
            'id',
            '`category_id` IN (' . implode(',', $cIds) . ')'
        );
        foreach ($sIds as $sId) {
            $oSeries->updateKeywords($sId);
        }
    }
}
