<?php

/**
 * Связь товаров в сериях с матегриалами
 *
 * @author    Seka
 */

class Catalog_Items_2Materials extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_items2materials';


    /** При добавлении записей в данную табл. нужно пересчитывать мин. и макс. цены для товаров
     * @param array  $data
     * @param string $method
     * @return    int
     * @see    DbList::addArr()
     */
    function addArr($data = array(), $method = self::INSERT)
    {
        $res = parent::addArr($data, $method);

        $itemsIds = array();
        foreach ($data as $d) {
            if (isset($d['item_id'])) {
                $itemsIds[] = intval($d['item_id']);
            }
        }
        if (count($itemsIds)) {
            $itemsIds = array_unique($itemsIds);
            $oItems = new Catalog_Items();
            foreach ($itemsIds as $itemId) {
                $oItems->calcDeferred($itemId);
            }
        }

        return $res;
    }


    /** При ЛЮБЫХ изменениях в данной табл. нужно пересчитывать мин. и макс. цены для товаров
     * @param string $cond
     * @param array  $updArr
     */
    function updCond($cond = '', $updArr = array())
    {
        $itemsIds = $this->getCol('item_id', $cond);

        parent::updCond($cond, $updArr);

        $itemsIds = array_merge($itemsIds, $this->getCol('item_id', $cond));
        if (count($itemsIds)) {
            $itemsIds = array_unique($itemsIds);
            $oItems = new Catalog_Items();
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
        $itemsIds = $this->getCol('item_id', $cond);

        $result = parent::delCond($cond);

        if (count($itemsIds)) {
            // При удалении связи товар-материал, нужно пересчитать мин. и макс. цены товара
            $itemsIds = array_unique($itemsIds);
            $oItems = new Catalog_Items();
            foreach ($itemsIds as $itemId) {
                $oItems->calcDeferred($itemId);
            }
        }
        return $result;
    }
}
