<?php

/** Статьи
 * @author    Seka
 */

class mArticles
{

    /**
     * К-во на странице
     */
    const ON_PAGE = 20;

    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(array $pageInf = [])
    {
        $oArticles = new Articles();
        $artId = $oArticles->detectByUrl();

        // Статья не найдена
        if ($artId === false) {
            // Статья не найдена
            header('HTTP/1.0 404', true, 404);
            $tpl = Pages::tplFile($pageInf, 'notfound');
            return pattExeP(fgc($tpl), array(
                'pageInf' => $pageInf
            ));
        }

        if (!$artId) {
            // Список статей
            list($articles, $toggle, $pgNum) = $oArticles->getByPage(
                intval($_GET['page']),
                self::ON_PAGE,
                '*',
                '`date` < NOW( ) ',
                '`order` DESC'
            );
            $articles = $oArticles->imageExtToData($articles);

            //
            if ($pgNum > 1) {
                $pageInf['dscr'] = '';
                $pageInf['kwrd'] = '';
            }

            // Выводим страницу
            $tpl = Pages::tplFile($pageInf, 'list');
            return pattExeP(fgc($tpl), array(
                'pageInf' => $pageInf,
                'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id'])),
                'articles' => $articles,
                'toggle' => $toggle
            ));
        } else {
            // Одна статья
            $artInf = $oArticles->imageExtToData(
                $oArticles->getRow(
                    '*',
                    '`id` = ' . intval($artId)
                )
            );

            // Параметры страницы
            $pageInf['header'] = $artInf['a_title'];

            if ($artInf['title']) {
                $pageInf['title'] = $artInf['title'];
            }
            if ($artInf['dscr']) {
                $pageInf['dscr'] = $artInf['dscr'];
            }
            if ($artInf['kwrd']) {
                $pageInf['kwrd'] = $artInf['kwrd'];
            }

            // Выводим страницу
            $tpl = Pages::tplFile($pageInf, 'view');
            return pattExeP(fgc($tpl), array(
                'pageInf' => $pageInf,
                'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id'])),
                'artInf' => $artInf
            ));
        }
    }
}
