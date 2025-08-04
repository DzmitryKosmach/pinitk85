<?php

/**
 * Новости
 *
 * @author    Seka
 */

class News extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'news';

    /**
     * @var string
     */
    static $imagePath = '/News/';

    /**
     * ID основной страницы
     */
    const NEWS_PAGE_ID = 920;

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /** URL новости по её ID
     * @static
     * @param int $nId
     * @return    string
     */
    static function a($nId)
    {
        static $urls = array();
        static $o;
        if (!$o) {
            $o = new self();
        }

        $nId = intval($nId);

        if (!isset($urls[$nId])) {
            $urls[$nId] = Url::a(self::NEWS_PAGE_ID);

            $u = $o->getCell('url', '`id` = ' . $nId);
            if ($u) {
                $urls[$nId] .= $u . '/';
            }
        }

        return $urls[$nId];
    }


    /** Определяем ID новости по URL'у
     * @param string $url
     * @return    bool|int    $newsId
     * 0 - не страница новости (вероятно, страница со списком новостей), false - новость не найдена
     */
    function detectByUrl($url = '')
    {
        if ($url === '') {
            $url = Url::$current;
        }

        $oUrl = new Url();

        $oUrl->parse($url, $oUrl::$parsed['pageId'], $oUrl::$parsed['pagePath'], $oUrl::$parsed['getVars']);

        $pageId = $oUrl::$parsed['pageId'];
        $pagePath = $oUrl::$parsed['pagePath'];
        $getVars = $oUrl::$parsed['getVars'];

        if ($pageId != self::NEWS_PAGE_ID) {
            // URL не соответствует странице новостей
            return 0;
        }

        // Получаем массив частей URL
        $urlPath = array();
        foreach ($getVars as $k => $v) {
            if (preg_match('/^p[0-9]+$/', $k)) {
                $urlPath[] = $v;
            }
        }

        // Получаем часть URL, определяющую новость
        if (count($urlPath)) {
            $nUrl = $urlPath[0];
            $nId = $this->getCell(
                'id',
                '`url` = \'' . MySQL::mres($nUrl) . '\''
            );
            return $nId;
        } else {
            // Страница со списком новостей
            return 0;
        }
    }


    /** Формируем дату в формате "03 дек 2013" (для текущего года "03 дек")
     * @static
     * @param int  $ts
     * @param bool $br Вместо последнего пробела - перенос строки (<br>)
     * @return    string
     */
    static function date($ts, $br = true)
    {
        static $mNames = array(
            1 => 'янв',
            2 => 'фев',
            3 => 'мар',
            4 => 'апр',
            5 => 'май',
            6 => 'июн',
            7 => 'июл',
            8 => 'авг',
            9 => 'сен',
            10 => 'окт',
            11 => 'ноя',
            12 => 'дек'
        );
        static $nowY = 0;
        if (!$nowY) {
            $nowY = intval(date('Y'));
        }

        $d = date('d', $ts);
        $m = intval(date('n', $ts));
        $y = intval(date('Y', $ts));

        if ($y == $nowY) {
            $date = $d . ($br ? '<br>' : ' ') . $mNames[$m];
        } else {
            $date = $d . ' ' . $mNames[$m] . ($br ? '<br>' : ' ') . $y;
        }

        return $date;
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
