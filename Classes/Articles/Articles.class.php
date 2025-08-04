<?php

/**
 * Статьи
 *
 * @author    Seka
 */

class Articles extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'articles';

    /**
     * @var string
     */
    static $imagePath = '/Articles/';

    /**
     * ID основной страницы
     */
    const ARTICLES_PAGE_ID = 930;


    /** URL статьи по её ID
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
            $urls[$aId] = Url::a(self::ARTICLES_PAGE_ID);

            $u = $o->getCell('url', '`id` = ' . $aId);
            if ($u) {
                $urls[$aId] .= $u . '/';
            }
        }

        return $urls[$aId];
    }


    /** Определяем ID статьи по URL'у
     * @param string $url
     * @return    bool|int    $newsId
     * 0 - не страница статьи (вероятно, страница со списком статей), false - новость не статья
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

        if ($pageId != self::ARTICLES_PAGE_ID) {
            // URL не соответствует странице статей
            return 0;
        }

        // Получаем массив частей URL
        $urlPath = array();
        foreach ($getVars as $k => $v) {
            if (preg_match('/^p[0-9]+$/', $k)) {
                $urlPath[] = $v;
            }
        }

        // Получаем часть URL, определяющую статью
        if (count($urlPath)) {
            $nUrl = $urlPath[0];
            $aId = $this->getCell(
                'id',
                '`url` = \'' . MySQL::mres($nUrl) . '\''
            );
            return $aId;
        } else {
            // Страница со списком статей
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
