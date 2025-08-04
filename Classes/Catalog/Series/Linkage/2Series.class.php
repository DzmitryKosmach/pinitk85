<?php

/**
 * Связь серия-серия в пределах группы линковки
 * series1_id - серия, на странице которой отображаются связанные с ней
 * series2_id - серии, связанные с series1_id
 *
 * @author    Seka
 */

class Catalog_Series_Linkage_2Series extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_series_linkage2series';
}
