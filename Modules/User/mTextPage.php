<?php

/**
 * Нефункциональная текстовая страница
 * @author    Seka
 */

class mTextPage
{

    static $output = OUTPUT_DEFAULT;

    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        // Выводим шаблон
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            self::$output = OUTPUT_FRAME;
            return $pageInf['text'];
        }

        $tpl = Pages::tplFile($pageInf);

        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id']))
        ));
    }
}
