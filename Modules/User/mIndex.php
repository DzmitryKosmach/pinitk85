<?php
/** Главная станица
 * @author    Seka
 */

class mIndex
{

    /**
     *
     */
    const SERIES_IN_CAT = 10;

    /**
     *
     */
    const PROJECTS_CNT = 3;

    /**
     *
     */
    const LETTERS_CNT = 3;

    /**
     *
     */
    const REVIEWS_CNT = 3;

    /**
     *
     */
    const NEWS_CNT = 3;

    /**
     *
     */
    const ARTICLES_CNT = 2;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */

    static function main(&$pageInf = array())
    {
        // Картинки для слайдера
        $oSlider = new Slider_Index();
        $slides = $oSlider->imageExtToData(
            $oSlider->get(
                '*',
                '`active` = 1',
                'order'
            )
        );

        // Категории и серии
        $oCategories = new Catalog_Categories();
        $categories = $oCategories->imageExtToData(
            $oCategories->getTree('id, name, in_index, use_noindex, use_nofollow', 2)
        );

        // удаляем категории, которые не выводятся на главной
        for ($i = count($categories) - 1; $i >= 0; $i--) {
            if ($categories[$i]['in_index'] == 0) {
                unset($categories[$i]);
            }
        };
        // удаляем категории, которые не выводятся на главной

        /*$categories = $oCategories->get(
            'id, name, use_noindex, use_nofollow',
            '`in_index` = 1',
            'order'
        );
        $oSeries = new Catalog_Series();
        $categories = $oSeries->getForCats($categories, self::SERIES_IN_CAT);*/

        // Серии на акции
        $oSeries = new Catalog_Series();
        $seriesPromo = $oSeries->get(
            '*',
            '(`marker_id` != 0 OR `price_min_old` > 0) AND `out_of_production` = 0',
            'RAND()',
            self::SERIES_IN_CAT
        );

        $seriesPromo = $oSeries->details($seriesPromo, true);
//dd($seriesPromo);
        // Наши проекты
        /*$oProjects = new Clients_Projects();
        $projects = $oProjects->get(
            '*',
            '`in_index` = 1',
            '`order` DESC',
            self::PROJECTS_CNT
        );
        $oPics = new Clients_Projects_Pics();
        $projects = $oPics->get1stPhotos($projects);*/

        // Письма клиентов
        $oLetters = new Clients_Letters();
        $letters = $oLetters->imageExtToData($oLetters->get(
            '*',
            '`in_index` = 1',
            '`order` DESC',
            self::LETTERS_CNT
        ));

        // Отзывы для блока "Отзывы покупателей" на главной
        $oReviews = new Reviews();
        $reviews = $oReviews->imageExtToData(
            $oReviews->get(
                '*',
                '`object` = \'' . Reviews::OBJ_SITE . '\' AND `approved` = 1',
                '`date` DESC, `id` DESC',
                self::REVIEWS_CNT
            )
        );

        // Новости
        $oNews = new News();
        $news = $oNews->imageExtToData(
            $oNews->get(
                '*',
                '',
                '`date` DESC',
                self::NEWS_CNT
            )
        );

        // Статьи
        /*$oArticles = new Articles();
        $articles = $oArticles->imageExtToData($oArticles->get(
            '*',
            '',
            '`order` DESC',
            self::ARTICLES_CNT
        ));*/


        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);

        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'slides' => $slides,
            'categories'  => $categories,
            'seriesPromo' => $seriesPromo,
            //'projects'   => $projects,
            'letters'    => $letters,
            'reviews'     => $reviews,
            'news'        => $news,
            //'articles'   => $articles

        ));
    }
}
