<?php

/**
 * Выгрузка товаров в Яндекс.Маркет
 *
 * @author    Seka
 */

class Catalog_Yml
{

    /**
     * @var string
     */
    static $fileAll = 'yandex.xml';

    /**
     * @var string
     */
    static $tpl = '/Skins/html/User/Catalog/yandex.xml';

    /**
     *
     */
    const OPT_TEXT = 'text';
    const OPT_NUM = 'NUM';
    const OPT_YN = 'yesno';

    /**
     * @var array
     */
    static array $options = [
        'yml_shop_name' => self::OPT_TEXT,
        'yml_shop_company' => self::OPT_TEXT,
        'yml_shop_url' => self::OPT_TEXT,
        //'yml_shop_delivery_cost'	=> self::OPT_NUM,
        //'yml_shop_prod_available'	=> self::OPT_YN
        'yml-prepaid-min-price' => self::OPT_NUM,
    ];

    /**
     *
     */
    static function make()
    {
        $oYmlFiles = new Catalog_Yml_Files();
        $ymlFiles = $oYmlFiles->getHash('id, file');
        $ymlFiles[0] = self::$fileAll;

        $oCategories = new Catalog_Categories();
        $oSeries = new Catalog_Series();
        $oItems = new Catalog_Items();
        $oItemGroups = new Catalog_Items_Groups();

        $templates = $oCategories->get('id, pattern_series_dscr, pattern_items_dscr');
        $description_templates = array();

        foreach ($templates as $template) {
            $description_templates[$template['id']] = $template;
        }

        $groups = $oItemGroups->get('id, category_id');
        $item_groups = array();

        foreach ($groups as $group) {
            $item_groups[$group['id']] = $group;
        }

        foreach ($ymlFiles as $fileId => $fileName) {
            // Получаем список категорий каталога
            $categories = $oCategories->getFloatTree(
                'id, name, parent_id',
                3,
                0,
                $fileId ? '`yml_file_id` = ' . $fileId : ''
            );

            $cIds = array();
            foreach ($categories as $c) {
                if (!$c['has_subcats']) {
                    $cIds[] = $c['id'];
                }
            }

            if (count($cIds)) {
                // Получаем список серий
                $series = $oSeries->getWhtKeys(
                    'id, name, text, dscr, url, category_id, in_stock',
                    '`category_id` IN (' . implode(',', $cIds) . ')'
                );
                $sIds = array();
                foreach ($series as $s) {
                    $sIds[] = $s['id'];
                }
            } else {
                $series = array();
                $sIds = array();
            }

            $series = $oSeries->details($series);

            if (count($sIds)) {
                // Получаем список товаров
                if ($fileId) {
                    $items = $oItems->imageExtToData(
                        $oItems->get(
                            'id, series_id, name, art, text, dscr, url, price_min',
                            '`series_id` IN (' . implode(',', $sIds) . ') AND `in_ym` = 1'
                        )
                    );
                } else {
                    $items = $oItems->imageExtToData(
                        $oItems->get(
                            'id, series_id, name, art, text, dscr, url, price_min',
                            '`series_id` IN (' . implode(',', $sIds) . ')'
                        )
                    );
                }
            } else {
                $items = array();
            }

            // Раскладываем товары по сериям
            foreach ($items as $item) {
                $series[$item['series_id']]['items'][] = $item;
            }

            // В каждой серии оставляем только 1 товар
            foreach ($series as $sn => $s) {
                if (isset($s['items']) && count($s['items']) == 0) {
                    unset($series[$sn]);
                } elseif (isset($s['items']) && count($s['items']) > 1) {
                    $s['items'] = $s['items'][0];
                }
            }

            // Генерируем заголовки серий
            foreach ($series as $i => $s) {
                $seriesMeta = $oSeries->generateHeadersAndMeta($s['id']);
                $series[$i]['h1'] = $seriesMeta['h1'];
            }


            $yml = pattExeP(fgc(_ROOT . self::$tpl), array(
                'categories' => $categories,
                'series' => $series,
                'description_templates' => $description_templates
                //'items'			=> $items
            ));

            file_put_contents(
                _ROOT . '/' . $fileName,
                $yml
            );
        }
    }
}
