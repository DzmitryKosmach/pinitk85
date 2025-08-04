<?php

/**
 * Общий класс для каталога, содержит основные методы, используемые в разных модулях
 *
 * @author    Seka
 */

class Catalog
{
    /**
     * Валюты
     */
    const RUB = 'rub';
    const USD = 'usd';

    /**
     * Точность дробных параметров в каталоге
     */
    const PRICES_DECIMAL = 2;        // К-во десятичных знаков для цен
    const MULTIPLERS_DECIMAL = 5;    // К-во десятичных знаков множителей цен (наценка, скидка)

    /**
     * ID основной страницы каталога
     */
    const CATALOG_PAGE_ID = 500;

    /**
     * В этих переменных сохраняются последние значения, полученные в методе detectByUrl()
     * @see    Catalog::detectByUrl()
     * @var int
     */
    static int $catId = 0;
    static int $seriesId = 0;
    static int $itemId = 0;
    static int $pageId = 0;
    static bool $isDiscountPage = false;

    /** Сюда записываем URL фотографии серии/товара, которая будет передаваться в соц. сети через блок "Поделиться"
     * @var string
     */
    static $sharePhoto = '';

    /**
     * Кусок URL, добавляемый к URL' категории для генерации адреса страницы с сериями на акциях и скидках в данной категории
     */
    const DISCOUNTS_URL = 'rasprodaja';


