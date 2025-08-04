<?php

/**
 * Хлебные крошки
 *
 * @author    Seka
 */

class BreadCrumbs
{

    /**
     * @var array
     */
    protected static $pagesBC = array();


    /**
     * @param string|int $alias
     * @return    array
     */
    static function forPage($alias)
    {
        if (isset(self::$pagesBC[$alias])) {
            return self::$pagesBC[$alias];
        }

        $oPages = new Pages();
        $id = false;
        if (gettype($alias) == 'string') {
            $id = intval(
                $oPages->getCell(
                    'id',
                    '`alias` = \'' . MySQL::mres($alias) . '\''
                )
            );
        }
        if (!$id) {
            $id = intval($alias);
        }

        $bc = array();
        while ($id) {
            $pg = $oPages->getRow('name, parent_id', '`id` = ' . $id);
            if (!$pg) {
                break;
            }
            $bc[] = array(
                'name' => $pg['name'],
                'url' => Url::a($id)
            );
            $id = $pg['parent_id'];
        }

        $pg = $oPages->getRow('name, parent_id', '`alias` = \'index\'');
        $bc[] = array(
            'name' => $pg['name'],
            'url' => Url::a($id)
        );

        $bc = array_reverse($bc);

        self::$pagesBC[$alias] = $bc;
        return $bc;
    }


    /**
     * Создаем "хлебные крошки" для страницы категории
     *
     * @static
     * @param array $catInf
     * @param int   $pageId
     * @param bool  $isDiscountPage
     * @return    array
     */
    public static function forCategory(array $catInf, int $pageId = 0, bool $isDiscountPage = false)
    {
        $oPages = new Pages();
        $breadcrumbs = [
            [
                'name' => $oPages->getCell('name', '`alias` = \'index\''),
                'url' => Url::a('index')
            ]
        ];

        //de($catInf);

        $oCategories = new Catalog_Categories();
        foreach ($oCategories->getChainFields($catInf['id'], 'id') as $cId) {
            $breadcrumbs[] = array(
                'name' => Catalog_Categories::name1($cId),
                'url' => Catalog_Categories::a($cId)
            );
        }

        $pageId = intval($pageId);
        if ($pageId) {
            $oPages = new Catalog_Pages();
            $page = $oPages->getRow('id, name', '`id` = ' . $pageId);
            if ($page) {
                $breadcrumbs[] = array(
                    'name' => $page['name'],
                    'url' => Catalog_Pages::a($page['id'])
                );
            }
        }

        if ($isDiscountPage) {
            $breadcrumbs[] = array(
                'name' => 'Распродажа',
                'url' => Catalog_Categories::a($catInf['id'], true)
            );
        }

        return $breadcrumbs;
    }


    /** Генерим ХК для страницы серии
     * @static
     * @param array $catInf
     * @param array $seriesInf
     * @return    array
     */
    static function forSeries($catInf, $seriesInf)
    {
        $breadcrumbs = self::forCategory($catInf);
        $breadcrumbs[] = array(
            'name' => $seriesInf['name'],
            'url' => Catalog_Series::a($seriesInf)
        );
        return $breadcrumbs;
    }


    /** Генерим ХК для страницы товара
     * @static
     * @param array $catInf
     * @param array $seriesInf
     * @param array $itemInf
     * @return    array
     */
    static function forItem($catInf, $seriesInf, $itemInf)
    {
        $breadcrumbs = self::forSeries($catInf, $seriesInf);
        $breadcrumbs[] = array(
            'name' => $itemInf['name'] . ' ' . $itemInf['art'],
            'url' => Catalog_Items::a($seriesInf, $itemInf)
        );
        return $breadcrumbs;
    }

}
