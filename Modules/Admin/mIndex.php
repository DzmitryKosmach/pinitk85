<?php

/** Первая страница админки
 * @author    Seka
 */

class mIndex
{
    /**
     * @static
     * @param array $pageInf
     * @return void
     */
    static function main(array $pageInf = []): void
    {
        header('Location: ' . Url::a('admin-content'));
        return;
    }
}
