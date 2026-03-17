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

        // Блоки «Преимущества» (как на главной, из админки, до 8 штук)
        $benefits = array();
        $benefitImages = array(
            '/images/index/us1.svg',
            '/images/index/us2.svg',
            '/images/index/us3.svg',
            '/images/index/us7.svg',
            '/images/index/us7 (2).svg',
            '/images/index/us8.svg',
            '/images/index/us6.svg',
            '/images/index/us9 (2).svg',
        );
        for ($i = 1; $i <= 8; $i++) {
            $text = trim(Options::name('head-benefit' . $i));
            if ($text === '') {
                continue;
            }
            $benefits[] = array(
                'text'  => $text,
                'url'   => trim(Options::name('head-benefit' . $i . '-url')),
                'image' => isset($benefitImages[$i - 1]) ? $benefitImages[$i - 1] : $benefitImages[0],
            );
        }

        $tpl = Pages::tplFile($pageInf);

        return pattExeP(fgc($tpl), array(
            'pageInf'     => $pageInf,
            'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id'])),
            'benefits'    => $benefits,
        ));
    }
}
