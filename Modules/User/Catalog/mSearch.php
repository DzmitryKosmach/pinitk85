<?php

/**
 * @author    Seka
 */

class mSearch
{

    /**
     * @var int
     */
    static $output = OUTPUT_DEFAULT;

    /**
     *
     */
    const SERIES_ON_PAGE_DEFAULT = 20;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        if (isset($_GET['getpopular']) && trim($_GET['getpopular']) !== '') {
            return self::getPopular(trim($_GET['getpopular']));
        }
        if (isset($_GET['quick']) && trim($_GET['quick']) !== '') {
            return self::getQuick(trim($_GET['quick']), $pageInf);
        }

        if (isset($_GET['text']) && trim($_GET['text']) !== '') {
            $empty = false;

            $cond = Catalog_Search::textCond(trim($_GET['text']));
            if ($cond !== false) {
                $oSeries = new Catalog_Series();
                if (intval($_GET['onpage']) === -1) {
                    $series = $oSeries->get(
                        '*, (' . $cond . ') AS `rel`',
                        '(' . $cond . ') AND `out_of_production` = 0',
                        '`rel` DESC'
                    );
                    $toggle = '';
                    $pgNum = 1;
                    $seriesCnt = count($series);
                } else {
                    list($series, $toggle, $pgNum, $seriesCnt) = $oSeries->getByPage(
                        intval($_GET['page']),
                        self::SERIES_ON_PAGE_DEFAULT,
                        '*, (' . $cond . ') AS `rel`',
                        '(' . $cond . ') AND `out_of_production` = 0',
                        '`rel` DESC'
                    );
                }
                $series = $oSeries->details($series);

                //
                if ($pgNum > 1) {
                    $pageInf['dscr'] = '';
                    $pageInf['kwrd'] = '';
                }

                if (count($series)) {
                    $oSearchHistory = new Catalog_Search_History();
                    $oSearchHistory->log($_GET['text']);
                }
            } else {
                $empty = true;
                $series = array();
                $toggle = '';
                $seriesCnt = 0;
            }
        } else {
            $empty = true;
            $series = array();
            $toggle = '';
            $seriesCnt = 0;
        }

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);

        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id'])),
            'series' => $series,
            'toggle' => $toggle,
            'seriesCnt' => $seriesCnt,
            'empty' => $empty
        ));
    }


    /**
     * @static
     * @param string $text
     * @return    array
     */
    static function getPopular($text)
    {
        self::$output = OUTPUT_JSON;
        $oSearchHistory = new Catalog_Search_History();
        return $oSearchHistory->getPopular($text);
    }


    /**
     * @static
     * @param string $text
     * @param array  $pageInf
     * @return    string
     */
    static function getQuick($text, $pageInf)
    {
        self::$output = OUTPUT_FRAME;

        $cond = Catalog_Search::textCond($text);

        if ($cond !== false) {
            $oSeries = new Catalog_Series();
            $series = $oSeries->get(
                '*, (' . $cond . ') AS `rel`',
                '(' . $cond . ') AND `out_of_production` = 0',
                '`rel` DESC',
                10
            );
            if (!count($series)) {
                return '0';
            }
            $series = $oSeries->details($series);

            // Выводим шаблон
            $tpl = Pages::tplFile($pageInf, 'quick');

            return pattExeP(fgc($tpl), array(
                'series' => $series,
                'text' => $text
            ));
        } else {
            return '0';
        }
    }
}
