<?php

/**
 * Слайдер для главной страницы
 *
 * @author    Seka
 */

class Slider_Index extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'slider_index';

    /**
     * @var string
     */
    static $imagePath = '/SliderIndex/';

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /**
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
        $this->imageDel($ids);
        return $result;
    }
}
