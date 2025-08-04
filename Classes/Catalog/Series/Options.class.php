<?php

/**
 * Опции (характеристики) серий
 *
 * @author    Seka
 */

class Catalog_Series_Options extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series_options';

    /**
     * Макс. к-во характеристик в одной серии
     */
    //const MAX_CNT = 10;


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
}
