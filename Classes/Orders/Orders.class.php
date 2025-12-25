<?php

/**
 * Заказы
 *
 * @author    Seka
 */

class Orders extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'orders';

    /**
     * ID основной страницы каталога
     */
    const ORDERS_PAGE_ID = 610;

    /**
     * Способы оплаты заказа
     */
    const PAYMETHOD_NO = 'no';
    const PAYMETHOD_CASH = 'cash';
    const PAYMETHOD_BANK = 'bank';
    const PAYMETHOD_CARD = 'card';
    const PAYMETHOD_RASR = 'rasr';

    /**
     * @var array
     */
    static $paymethods = array(
        self::PAYMETHOD_NO => 'Не указан',
        self::PAYMETHOD_BANK => 'Банковский перевод',
        self::PAYMETHOD_RASR => 'Рассрочка/Кредит',
        self::PAYMETHOD_CARD => 'Кредитная карта',
        self::PAYMETHOD_CASH => 'Наличный расчёт'
    );

    /**
     * @var array
     */
    static $paymethodsComments = array(
        self::PAYMETHOD_NO => '',
        self::PAYMETHOD_CARD => 'Mir, MasterCard или Visa',
        self::PAYMETHOD_RASR => 'Cчет/Договор',
        self::PAYMETHOD_CASH => 'Вы сможете оплатить стоимость заказа наличными при получении заказа от курьера. <span class="text-red-500">Только при доставке курьером!</span>',
        self::PAYMETHOD_BANK => 'Cчет/Договор'
    );

    /**
     * @var string
     */
    protected static $codeSalt = ';aksjdnfias9er987q24urnjdsf vk ziux vi7aer7ftq879';


    /** Оформление заказа
     * @param array $cart Содержимое корзины (из Catalog_Cart::get())
     * @param array $optionsPrices Цены и описания доп.опций заказа (из Orders_Options::calc())
     * @param array $user Данные юзера (из формы)
     * @param string $paymethod
     * @return    int    ID заказа
     */
    function newOrder(array $cart, array $optionsPrices, array $user, string $paymethod): int
    {
        // Начальный статус заказа
        $oStatuses = new Orders_Statuses();
        $status = $oStatuses->getRow(
            '*',
            '',
            'order'
        );

        if (!$status) {
            $status = Orders_Statuses::$default;
        }

        // Проверяем данные юзера
        $user = [
            'fio' => $user['fio'],
            'phone' => $user['phone'],
            'email' => isset($user['email']) ? trim($user['email']) : '',
            'city' => isset($user['city']) ? trim($user['city']) : '',
            'address' => isset($user['address']) ? trim($user['address']) : '',
            'info' => isset($user['info']) ? trim($user['info']) : '',
        ];

        // Сумма заказа
        $totalPriceIn = 0;
        $totalPriceOut = 0;
        $totalAmount = 0;

        foreach ($cart as $c) {
            $totalPriceIn += $c['price-in'] * $c['amount'];
            $totalPriceOut += $c['price'] * $c['amount'];
            $totalAmount += $c['amount'];
        }

        $priceOptions = 0;

        foreach ($optionsPrices as $op) {
            $priceOptions += $op['price'];
        }

        // Проверяем способ оплаты
        if (!isset(self::$paymethods[$paymethod])) {
            $ak = array_keys(self::$paymethods);
            $paymethod = array_shift($ak);
        }

        // Код заказа
        $code = md5(rand(1, 1000000) . rand(1, 1000000) . self::$codeSalt);

        $dt = MySQL::dateTime();
        $order = [
            'code' => $code,
            'date' => $dt,
            'cart' => serialize($cart),
            'price_in' => $totalPriceIn,
            'price_out' => $totalPriceOut,
            'amount' => $totalAmount,
            'options' => serialize($optionsPrices),
            'price_options' => $priceOptions,
            'user' => serialize($user),
            'paymethod' => $paymethod,
            'status_id' => $status['id'],
            'status_name' => $status['name'],
            'status_color' => $status['color'],
            'status_date' => $dt
        ];

        $orderId = $this->add($order);

        // Костыль: т.к. в классе с БД свалено все в кучу, то возвращаемый результат может не быть ID
        // Поэтому делаем еще один запрос, чтобы получить ID новой записи.
        if (is_null($orderId) || !intval($orderId)) {
            $orderId = self::getCell('id', '`code` = \'' . $code . '\'', 'id DESC');
        }

        $tpl = Pages::tplFile(array('module' => 'User/Orders/mOrder'), 'user', 'eml');
        $userInfo = pattExeP(fgc($tpl), array(
            'user' => $user,
            'paymethod' => $paymethod
        ));

        $tpl = Pages::tplFile(array('module' => 'User/Orders/mOrder'), 'items', 'eml');
        $orderInfo = pattExeP(fgc($tpl), array(
            'cart' => $cart,
            'paymethod' => $paymethod,
            'optionsPrices' => $optionsPrices,
            'totalAmount' => $totalAmount,
            'totalPriceOut' => $totalPriceOut,
            'priceOptions' => $priceOptions
        ));

        // Письмо администратору
        Email_Tpl::send(
            'order-to-admin',
            Options::name('admin-order-email'),
            array(
                'id' => $orderId,
                'userInfo' => $userInfo,
                'orderInfo' => $orderInfo,
                'paymethod' => self::$paymethods[$paymethod]
            )
        );

        // Письмо покупателю
        Email_Tpl::send(
            'order-to-user',
            $user['email'],
            array(
                'fio' => $user['fio'],
                'userInfo' => $userInfo,
                'orderInfo' => $orderInfo,
                'paymethod' => self::$paymethods[$paymethod],
                'url' => 'http://' . $_SERVER['SERVER_NAME'] . self::a($orderId)
            )
        );

        return $orderId;
    }


    /** Генерация URL'а страницы для просмотра заказа
     * @static
     * @param int $orderId
     * @return    string|bool        false, если заказ е найден
     */
    static function a($orderId)
    {
        static $o;
        if (!$o) {
            $o = new self();
        }
        static $cache = array();

        $orderId = intval($orderId);
        if (isset($cache[$orderId])) {
            return $cache[$orderId];
        }

        $code = $o->getCell('code', '`id` = ' . $orderId);
        if ($code) {
            $cache[$orderId] = Url::a(self::ORDERS_PAGE_ID) . $code . '/';
        } else {
            $cache[$orderId] = false;
        }
        return $cache[$orderId];
    }


    /** Определяем ID заказа по URL'у
     * @param string $url
     * @return    int        $orderId
     * 0 - заказ не найден
     */
    function detectByUrl($url = ''): int
    {
        if ($url === '') {
            $url = Url::$current;
        }

        $oUrl = new Url();
        $oUrl->parse($url, $oUrl::$parsed['pageId'], $oUrl::$parsed['pagePath'], $oUrl::$parsed['getVars']);

        $pageId = $oUrl::$parsed['pageId'];
        $pagePath = $oUrl::$parsed['pagePath'];
        $getVars = $oUrl::$parsed['getVars'];

        if ($pageId != self::ORDERS_PAGE_ID) {
            // URL не соответствует странице заказов
            return 0;
        }

        // Получаем массив частей URL
        $urlPath = array();
        foreach ($getVars as $k => $v) {
            if (preg_match('/^p[0-9]+$/', $k)) {
                $urlPath[] = $v;
            }
        }

        // Получаем часть URL, являющуюся кодом заказа
        if (count($urlPath)) {
            $code = $urlPath[0];
            $oId = $this->getCell(
                'id',
                '`code` = \'' . MySQL::mres($code) . '\''
            );
            return intval($oId);
        } else {
            // В URL'е нет кода заказа
            return 0;
        }
    }


    /**
     * Устанавливаем указанный статус для заказа
     * @param int $orderId
     * @param int $statusId
     */
    function setStatus($orderId, $statusId)
    {
        $orderId = intval($orderId);
        $statusId = intval($statusId);


        $oStatuses = new Orders_Statuses();
        $status = $oStatuses->getRow(
            '*',
            '`id` = ' . $statusId
        );
        if (!$status) {
            $status = Orders_Statuses::$default;
        }

        $this->upd(
            $orderId,
            array(
                'status_id' => $status['id'],
                'status_name' => $status['name'],
                'status_color' => $status['color'],
                'status_date' => MySQL::dateTime()
            )
        );
    }
}
