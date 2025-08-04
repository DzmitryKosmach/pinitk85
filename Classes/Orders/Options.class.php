<?php

/**
 * Опции калькулятора корзины
 *
 * @author    Seka
 */

class Orders_Options extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'orders_options';

    /**
     * Основные параметры заказа
     */
    const DELIVERY = 'delivery';
    const UNLOADING = 'unloading';
    const ASSEMBLY = 'assembly';
    const GARBAGE = 'garbage';

    /**
     * Доп. параметры заказа
     */
    const DELIVERY_DISTANCE = 'delivery-distance';
    const UNLOADING_FLOOR = 'unloading-floor';

    /**
     * Ограничения
     */
    const DELIVERY_DISTANCE_MIN = 0;
    const DELIVERY_DISTANCE_MAX = 150;
    const UNLOADING_FLOOR_MIN = 1;
    const UNLOADING_FLOOR_MAX = 50;

    /**
     * Варианты доставки
     */
    const DELIVERY_NO = 0;        // Самовывоз
    const DELIVERY_MSK = 1;        // По Москве
    const DELIVERY_OUTMSK = 2;    // За МКАД
    const DELIVERY_TO_SHIPPING_COMPANY = 3;    // До транспортной компании

    /**
     * Варианты разгрузки
     */
    const UNLOADING_NO = 0;            // Не нужна
    const UNLOADING_ELEVATOR = 1;    // Нужна, есть лифт
    const UNLOADING_STAIRS = 2;        // Нужна, нет лифта

    /**
     * Варианты сборки
     */
    const ASSEMBLY_NO = 0;    // Не нужна
    const ASSEMBLY_YES = 1;    // Нужна

    /**
     * Варианты для вывоза мусора
     */
    const GARBAGE_NO = 0;    // Не нужен
    const GARBAGE_YES = 1;    // Нужен

    /**
     * Значения по-умолчанию для формы с опциями заказа
     * @var array
     */
    static $default = array(
        self::DELIVERY => self::DELIVERY_MSK,
        self::UNLOADING => self::UNLOADING_NO,
        self::ASSEMBLY => self::ASSEMBLY_NO,
        self::GARBAGE => self::GARBAGE_NO,
        self::DELIVERY_DISTANCE => 0,
        self::UNLOADING_FLOOR => 0
    );

    static $metallUnloadingWeights = array(
        array(
            'from' => 0,
            'to' => 50,
            'options' => array(
                'metal-unloading-0-50',
                'metal-unloading-0-50-each-floor'
            )
        ),
        array(
            'from' => 51,
            'to' => 100,
            'options' => array(
                'metal-unloading-51-100',
                'metal-unloading-51-100-each-floor'
            )
        ),
        array(
            'from' => 101,
            'to' => 150,
            'options' => array(
                'metal-unloading-101-150',
                'metal-unloading-101-150-each-floor'
            )
        ),
        array(
            'from' => 151,
            'to' => 200,
            'options' => array(
                'metal-unloading-151-200',
                'metal-unloading-151-200-each-floor'
            )
        ),
        array(
            'from' => 201,
            'to' => 250,
            'options' => array(
                'metal-unloading-201-250',
                'metal-unloading-201-250-each-floor'
            )
        ),
        array(
            'from' => 251,
            'to' => 300,
            'options' => array(
                'metal-unloading-251-300',
                'metal-unloading-251-300-each-floor'
            )
        ),
        array(
            'from' => 301,
            'to' => 350,
            'options' => array(
                'metal-unloading-301-350',
                'metal-unloading-301-350-each-floor'
            )
        ),
        array(
            'from' => 351,
            'to' => 400,
            'options' => array(
                'metal-unloading-351-400',
                'metal-unloading-351-400-each-floor'
            )
        ),
        array(
            'from' => 401,
            'to' => 450,
            'options' => array(
                'metal-unloading-401-450',
                'metal-unloading-401-450-each-floor'
            )
        ),
        array(
            'from' => 451,
            'to' => 500,
            'options' => array(
                'metal-unloading-451-500',
                'metal-unloading-451-500-each-floor'
            )
        ),
        array(
            'from' => 501,
            'to' => 1000000000,
            'options' => array(
                'metal-unloading-501-infin',
                'metal-unloading-501-infin-each-floor'
            )
        )
    );


    /** Мин. сумма заказа, при которой достпен самовывоз
     * @static
     * @return float
     */
    static function pickupMinSum()
    {
        static $cache = false;
        if ($cache !== false) {
            return $cache;
        }

        static $o;
        if (!$o) {
            $o = new self();
        }
        $cache = round(
            $o->getCell('value', '`name` = \'' . MySQL::mres('pickup-min-sum') . '\''),
            Catalog::PRICES_DECIMAL
        );
        return $cache;
    }


    /**
     * @param array $cart Содержимое корзины (возвращается методом Catalog_Cart::get()) или заказа (такая же структура)
     * @param array $orderOptions Опции заказа, выбранные покупателем; Ожидается массив, по структуре аналогичный Orders_Options::$default
     * @param array $calcOnly
     * @return    array
     * Формат ответа:
     * array(
     *        self::DELIVERY    => array(
     *            'price'    => общая стоимость доставки,
     *            'info'    => описание опций доставки
     *        ),
     *        self::UNLOADING    => array(
     *            'price'    => общая стоимость разгрузки,
     *            'info'    => описание опций разгрузки
     *        ),
     *        self::ASSEMBLY    => array(
     *            'price'    => общая стоимость сборки,
     *            'info'    => описание опций сборки,
     *            'comment'    => если цена сборки 0, то здесь поясняется, почему
     *        ),
     *        self::GARBAGE    => array(
     *            'price'    => общая стоимость вывоза мусора,
     *            'info'    => описание опций вывоза мусора
     *        )
     * );
     * @see    Orders_Options::$default
     * @see    Catalog_Cart::get()
     */
    function calc(
        $cart = array(),
        &$orderOptions = array(),
        $calcOnly = array(self::DELIVERY, self::UNLOADING, self::ASSEMBLY, self::GARBAGE)
    ) {
        //print_array($cart); exit;
        $result = array();
        foreach ($calcOnly as $p) {
            $result[$p] = array(
                'price' => 0,
                'info' => ''
            );
        }

        if (!is_array($cart) || !count($cart)) {
            return $result;
        }

        // Опции калькулятора
        //static $calcOptions;
        //if (!$calcOptions) {
            $calcOptions = $this->getHash('name, value');
            foreach ($calcOptions as &$v) {
                $v = round($v, Catalog::PRICES_DECIMAL);
            }
            unset($v);
        //}

        // Проверяем корректность опций заказа
        $orderOptions = self::checkOptions($orderOptions);

        // Доставка
        if (in_array(self::DELIVERY, $calcOnly)) {
            $result[self::DELIVERY] = self::calcDelivery($cart, $orderOptions, $calcOptions);
        }

        // Разгрузка
        if (in_array(self::UNLOADING, $calcOnly)) {
            $result[self::UNLOADING] = self::calcUnloading($cart, $orderOptions, $calcOptions);
        }

        // Сборка
        if (in_array(self::ASSEMBLY, $calcOnly)) {
            $result[self::ASSEMBLY] = self::calcAssembly($cart, $orderOptions, $calcOptions);
        }

        // Вывоз мусора
        if (in_array(self::GARBAGE, $calcOnly)) {
            $result[self::GARBAGE] = self::calcGarbage($cart, $orderOptions, $calcOptions);
        }

        return $result;
    }


    /** Проверяем корректность опций корзины
     * Некорректные значения исправляются с возвращается исправленный массив
     * @static
     * @param array $options Опции заказа, выбранные покупателем; Ожидается массив, по структуре аналогичный Orders_Options::$default
     * @return    array
     * @see    Orders_Options::$default
     */
    protected static function checkOptions($options)
    {
        if (!in_array(
            $options[self::DELIVERY],
            array(self::DELIVERY_NO, self::DELIVERY_MSK, self::DELIVERY_OUTMSK, self::DELIVERY_TO_SHIPPING_COMPANY)
        )) {
            // Доставка
            $options[self::DELIVERY] = self::DELIVERY_NO;
        }
        if (!in_array(
            $options[self::UNLOADING],
            array(self::UNLOADING_NO, self::UNLOADING_ELEVATOR, self::UNLOADING_STAIRS)
        )) {
            // Разгрузка
            $options[self::UNLOADING] = self::UNLOADING_NO;
        }
        if (!in_array($options[self::ASSEMBLY], array(self::ASSEMBLY_NO, self::ASSEMBLY_YES))) {
            // Сборка
            $options[self::ASSEMBLY] = self::ASSEMBLY_NO;
        }
        if (!in_array($options[self::GARBAGE], array(self::GARBAGE_NO, self::GARBAGE_YES))) {
            // Вывоз мусора
            $options[self::GARBAGE] = self::GARBAGE_NO;
        }

        if ($options[self::DELIVERY] == self::DELIVERY_OUTMSK) {
            // Расстояние доставки за МКАД
            $options[self::DELIVERY_DISTANCE] = abs($options[self::DELIVERY_DISTANCE]);
            if ($options[self::DELIVERY_DISTANCE] < self::DELIVERY_DISTANCE_MIN) {
                $options[self::DELIVERY_DISTANCE] = self::DELIVERY_DISTANCE_MIN;
            }
            if ($options[self::DELIVERY_DISTANCE] > self::DELIVERY_DISTANCE_MAX) {
                $options[self::DELIVERY_DISTANCE] = self::DELIVERY_DISTANCE_MAX;
            }
        } else {
            $options[self::DELIVERY_DISTANCE] = self::DELIVERY_DISTANCE_MIN;
        }
        if ($options[self::UNLOADING] == self::UNLOADING_STAIRS || $options[self::UNLOADING] == self::UNLOADING_ELEVATOR) {
            // Этаж для разгрузки без лифта
            $options[self::UNLOADING_FLOOR] = abs($options[self::UNLOADING_FLOOR]);
            if ($options[self::UNLOADING_FLOOR] < self::UNLOADING_FLOOR_MIN) {
                $options[self::UNLOADING_FLOOR] = self::UNLOADING_FLOOR_MIN;
            }
            if ($options[self::UNLOADING_FLOOR] > self::UNLOADING_FLOOR_MAX) {
                $options[self::UNLOADING_FLOOR] = self::UNLOADING_FLOOR_MAX;
            }
        } else {
            $options[self::UNLOADING_FLOOR] = self::UNLOADING_FLOOR_MIN;
        }

        return $options;
    }


    /** Вычисляем итоговую стоимость доставки
     * @static
     * @param array $cart
     * @param array $orderOptions
     * @param array $calcOptions
     * @return    array
     * Формат ответа:
     * array(
     *        'price'    => ...,
     *        'info'    => '...'
     * )
     */
    protected static function calcDelivery($cart, &$orderOptions, $calcOptions)
    {
        if (count($cart) == 1 && $orderOptions[self::DELIVERY] == self::DELIVERY_MSK && abs(
                $cart[0]['item']['price_delivery']
            )) {
            // Один товар с особой ценой доставки по МСК
            return array(
                'price' => round($cart[0]['item']['price_delivery'], Catalog::PRICES_DECIMAL),
                'info' => 'По Москве'
            );
        }

        // Вычисляем сумарные значения заказанных товаров
        $totalPrice = $totalNonMetallPrice = $totalMetallPrice = 0;
        $accessoriesOnly = true;
        $nonMetallWthDiscount = false;

        //dd($cart);
        foreach ($cart as $c) {
            $p = $c['price'] * intval($c['amount']);
            $totalPrice += $p;
            if ($c['item']['is_metal']) {
                // Металл. мебель
                $totalMetallPrice += $p;
            } else {
                // Неметалл. мебель
                $totalNonMetallPrice += $p;
                if (!$c['item']['is_accessories']) {
                    // Среди неметалл. мебели не только аксессуары
                    $accessoriesOnly = false;
                }
                if (abs($c['item']['discount']) != 1) {
                    // Среди неметалл. мебели есть товары со скидкой
                    $nonMetallWthDiscount = true;
                }
            }
        }

        if ($orderOptions[self::DELIVERY] == self::DELIVERY_NO && $totalPrice < $calcOptions['pickup-min-sum']) {
            // Самовывоз доступен при заказе от 'pickup-min-sum'
            $orderOptions[self::DELIVERY] = self::DELIVERY_MSK;
        }
        if ($orderOptions[self::DELIVERY] == self::DELIVERY_NO) {
            // Самовывоз
            return array(
                'price' => 0,
                'info' => 'Самовывоз'
            );
        }

        // Доставка по МСК фиксирована, но зависит от различных параметров, которые проверяются ниже
        $nonMetallMsk = $calcOptions['office-delivery-msk'];
        $metallMsk = $calcOptions['metal-delivery-msk'];

        // Доставка за МКАД - это стоимость по МСК + расстояние * 2 * стоимость километра
        $nonMetallOutmsk = $calcOptions['office-delivery-outmsk'];
        $metallOutmsk = $calcOptions['metal-delivery-outmsk'];

        // Корректировка коэффициентов в зависимости от некоторых параметров
        if ($accessoriesOnly) {
            // В заказе из неметаллической мебели только аксессуары
            if ($orderOptions[self::DELIVERY] == self::DELIVERY_MSK || $orderOptions[self::DELIVERY] == self::DELIVERY_TO_SHIPPING_COMPANY) {
                // Фикс. часть стоимости доставки за МКАД и в пределах МСК для аксессуаров различается
                $nonMetallMsk = $calcOptions['accessories-delivery-msk'];
            } else {
                // Стоимость 1 КМ за МКАД для аксессуаров отличается от немет. мебели
                $nonMetallMsk = $calcOptions['accessories-delivery-outmsk-fixed'];
                $nonMetallOutmsk = $calcOptions['accessories-delivery-outmsk'];
            }
        } else {
            // В заказе из неметаллической мебели НЕ только аксессуары, считаем доставку по тарифу офисной мебели
            if ($calcOptions['office-delivery-free'] && $totalNonMetallPrice >= $calcOptions['office-delivery-free']) {
                if (!$nonMetallWthDiscount || !$calcOptions['office-delivery-msk-if-discount']) {
                    // Бесплатная доставка немет. мебели при сумме немет. мебели от 'office-delivery-free'
                    $nonMetallMsk = 0;
                } else {
                    // В заказе есть товары со скидкой, так что доставка всё равно платная, но дешевле
                    $nonMetallMsk = $calcOptions['office-delivery-msk-if-discount'];
                }
            } elseif ($calcOptions['office-delivery-msk-lower-from'] && $totalNonMetallPrice >= $calcOptions['office-delivery-msk-lower-from']) {
                // Понижение стоимости доставки немет. мебели в пределах МСК при сумме от 'office-delivery-msk-lower-from'
                $nonMetallMsk = $calcOptions['office-delivery-msk-lower'];
            }
        }
        if ($calcOptions['metal-delivery-free'] && $totalMetallPrice >= $calcOptions['metal-delivery-free']) {
            // Бесплатная доставка металлической мебели (в пределах МСК)
            $metallMsk = 0;
        }

        if ($orderOptions[self::DELIVERY] == self::DELIVERY_MSK) {
            // По Москве
            $price = 0;
            if ($totalNonMetallPrice) {
                $price += $nonMetallMsk;
            }
            if ($totalMetallPrice) {
                $price += $metallMsk;
            }
            return array(
                'price' => round($price, Catalog::PRICES_DECIMAL),
                'info' => 'По Москве'
            );
        } elseif ($orderOptions[self::DELIVERY] == self::DELIVERY_TO_SHIPPING_COMPANY) {
            // До трансп.компании (рассчёт как По Москве)
            $price = 0;
            if ($totalNonMetallPrice) {
                $price += $nonMetallMsk;
            }
            if ($totalMetallPrice) {
                $price += $metallMsk;
            }
            return array(
                'price' => round($price, Catalog::PRICES_DECIMAL),
                'info' => 'До транспортной компании'
            );
        } else/*if($orderOptions[self::DELIVERY] == self::DELIVERY_OUTMSK)*/ {
            // За МКАД
            $km = abs(round($orderOptions[self::DELIVERY_DISTANCE]));
            $price = 0;
            if ($totalNonMetallPrice) {
                $price += $nonMetallMsk;
                $price += $nonMetallOutmsk * $km * 2;
            }
            if ($totalMetallPrice) {
                $price += $metallMsk;
                $price += $metallOutmsk * $km * 2;
            }
            return array(
                'price' => round($price, Catalog::PRICES_DECIMAL),
                'info' => 'За МКАД (' . $km . ' км)'
            );
        }
    }


    /** Вычисляем итоговую стоимость разгрузки
     * @static
     * @param array $cart
     * @param array $orderOptions
     * @param array $calcOptions
     * @return    array
     * Формат ответа:
     * array(
     *        'price'    => ...,
     *        'info'    => '...'
     * )
     */
    protected static function calcUnloading($cart, &$orderOptions, $calcOptions)
    {
        if ($orderOptions[self::UNLOADING] == self::UNLOADING_NO) {
            // Разгрузка не нужна
            return array(
                'price' => 0,
                'info' => 'Не нужна'
            );
        } else {
            $price = 0;

            // Вычисляем сумарные значения заказанных товаров
            $totalNonMetallPrice = $totalMetallPrice = 0;    // Сумарная цена
            $totalNonMetallWeight = $totalMetallWeight = 0;    // Вес

            foreach ($cart as $c) {
                $c['amount'] = intval($c['amount']);
                if (abs($c['item']['price_unloading'])) {
                    $price += $c['item']['price_unloading'] * $c['amount'];
                } else {
                    $p = $c['price'] * $c['amount'];
                    $w = $c['item']['weight'] * $c['amount'];
                    if ($c['item']['is_metal']) {
                        // Металл. мебель
                        $totalMetallPrice += $p;
                        $totalMetallWeight += $w;
                    } else {
                        // Неметалл. мебель
                        $totalNonMetallPrice += $p;
                        $totalNonMetallWeight += $w;
                    }
                }
            }
            $totalNonMetallWeight = round($totalNonMetallWeight);
            $totalMetallWeight = round($totalMetallWeight);

            // Этаж
            $floor = abs(round($orderOptions[self::UNLOADING_FLOOR]));

            if ($totalMetallPrice) {        // Необходимость разгрузки мебели определяется не по положительному весу, а по положительной цене, т.к. вес может быть нулевым
                // Металл. мебель
                $p = 0;
                foreach (self::$metallUnloadingWeights as $w) {
                    // Опции зависят от веса
                    if ($w['from'] <= $totalMetallWeight && $totalMetallWeight <= $w['to']) {
                        $p = $calcOptions[$w['options'][0]] + $floor * $calcOptions[$w['options'][1]];
                        break;
                    }
                }
                $price += $p;
            }

            if ($totalNonMetallPrice) {    // Необходимость разгрузки мебели определяется не по положительному весу, а по положительной цене, т.к. вес может быть нулевым
                $f = $floor;

                if ($orderOptions[self::UNLOADING] == self::UNLOADING_ELEVATOR) {
                    // При наличии лифта считаем разгрузку как на 1-й этаж
                    $f = self::UNLOADING_FLOOR_MIN;
                }

                $p = $calcOptions['office-unloading-by-floor-kg'] * $totalNonMetallWeight * $f;

                if ($p < $calcOptions['office-unloading-min']) {
                    $p = $calcOptions['office-unloading-min'];
                }

                if ($p > $calcOptions['office-unloading-max']) {
                    $p = $calcOptions['office-unloading-max'];
                }

                $price += $p;
            }

            if ($orderOptions[self::UNLOADING] == self::UNLOADING_ELEVATOR) {
                $info = 'Нужна, есть лифт (' . $floor . ' эт.)';
            } else/*if($orderOptions[self::UNLOADING] == self::UNLOADING_STAIRS)*/ {
                $info = 'Нужна, нет лифта (' . $floor . ' эт.)';
            }

            return array(
                'price' => round($price, Catalog::PRICES_DECIMAL),
                'info' => $info
            );
        }
    }


    /** Вычисляем итоговую стоимость сборки
     * @static
     * @param array $cart
     * @param array $orderOptions
     * @param array $calcOptions
     * @return    array
     * Формат ответа:
     * array(
     *        'price'        => ...,
     *        'info'        => '...',
     *        'comment'    => '...'    // В случае нулевой стоимости сборки здесь даётся пояснение: бесплатно или не требуется (для металл.мебели)
     * )
     */
    protected static function calcAssembly($cart, &$orderOptions, $calcOptions)
    {
        if ($orderOptions[self::ASSEMBLY] == self::ASSEMBLY_NO) {
            // Сборка не нужна
            return array(
                'price' => 0,
                'info' => 'Не нужна',
                'comment' => ''
            );
        } else/*if($orderOptions[self::ASSEMBLY] == self::ASSEMBLY_YES)*/ {
            // Сборка нужна

            // Цены на сборку металл. мебели разных серий
            static $metallSeries;
            if (!$metallSeries) {
                $oMetallSeries = new Catalog_MetallSeries();
                $metallSeries = $oMetallSeries->getHash('id, price_accembly');
            }

            $assemblyNonMetall = 0;
            $isUsualFurn = false;
            $assemblyNonMetallHangers = 0;
            $assemblyMetall = 0;
            $comment = '';
            foreach ($cart as $c) {
                if (!intval($c['amount'])) {
                    continue;
                }
                if (intval($c['item']['free_assembly'])) {
                    $comment = 'Бесплатно';
                    continue;
                }
                if (intval($c['item']['no_assembly'])) {
                    if ($comment === '') {
                        $comment = 'Не требуется';
                    }
                    continue;
                }

                if ($c['item']['is_metal']) {
                    // Металл. мебель
                    $isMetall = true;
                    if (abs($c['item']['price_assembly'])) {
                        $assemblyMetall += $c['item']['price_assembly'] * $c['amount'];
                    } elseif (isset($metallSeries[$c['item']['metall_series_id']])) {
                        $assemblyMetall += $metallSeries[$c['item']['metall_series_id']] * $c['amount'];
                    } else {
                        $assemblyMetall += $calcOptions['metal-assembly-default'] * $c['amount'];
                    }
                } else {
                    // Неметалл. мебель
                    if ($c['item']['is_hanger']) {
                        // Вешалка (фикс. стоимость сборки)
                        if (abs($c['item']['price_assembly'])) {
                            $assemblyNonMetallHangers += $c['item']['price_assembly'] * $c['amount'];
                        } else {
                            $assemblyNonMetallHangers += $calcOptions['office-assembly-hanger-price'] * $c['amount'];
                        }
                    } else {
                        // Обычная мебель (стоимость сборки - 6% от стоимости мебели)
                        $isUsualFurn = true;
                        if (abs($c['item']['price_assembly'])) {
                            $assemblyNonMetall += $c['item']['price_assembly'] * $c['amount'];
                        } else {
                            $assemblyNonMetall += $c['price'] * $c['amount'] * Catalog::percent2num(
                                    $calcOptions['office-assembly-percent']
                                );
                        }
                    }
                }
            }
            if ($isUsualFurn && $assemblyNonMetall < $calcOptions['office-assembly-min']) {
                // Сборка оф.меб. не менее...
                $assemblyNonMetall = $calcOptions['office-assembly-min'];
            }
            $assemblyNonMetall += $assemblyNonMetallHangers;    // Отдельно прибаввляем сборку вешалок

            $price = round($assemblyNonMetall + $assemblyMetall, Catalog::PRICES_DECIMAL);
            if ($price != 0) {
                $comment = '';
            }

            return array(
                'price' => $price,
                'info' => 'Нужна',
                'comment' => $price == 0 ? $comment : ''
            );
        }
    }


    /** Вычисляем итоговую стоимость вывоза мусора
     * @static
     * @param array $cart
     * @param array $orderOptions
     * @param array $calcOptions
     * @return    array
     * Формат ответа:
     * array(
     *        'price'    => ...,
     *        'info'    => '...'
     * )
     */
    protected static function calcGarbage($cart, &$orderOptions, $calcOptions)
    {
        if ($orderOptions[self::GARBAGE] == self::GARBAGE_NO) {
            return array(
                'price' => 0,
                'info' => 'Не нужен'
            );
        } else/*if($orderOptions[self::GARBAGE] == self::GARBAGE_YES)*/ {
            return array(
                'price' => round($calcOptions['garbage'], Catalog::PRICES_DECIMAL),
                'info' => 'Нужен'
            );
        }
    }


    /**
     * @param int $itemId
     * @return    number
     */
    static function deliveryForItem($itemId)
    {
        $itemId = intval($itemId);

        $cart = Catalog_Cart::get(array(
            $itemId => array(
                0 => 1
            )
        ));
        $options = array(
            self::DELIVERY => self::DELIVERY_MSK,
            self::UNLOADING => self::UNLOADING_ELEVATOR,
            self::ASSEMBLY => self::ASSEMBLY_YES,
            self::GARBAGE => self::GARBAGE_NO,
            self::DELIVERY_DISTANCE => 0,
            self::UNLOADING_FLOOR => 0
        );
        $o = new self();
        $result = $o->calc($cart, $options, array(self::DELIVERY));
        return abs($result[self::DELIVERY]['price']);
    }


    /**
     * @param $set
     * @return array
     */
    static function deliveryAndAssemblyForSet($set)
    {
        $cart = Catalog_Cart::get($set);
        //print_array($cart);
        $options = array(
            self::DELIVERY => self::DELIVERY_MSK,
            self::UNLOADING => self::UNLOADING_ELEVATOR,
            self::ASSEMBLY => self::ASSEMBLY_YES,
            self::GARBAGE => self::GARBAGE_NO,
            self::DELIVERY_DISTANCE => 0,
            self::UNLOADING_FLOOR => 0
        );
        $o = new self();
        $result = $o->calc($cart, $options);
        return array(
            $result[self::DELIVERY]['price'],
            $result[self::ASSEMBLY]['price'],
            $result[self::UNLOADING]['price'],
            $result[self::ASSEMBLY]['comment']
        );
    }
}
