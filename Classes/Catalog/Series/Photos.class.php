<?php

/**
 * Фотографии серий
 *
 * В таблице БД в поле rm указывается способ обрезания картинки, варианты в константах:
 * IMG_RESIZE_CROP, IMG_RESIZE_ADD_MARGINS, IMG_RESIZE_AUTO
 * IMG_RESIZE_AUTO - этот вариант в нашем случае лучше совсем запретить к использованию, т.к. он не впишется в вёрстку
 * @see    Images::resize()
 *
 * @author    Seka
 */

class Catalog_Series_Photos extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series_photos';

    /**
     * @var string
     */
    static $imagePath = '/Series/';

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
    public function addArr($data = array(), $method = self::INSERT): int
    {
        $res = parent::addArr($data, $method);
        $this->setOrderValue();
        return is_null($res) ? 0 : $res;
    }

    /**
     * @param string $cond
     * @return bool
     * @see    DbList::delCond()
     */
    public function delCond($cond = ''): bool
    {
        $ids = $this->getCol('id', $cond);
        $result = parent::delCond($cond);
        $this->imageDel($ids);
        return $result;
    }
}
