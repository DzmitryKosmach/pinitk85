<?php

/**
 * Группы товаров (в пределах серии)
 * Группы настраиваются для каждой категории каталога и распространяются на серии в ней
 *
 * @author    Seka
 */

class Catalog_Items_Groups extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_items_groups';


    /**
     * Переопределим метод для присвоения order
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
            $oItemsGroupsOptions = new Catalog_Items_Groups_Options();
            $oItemsGroupsOptions->delCond('`group_id` IN (' . implode(',', $ids) . ')');

            $oItemsLinks = new Catalog_Categories_ItemsLinks();
            $oItemsLinks->delCond('`group_id` IN (' . implode(',', $ids) . ')');

            $oCrossItems = new Catalog_Series_CrossItems();
            $oCrossItems->delCond('`where_group_id` IN (' . implode(',', $ids) . ')');

            $oItems = new Catalog_Items();
            $oItems->updCond(
                '`group_id` IN (' . implode(',', $ids) . ')',
                array(
                    'group_id' => 0
                )
            );

            $oSeries = new Catalog_Series();
            $oSeries->updCond(
                '`price_search_group_id` IN (' . implode(',', $ids) . ')',
                array(
                    'price_search_group_id' => 0
                )
            );
        }

        return $result;
    }


    /**
     * @param int $catId
     * @return    array
     */
    function getForCat($catId)
    {
        $catId = intval($catId);
        return $this->getWhtKeys(
            '*',
            '`category_id` = ' . $catId,
            'order'
        );
    }


    /**
     * @param int  $seriesId
     * @param bool $itemsCntForLinks
     * @param bool $crossItems
     * @return    array
     */
    function getForSeries($seriesId, $itemsCntForLinks = false, $crossItems = false)
    {
        $seriesId = intval($seriesId);

        // Определяем, из какой категории серия
        $oSeries = new Catalog_Series();
        $catId = intval(
            $oSeries->getCell(
                'category_id',
                '`id` = ' . $seriesId
            )
        );
        if (!$catId) {
            return array();
        }

        // Получаем группы для серий этой категории
        $groups = $this->getWhtKeys(
            '*',
            '`category_id` = ' . $catId,
            'order'
        );

        // Исключаем группы, в которых в данной серии нет товаров
        $oItems = new Catalog_Items();
        $oCrossItems = new Catalog_Series_CrossItems();
        foreach ($groups as $gn => &$g) {
            $cnt = intval(
                $oItems->getCount(
                    '`series_id` = ' . $seriesId . ' AND `group_id` = ' . $g['id']/* . ($itemsCntForLinks ? ' AND `items_links_exclude` = 0' : '')*/
                )
            );
            if ($crossItems) {
                $crossCnt = $oCrossItems->getCount(
                    '`where_series_id` = ' . $seriesId . ' AND `where_group_id` = ' . $g['id']
                );
            } else {
                $crossCnt = 0;
            }
            if (!$cnt && !$crossCnt) {
                unset($groups[$gn]);
                continue;
            }
            $g['items-count'] = $cnt;
        }
        unset($g);

        // Получаем опии групп, характерные для данной серии
        if (count($groups)) {
            foreach ($groups as &$g) {
                $g['h2'] = 0;
            }
            unset($g);

            $oItemsGroupsOptions = new Catalog_Items_Groups_Options();
            $options = $oItemsGroupsOptions->get(
                '*',
                '`series_id` = ' . $seriesId . ' AND `group_id` IN (' . implode(',', array_keys($groups)) . ')'
            );
            foreach ($options as $o) {
                $groups[$o['group_id']]['h2'] = $o['h2'];
            }
        }
        return $groups;
    }
}
