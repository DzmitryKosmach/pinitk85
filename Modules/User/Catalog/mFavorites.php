<?php

/** Избранное
 * @author	Seka
 */

class mFavorites
{

    /**
     * @var int
     */
    static $output = OUTPUT_DEFAULT;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {

        if ($seriesId = intval($_GET['add'])) {
            return self::add($seriesId);
        }

        if ($seriesId = intval($_GET['remove'])) {
            return self::remove($seriesId);
        }

        list($series, $matsBySeries, $options, $materials) = Catalog_Favorites::get();

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'pageInf'    => $pageInf,
            'breadcrumbs'    => BreadCrumbs::forPage(intval($pageInf['id'])),
            'series'    => $series,
            'options'    => $options,
            'matsBySeries'    => $matsBySeries,
            'materials'        => $materials
        ));
    }


    /**
     * @static
     * @param	int	$seriesId
     * @return	int
     */
    static function add($seriesId)
    {
        $seriesId = intval($seriesId);
        Catalog_Favorites::add($seriesId);

        if (intval($_GET['ajax'])) {
            self::$output = OUTPUT_FRAME;
            return Catalog_Favorites::count();
        } else {
            Pages::flash('Серия добавлена в избранное');
            exit;
        }
    }


    /**
     * @static
     * @param	int	$seriesId
     * @return	int
     */
    static function remove($seriesId)
    {
        $seriesId = intval($seriesId);
        Catalog_Favorites::remove($seriesId);

        if (intval($_GET['ajax'])) {
            self::$output = OUTPUT_FRAME;
            return Catalog_Favorites::count();
        } else {
            Pages::flash('Серия удалена из избранного');
            exit;
        }
    }
}
