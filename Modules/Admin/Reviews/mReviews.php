<?php

/** Админка: Отзывы
 * @author    Seka
 */

class mReviews extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::REVIEWS;

    /**
     * @var string
     */
    var $mainClass = 'Reviews';

    /**
     * @var int
     */
    var $rights = Administrators::R_REVIEWS;

    /**
     *
     */
    const ON_PAGE = 50;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(array &$pageInf = array())
    {
        $o = new self();
        $o->checkRights();
        $o->getOperations();

        $_GET['object'] = isset($_GET['object']) ? $_GET['object'] : "";
        $_GET['object_id'] = isset($_GET['object_id']) ? (int)$_GET['object_id'] : 0;

        // Условия поиска
        $search = array();
        if ($o = trim($_GET['object'])) {
            $search[] = '`object` = \'' . MySQL::mres($o) . '\'';
        }
        if ($oId = intval($_GET['object_id'])) {
            $search[] = '`object_id` = ' . $oId;
        }

        // Получаем список
        $oReviews = new Reviews();
        list($reviews, $toggle) = $oReviews->getByPage(
            intval($_GET['page']),
            self::ON_PAGE,
            '*',
            implode(' AND ', $search),
            '`date` DESC, `id` DESC'
        );
        $reviews = $oReviews->imageExtToData($reviews);

        // Связанные серии
        $sIds = array();
        foreach ($reviews as $r) {
            if ($r['object'] === Reviews::OBJ_SERIES && $sId = intval($r['object_id'])) {
                $sIds[] = $sId;
            }
        }
        if (trim($_GET['object']) === Reviews::OBJ_SERIES && $sId = intval($_GET['object_id'])) {
            $sIds[] = $sId;
        }
        if (count($sIds)) {
            $oSeries = new Catalog_Series();
            $series = $oSeries->getWhtKeys(
                'id, name, category_id, url',
                '`id` IN (' . implode(',', $sIds) . ')'
            );
        } else {
            $series = array();
        }

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'reviews' => $reviews,
            'toggle' => $toggle,
            'series' => $series
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId)
    {
        $oReviews = new Reviews();
        $oReviews->del(intval($iId));

        Pages::flash('Отзыв успешно удалён.');
    }
}
