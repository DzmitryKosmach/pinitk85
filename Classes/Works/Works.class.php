<?php

/**
 * Наши работы
 *
 * @author    Seka
 */

class Works extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'works';

    /**
     * @var string
     */
    static $imagePath = '/Works/';

    /**
     * ID основной страницы
     */
    const WORKS_PAGE_ID = 2033;


    /** URL наши работы по её ID
     * @static
     * @param int $aId
     * @return    string
     */
    static function a(int $aId)
    {
        $urls = array();

        $o = new self();

        $aId = intval($aId);

        if (!isset($urls[$aId])) {
            $urls[$aId] = Url::a(self::WORKS_PAGE_ID);

            $u = $o->getCell('url', '`id` = ' . $aId);
            if ($u) {
                $urls[$aId] .= $u . '/';
            }
        }

        return $urls[$aId];
    }


    /** Определяем ID наши работы по URL'у
     * @param string $url
     * @return    bool|int    $newsId
     * 0 - не страница наши работы (вероятно, страница со списком наши работы), false - новость не наши работы
     */
    function detectByUrl(string $url = '')
    {
        if ($url === '') {
            $url = Url::$current;
        }

        $oUrl = new Url();

        $oUrl->parse($url, $oUrl::$parsed['pageId'], $oUrl::$parsed['pagePath'], $oUrl::$parsed['getVars']);

        $pageId = $oUrl::$parsed['pageId'];
        $pagePath = $oUrl::$parsed['pagePath'];
        $getVars = $oUrl::$parsed['getVars'];

        if ($pageId != self::WORKS_PAGE_ID) {
            // URL не соответствует странице наши работы
            return 0;
        }

        // Получаем массив частей URL
        $urlPath = array();
        foreach ($getVars as $k => $v) {
            if (preg_match('/^p[0-9]+$/', $k)) {
                $urlPath[] = $v;
            }
        }

        // Получаем часть URL, определяющую наши работы
        if (count($urlPath)) {
            $nUrl = $urlPath[0];
            $aId = $this->getCell(
                'id',
                '`url` = \'' . MySQL::mres($nUrl) . '\''
            );
            return $aId;
        } else {
            // Страница со списком наши работы
            return 0;
        }
    }


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
