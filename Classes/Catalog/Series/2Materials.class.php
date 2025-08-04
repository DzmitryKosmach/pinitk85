<?php

/**
 * Связь серий с материалами
 *
 * @author    Seka
 */

class Catalog_Series_2Materials extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series2materials';

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /**
     * @param string $cond
     * @return bool
     * @see    DbList::delCond()
     */
    function delCond($cond = '')
    {
        $matIds = $seriesIds = array();
        foreach ($this->get('material_id, series_id', $cond) as $s2m) {
            $matIds[] = $s2m['material_id'];
            $seriesIds[] = $s2m['series_id'];
        }
        $matIds = array_unique($matIds);
        $seriesIds = array_unique($seriesIds);

        $result = parent::delCond($cond);

        if (count($matIds) && count($seriesIds)) {
            // При удалении связи серия-материал нужно удалить соответствующие связи товар-материал
            $oItems = new Catalog_Items();
            $itemsIds = $oItems->getCol('id', '`series_id` IN (' . implode(',', $seriesIds) . ')');
            if (count($itemsIds)) {
                $oItems2Materials = new Catalog_Items_2Materials();
                $oItems2Materials->delCond(
                    '`material_id` IN (' . implode(',', $matIds) . ') AND `item_id` IN (' . implode(
                        ',',
                        $itemsIds
                    ) . ')'
                );
            }
        }
        return $result;
    }
}