    /**
     * Определяем категорию, серию, товар по URL'у
     *
     * Если какой-то из возвращаемых параметров равен:
     * 0        - значит он не представлен в URL'е
     * false    - в БД не найдена запись (категория, серия и т.д.), соответствующая переданному URL'у
     * $pageId - это id теговой страницы
     * $isDiscountPage (bool) - страница категории, но отобразить нужно только серии с акциями и скидками
     *
     * @static
     * @param string $url
     * @param bool   $allowRedirect Разрешить редирект и прекращение скрипта до возврата результата, если это необходимо
     * @return    array    ($catId, $seriesId, $itemId, $pageId, $isDiscountPage)
     *
     * Шаблоны URL'ов:
     * /catalog/category/[category/]*series/
     * /catalog/category/[category/]*series/item/
     * Методы, генерирующие URL'ы для страниц каталога:
     * @see    Catalog_Categories::a()
     * @see    Catalog_Series::a()
     * @see    Catalog_Items::a()
     */
    static function detectByUrl($url = '', $allowRedirect = true)
    {

        if ($url === '') {
            $url = Url::$current;
        }

        $oUrl = new Url();
        $oUrl->parse(
            $url,
            Url::$parsed['pageId'],
            Url::$parsed['pagePath'],
            Url::$parsed['getVars'],
            Url::$parsed['leftUrlParts'],
            Url::$parsed['allowRedirect']
        );

        $pageId = $oUrl::$parsed['pageId'];
        $getVars = $oUrl::$parsed['getVars'];
        $leftUrlParts = $oUrl::$parsed['leftUrlParts'];

        if ($pageId != self::CATALOG_PAGE_ID) {
            // URL не соответствует странице каталога
            self::$catId = 0;
            self::$seriesId = 0;
            self::$itemId = 0;
            self::$pageId = 0;
            self::$isDiscountPage = false;
            return array(0, 0, 0, 0, false);
        }

        $oCategories = new Catalog_Categories();


        $oSeries = new Catalog_Series();
        $oItems = new Catalog_Items();
        $oPagesGroups = new Catalog_Pages_Groups();
        $oPages = new Catalog_Pages();

        // Получаем массив частей URL
        $urlPath = array();
        foreach ($getVars as $k => $v) {
            if (preg_match('/^p[0-9]+$/', $k)) {
                $urlPath[] = $v;
            }
        }

        // Если частей URL нет в getVars, берем их из leftUrlParts
        if (!count($urlPath) && count($leftUrlParts)) {
            $urlPath = $leftUrlParts;
        }

        if (!count($urlPath)) {
            self::$catId = 0;
            self::$seriesId = 0;
            self::$itemId = 0;
            self::$pageId = 0;
            self::$isDiscountPage = false;
            return array(0, 0, 0, 0, false);
        }

        // Вычисляем ID категории
        $catId = 0;
        $urlWord = array_shift($urlPath);
        do {
            $cid = $oCategories->getCell(
                'id',
                '`parent_id` = ' . $catId . ' AND `url` = \'' . MySQL::mres($urlWord) . '\''
            );
            if ($cid === false) {
                break;
            }

            // Запоминаем ID найденной страницы, а также принимаем его как parent_id для следующей итерации
            $catId = $cid;

            // Получаем следующий кусок URL'а
            $urlWord = count($urlPath) ? array_shift($urlPath) : false;
        } while ($cid && $urlWord !== false && $urlWord !== '');

        if (!$catId && $urlWord !== false && $urlWord !== '') {
            // Категория не найдена
            self::$catId = false;
            self::$seriesId = 0;
            self::$itemId = 0;
            self::$pageId = 0;
            self::$isDiscountPage = false;
            return array(false, 0, 0, 0, false);
        }

        if ($allowRedirect) {
            // Редирект на новый URL (без слова /catalog/)
            $catPageUrl = Url::a(Catalog::CATALOG_PAGE_ID);
            if (mb_strpos($url, $catPageUrl) === 0) {
                header(
                    'Location: ' . substr_replace($url, '/', 0, strlen($catPageUrl)),
                    true,
                    301
                );
                exit;
            }
        }

        self::$catId = $catId;

        if ($urlWord !== false && $urlWord !== '') {
            // Ищем ID серии
            //$oSeries->table($oSeries::$tab);
            $seriesId = $oSeries->getCell(
                'id',
                '`category_id` = ' . $catId . ' AND `url` = \'' . MySQL::mres($urlWord) . '\''
            );
            if ($seriesId) {
                // Серия найдена
                self::$seriesId = $seriesId;
                self::$pageId = 0;
                self::$isDiscountPage = false;

                $urlWord = count($urlPath) ? array_shift($urlPath) : false;
                if ($urlWord !== false && $urlWord !== '') {
                    // Ищем ID товара
                    /*$itemId = $oItems->getCell(
                        'id',
                        '`series_id` = ' . $seriesId . ' AND `url` = \'' . MySQL::mres($urlWord) . '\''
                    );*/
                    $itemId = $oItems->getCell(
                        'id',
                        //убрал серию потому что в некоторых товарах есть привязка товаров из других серий
                        '`id` = ' . intval($urlWord)
                    );

                    if ($itemId) {
                        self::$itemId = $itemId;
                        return array($catId, $seriesId, $itemId, 0, false);    // Найдена категория, серия и товар
                    } else {
                        self::$itemId = false;
                        return array(
                            $catId,
                            $seriesId,
                            false,
                            0,
                            false
                        );    // Найдена категория и серия, а товар - не найден
                    }
                } else {
                    return array(
                        $catId,
                        $seriesId,
                        0,
                        0,
                        false
                    );    // Найдена категория и серия, товар не представлен в URL
                }
            } else {
                // Серия не найдена, ищем теговую страницу
                self::$itemId = 0;
                if ($urlWord == self::DISCOUNTS_URL) {
                    // Страница категории с сериями на скидках и акциях
                    self::$seriesId = 0;
                    self::$pageId = 0;
                    self::$isDiscountPage = true;
                    return array($catId, 0, 0, 0, true);
                } else {
                    self::$isDiscountPage = false;

                    $pagesGroupsIds = $oPagesGroups->getCol('id', '`category_id` = ' . $catId);
                    $pagesGroupsIds[] = 0;
                    $pageId = $oPages->getCell(
                        'id',
                        '`group_id` IN (' . implode(',', $pagesGroupsIds) . ') AND `url` = \'' . MySQL::mres(
                            $urlWord
                        ) . '\''
                    );
                    if ($pageId) {
                        self::$seriesId = 0;
                        self::$pageId = $pageId;
                        return array($catId, 0, 0, $pageId, false);        // Найдена категория и теговая стр.
                    } else {
                        self::$seriesId = false;
                        self::$pageId = false;
                        return array(
                            $catId,
                            false,
                            0,
                            false,
                            false
                        );        // Найдена категория, а серия или теговая стр. - не найдены
                    }
                }
            }
        } else {
            self::$seriesId = 0;
            self::$itemId = 0;
            self::$pageId = 0;
            self::$isDiscountPage = false;
            return array($catId, 0, 0, 0, false);    // Найдена категория, остальное не представлено в URL
        }
    }


    /**
     * @static
     * @param number $price
     * @param string $decSep
     * @return    string
     */
    static function priceFormat($price, $decSep = '.')
    {
        $price = number_format(abs($price), 2, $decSep, ' ');
        $price = str_replace($decSep . '00', '', $price);
        return $price;
    }


    /**
     * Пояснение к конвертации числа в % и наоброт
     * @see    Catalog::num2percent();
     * @see    Catalog::percent2num();
     */
    const PC_EXACTLY = 1;    // Число и проценты - суть одно (0.58 = 58%)
    const PC_INCREASE = 2;    // Умножение X на число - это увеличение X на % (1.25 = 25%)
    const PC_DECREASE = 3;    // Умножение X на число - это уменьшение X на % (0.72 = 28%)

