<?php

/** Оформление заказа, Шаг 3
 * @author    Seka
 */

class mStep3
{

    /**
     * @var array
     */
    static $cart = array();

    /**
     * @var array
     */
    static $user = array();

    /**
     * @var array
     */
    static $optionsPrices = array();

    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        Catalog_Cart::fix();

        // Данные корзины
        self::$cart = Catalog_Cart::get();
        if (!count(self::$cart) || Dealers_Security::isAuthorized()) {
            header('Location: ' . Url::a('catalog-cart'));
            exit;
        }
        list($totalAmount, $totalPrice, $totalPriceOld) = Catalog_Cart::total();

        // Название страницы "Корзина" для хлебных крошек
        $oPages = new Pages();
        $cartPageName = $oPages->getCell('name', '`alias` = \'catalog-cart\'');

        // Проверяем, введены ли данные юзера
        if (!isset($_SESSION['cart-user']) || !is_array($_SESSION['cart-user'])) {
            header('Location: ' . Url::a('catalog-cart-step1'));
            exit;
        }
        self::$user = $_SESSION['cart-user'];

        // Проверяем, указаны ли опции заказа (доставка, сборка и проч.)
        if (!isset($_SESSION['cart-options']) || !is_array($_SESSION['cart-options'])) {
            header('Location: ' . Url::a('catalog-cart-step2'));
            exit;
        }
        $options = $_SESSION['cart-options'];

        // Вычисляем стоимость опций заказа
        $oOptions = new Orders_Options();
        self::$optionsPrices = $oOptions->calc(self::$cart, $options);

        // Исходные данные
        $init = array();
        if (isset($_SESSION['cart-paymethod']) && isset(Orders::$paymethods[$_SESSION['cart-paymethod']])) {
            $p = $_SESSION['cart-paymethod'];
        } else {
            $ak = array_keys(Orders::$paymethods);
            $p = array_shift($ak);
        }
        $init['paymethod'] = $p;

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id'])),
            'cartPageName' => $cartPageName,
            'cart' => self::$cart,
            'totalAmount' => $totalAmount,
            'totalPrice' => $totalPrice,
            'totalPriceOld' => $totalPriceOld,
            'optionsPrices' => self::$optionsPrices
        ));

        // Выводим форму
        $frm = new Form($formHtml);
        $frm->setInit($init);

        return $frm->run('mStep3::save', 'mStep3::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        if (!isset(Orders::$paymethods[$_POST['paymethod']]) || $_POST['paymethod'] === Orders::PAYMETHOD_NO) {
            return array(
                array(
                    'msg' => 'Выберите способ оплаты заказа'
                )
            );
        }

        return true;
    }


    /** Сохранение заказа
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        // Запоминаем способ оплаты
        $_SESSION['cart-paymethod'] = $paymethod = $newData['paymethod'];

        // Оформляем заказ
        $oOrders = new Orders();
        $orderId = $oOrders->newOrder(
            self::$cart,
            self::$optionsPrices,
            self::$user,
            $paymethod
        );

        Catalog_Cart::clear();

        Pages::flash(
            'Ваш заказ успешно оформлен.<br>В ближайшее время с вами свяжется представитель магазин для уточнения деталей.',
            false,
            Orders::a($orderId)
        );
    }
}
