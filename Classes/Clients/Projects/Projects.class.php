<?php

/**
 * Готовые проекты клиентов
 *
 * @author    Seka
 */

class Clients_Projects extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'clients_projects';

    /**
     * ID основной страницы
     */
    const PROJECTS_PAGE_ID = 940;


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

        if (count($ids)) {
            // Удаляем зависимые данные
            $oPics = new Clients_Projects_Pics();
            $oPics->delCond('`project_id` IN (' . implode(',', $ids) . ')');
        }

        return $result;
    }


    /** URL страницы проекта по его ID
     * @static
     * @param int $pId
     * @return    string
     */
    static function a($pId)
    {
        static $urls = array();
        static $o;
        if (!$o) {
            $o = new self();
        }

        $pId = intval($pId);

        if (!isset($urls[$pId])) {
            $urls[$pId] = Url::a(self::PROJECTS_PAGE_ID);

            $u = $o->getCell('url', '`id` = ' . $pId);
            if ($u) {
                $urls[$pId] .= $u . '/';
            }
        }

        return $urls[$pId];
    }


    /** Определяем ID проекта по URL'у
     * @param string $url
     * @return    bool|int    $newsId
     * 0 - не страница проекта (вероятно, страница со списком проектов), false - проект не найден
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

        if ($pageId != self::PROJECTS_PAGE_ID) {
            // URL не соответствует странице проектов
            return 0;
        }

        // Получаем массив частей URL
        $urlPath = array();
        foreach ($getVars as $k => $v) {
            if (preg_match('/^p[0-9]+$/', $k)) {
                $urlPath[] = $v;
            }
        }

        // Получаем часть URL, определяющую проект
        if (count($urlPath)) {
            $nUrl = $urlPath[0];
            $pId = $this->getCell(
                'id',
                '`url` = \'' . MySQL::mres($nUrl) . '\''
            );
            return $pId;
        } else {
            // Страница со списком проектов
            return 0;
        }
    }
}
