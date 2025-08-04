<?php

/** Ошибка 404
 * @author    Seka
 */

class e404
{
    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(array$pageInf = []): string
    {
        header('HTTP/1.0 404', true, 404);
        //print_array($_SERVER);

        // Вывод страницы
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf
        ));
    }
}
