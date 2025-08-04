<?php

/**
 * Товары, входящие в коплекты серий
 * @see    Images::resize()
 *
 * @author    Seka
 */

class Catalog_Series_SetItems extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series_set_items';

    const SET_IMAGE_DEFAULT_W = 442;
    const SET_IMAGE_DEFAULT_H = 350;
    /*const SET_IMAGE_DEFAULT_W = 471;
    const SET_IMAGE_DEFAULT_H = 341;*/
}