    /** Конвертация малого дробного числа в количество процентов
     * @static
     * @param number $num
     * @param int    $sense
     * @return    float|int
     */
    static function num2percent($num, $sense = self::PC_EXACTLY)
    {
        $num = str_replace(',', '.', $num);
        $pcnt = round(abs($num) * 100, self::MULTIPLERS_DECIMAL - 2);
        if ($sense == self::PC_INCREASE) {
            $pcnt -= 100;
        } elseif ($sense == self::PC_DECREASE) {
            $pcnt = 100 - $pcnt;
        }
        return $pcnt;
    }

    /** Конвертация количества процентов в малое дробное число
     * @static
     * @param number $pcnt
     * @param int    $sense
     * @return    float|int
     */
    static function percent2num($pcnt, $sense = self::PC_EXACTLY)
    {
        $pcnt = str_replace(',', '.', $pcnt);
        $num = round(abs($pcnt) / 100, self::MULTIPLERS_DECIMAL);
        if ($sense == self::PC_INCREASE) {
            $num += 1;
        } elseif ($sense == self::PC_DECREASE) {
            $num = 1 - $num;
        }
        return $num;
    }


    /** Разделяем переданное название категории на 2 строки, если это возможно
     * @param string $name
     * @param string $separator
     * @return    string
     */
    static function formatTopCatName($name, $separator = '<br>')
    {
        $name = preg_replace('/[[:space:]]+/ius', ' ', trim($name));
        $words = explode(' ', $name);
        if (count($words) > 2) {
            // Разделяем название на 2 части так, чтобы разница в их длине была минимальной
            $nameLen = mb_strlen($name);
            $p1 = array();
            while (mb_strlen(implode(' ', $p1)) < $nameLen / 2) {
                $p1[] = array_shift($words);
            }
            $p1var1 = implode(' ', $p1);
            $p2var1 = implode(' ', $words);

            $p2var2 = implode(' ', array_merge(array(array_pop($p1)), $words));
            $p1var2 = implode(' ', $p1);

            if (abs(mb_strlen($p1var1) - mb_strlen($p2var1)) < abs(mb_strlen($p1var2) - mb_strlen($p2var2))) {
                $p1 = $p1var1;
                $p2 = $p2var1;
            } else {
                $p1 = $p1var2;
                $p2 = $p2var2;
            }
        } elseif (count($words) == 2) {
            $p1 = $words[0];
            $p2 = $words[1];
        } else {
            $p1 = $name;
            $p2 = '';
        }
        return $p1 . ($p2 ? $separator . $p2 : '');
    }


    /**
     * @param array $photoInf
     */
    static function sharePhotoSeries($photoInf = array())
    {
        self::$sharePhoto = Config::pathRel(
            'images'
        ) . Catalog_Series_Photos::$imagePath . $photoInf['id'] . '.' . $photoInf['_img_ext'];
    }


    /**
     * @param array $itemInf
     */
    static function sharePhotoItem(array $itemInf = [])
    {
        self::$sharePhoto = Config::pathRel(
            'images'
        ) . Catalog_Items::$imagePath . $itemInf['id'] . '.' . $itemInf['_img_ext'];
    }


    /**
     * @param string $imagePath
     * @param int    $photoId
     * @param int    $sizeW
     * @param int    $sizeH
     * @param int    $rm
     * @param null|string $ext
     * @param array  $photoNameParts
     * @return    string
     */
    public static function photoUrl(
        string $imagePath,
        int $photoId,
        int $sizeW,
        int $sizeH,
        int $rm,
        ?string $ext,
        ?array $photoNameParts = []
    ): string {
        if (!is_array($photoNameParts)) {
            $photoNameParts = [$photoNameParts];
        }

        foreach ($photoNameParts as $n => $p) {

            if (!$p) {
                unset($photoNameParts[$n]);
                continue;
            }

            $p = trans(mb_strtolower(trim($p)));
            $p = preg_replace('/[^0-9a-z]/', '-', $p);
            if ($p === '') {
                unset($photoNameParts[$n]);
                continue;
            }

            $photoNameParts[$n] = $p;
        }

        $ext = is_null($ext) ? 'jpg' : $ext;

        $name = implode('-', $photoNameParts);
        if ($name !== '') {
            $name .= '-';
        }
        while (strpos($name, '--') !== false) {
            $name = str_replace('--', '-', $name);
        }

        if (!$sizeW && !$sizeH) {
            $resize = '';
        } else {
            $resize = '_' . $sizeW . 'x' . $sizeH . '_' . $rm;
        }

        return
            Config::pathRel('images') . $imagePath .
            $name .
            $photoId . $resize .
            '.' . $ext;
    }
}
