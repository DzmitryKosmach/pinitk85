<?php

/**
 * Группы линковок между сериями ("Мы рекомендуем", "Похожие товары" и т.д.)
 *
 * @author    Seka
 */

class Catalog_Series_Linkage extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series_linkage';


    /** Переопределим метод для присвоения order
     * @param array $data
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
            $oSeriesLinkage2Series = new Catalog_Series_Linkage_2Series();
            $oSeriesLinkage2Series->delCond('`linkage_id` IN (' . implode(',', $ids) . ')');
        }

        return $result;
    }


    /**
     * @param string $cond
     * @param array  $updArr
     */
    function updCond($cond = '', $updArr = array())
    {
        if (isset($updArr['sidecol']) && intval($updArr['sidecol'])) {
            parent::updCond(
                '',
                array(
                    'sidecol' => 0
                )
            );
        }
        parent::updCond($cond, $updArr);
    }
}
