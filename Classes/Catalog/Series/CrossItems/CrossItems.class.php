<?php

/**
 * По аналогии с тем, как на странице категории или на теговой странице могут выводится товары (не серии),
 * этот класс от вечает за то, чтобы выводить в списке товаров на страницах серий товары из других серий *
 * @see    Catalog_Categories_ItemsLinks
 * @author    Seka
 */

class Catalog_Series_CrossItems extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series_crossitems';


    /**
     * @param array  $data
     * @param string $method
     * @return    int
     * @see    DbList::addArr()
     */
    function addArr($data = array(), $method = self::INSERT)
    {
        $res = parent::addArr($data, $method);

        $seriesIds = array();
        foreach ($data as $d) {
            $seriesIds[] = intval($d['where_series_id']);
        }
        $seriesIds = array_unique($seriesIds);
        $oSeries = new Catalog_Series();
        foreach ($seriesIds as $seriesId) {
            $oSeries->calcDeferred($seriesId);
        }

        return $res;
    }


    /**
     * @param string $cond
     * @return bool
     * @see    DbList::delCond()
     */
    function delCond($cond = '')
    {
        $data = $this->get('where_series_id, item_id', $cond);

        $result = parent::delCond($cond);

        if (count($data)) {
            // Удаляем зависимые данные
            $seriesIds = array();
            foreach ($data as $d) {
                $oSetItems = new Catalog_Series_SetItems();
                $oSetItems->delCond(
                    '`series_id` = ' . $d['where_series_id'] . ' AND `item_id` = ' . $d['item_id']
                );

                $seriesIds[] = intval($d['where_series_id']);
            }

            $seriesIds = array_unique($seriesIds);
            $oSeries = new Catalog_Series();
            foreach ($seriesIds as $seriesId) {
                $oSeries->calcDeferred($seriesId);
            }
        }

        return $result;
    }


    /**
     * @param int $seriesId
     * @param int $groupId
     * @return    array
     */
    function getItemsIds($seriesId, $groupId = 0)
    {
        $seriesId = intval($seriesId);
        $groupId = $groupId !== false ? intval($groupId) : false;

        $cond = array();

        if ($groupId === false) {
            $itemsIds = array_unique(
                $this->getCol(
                    'item_id',
                    '`where_series_id` = ' . $seriesId . ' AND `item_id` != 0'
                )
            );
        } else {
            $itemsIds = array_unique(
                $this->getCol(
                    'item_id',
                    '`where_series_id` = ' . $seriesId . ' AND `where_group_id` = ' . $groupId . ' AND `item_id` != 0'
                )
            );
        }

        if (!count($itemsIds)) {
            return array();
        }

        $oItems = new Catalog_Items();
        return $oItems->getCol(
            'id',
            '`id` IN (' . implode(',', $itemsIds) . ')'
        );
    }
}
