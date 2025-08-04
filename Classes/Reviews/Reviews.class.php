<?php

/**
 * Отзывы (о сайте и о сериях)
 *
 * @author    Seka
 */

class Reviews extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'reviews';

    /**
     * @var string
     */
    static $imagePath = '/Reviews/';

    /**
     *
     */
    const OBJ_SITE = 'site';
    const OBJ_SERIES = 'series';

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
        $ids = $this->getCol('id', $cond);
        $result = parent::delCond($cond);
        $this->imageDel($ids);
        return $result;
    }


    /** Отправка уведомления админу
     * @static
     */
    static function notice()
    {
        Email_Tpl::send(
            'review',
            Options::name('admin-review-email'),
            array()
        );

        return true;
    }

}
