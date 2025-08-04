<?php

/**
 * Маркеры-уголки для серий
 *
 * @author    Seka
 */

class Catalog_Markers extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_markers';

    /**
     * Варианты цветов маркеров
     */
    const GREEN = 'green';
    const SKYBLUE = 'skyblue';
    const ORANGE = 'orange';
    const RED = 'red';
    const BLUE = 'blue';
    const YELLOW = 'yellow';
    const WHITE = 'white';
    const BLACK = 'black';
    const PURPLE = 'purple';
    const BROWN = 'brown';

    /**
     * Параметры каждого цвета
     * @var array
     */
    static $colors = array(
        self::GREEN => array(
            'file' => '/Skins/img/user/markers/1.png',
            'name' => 'Зелёный',
            'color' => '#008000',
            'shadow' => true
        ),
        self::BLUE => array(
            'file' => '/Skins/img/user/markers/2.png',
            'name' => 'Синий',
            'color' => '#0000ff',
            'shadow' => true
        ),
        self::ORANGE => array(
            'file' => '/Skins/img/user/markers/3.png',
            'name' => 'Оранжевый',
            'color' => '#ffa500',
            'shadow' => false
        ),
        self::SKYBLUE => array(
            'file' => '/Skins/img/user/markers/4.png',
            'name' => 'Голубой',
            'color' => '#87ceeb',
            'shadow' => true
        ),
        self::BROWN => array(
            'file' => '/Skins/img/user/markers/5.png',
            'name' => 'Коричневый',
            'color' => '#a52a2a',
            'shadow' => true
        ),
        self::PURPLE => array(
            'file' => '/Skins/img/user/markers/6.png',
            'name' => 'Фиолетовый',
            'color' => '#800080',
            'shadow' => false
        ),
        self::BLACK => array(
            'file' => '/Skins/img/user/markers/7.png',
            'name' => 'Чёрный',
            'color' => '#000',
            'shadow' => true
        ),
        self::WHITE => array(
            'file' => '/Skins/img/user/markers/8.png',
            'name' => 'Белый',
            'color' => '#FFF',
            'shadow' => false
        ),
        self::YELLOW => array(
            'file' => '/Skins/img/user/markers/9.png',
            'name' => 'Жёлтый',
            'color' => '#ffff00',
            'shadow' => false
        ),
        self::RED => array(
            'file' => '/Skins/img/user/markers/10.png',
            'name' => 'Красный',
            'color' => '#ff0000',
            'shadow' => true
        )
    );

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /** Получаем маркер для серии
     * @param int $seriesId
     * @return    array|bool    false или параметры маркера
     * @see    Catalog_Markers::$colors
     */
    public function getForSeries(int $seriesId): array|bool
    {
        $oSeries = new Catalog_Series();

        $mId = intval($oSeries->getCell('marker_id', '`id` = ' . $seriesId));
        //dp($mId);
        if (!$mId) {
            return false;
        }

        $this->setTab(self::$tab);
        $markerInfo = $this->getRow('*', '`id` = ' . $mId);
        //dd($this->tab(), $markerInfo);
        if (!$markerInfo) {
            return false;
        }

        $marker = self::$colors[$markerInfo['color']];
        $marker['text'] = self::formatText($markerInfo['text']);
        $marker['padding'] = $markerInfo['padding'];

        return $marker;
    }


    /**
     * @param string $text
     * @return    string
     */
    static function formatText($text)
    {
        return str_replace('//', '<br>', $text);
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
            $oSeries = new Catalog_Series();
            $oSeries->updCond(
                '`marker_id` IN (' . implode(',', $ids) . ')',
                array(
                    'marker_id' => 0
                )
            );
        }

        return $result;
    }
}
