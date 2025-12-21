<?php

/** Покупка товара в 1 клик
 * @author    Seka
 */

class mBuyOneClick
{

    /**
     * @var int
     */
    static $output = OUTPUT_DEFAULT;

    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $ajaxForm = (intval($_GET['ajax']) !== 0 || $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' || intval(
                $_POST['XMLHttpRequest']
            ) !== 0);
        if ($ajaxForm) {
            self::$output = OUTPUT_FRAME;
        }

        $init = array(
            'set' => trim($_GET['set'])
        );
        if (isset($_SESSION['cart-user']) && is_array($_SESSION['cart-user'])) {
            foreach ($_SESSION['cart-user'] as $f => $v) {
                $init[$f] = $v;
            }
        }

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'ajaxForm' => $ajaxForm,
            'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id']))
        ));
        // Выводим форму
        $frm = new Form($formHtml, false, 'form-errors');
        $frm->setInit($init);
        return $frm->run('mBuyOneClick::save');
    }


    /** Сохранение заказа
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        // Данные корзины
        $cart = Catalog_Cart::get(Catalog_Cart::unpackSetString(trim($newData['set'])));
        if (!count($cart) || Dealers_Security::isAuthorized()) {
            header('Location: ' . Url::a('catalog-cart'));
            exit;
        }

        // Вычисляем стоимость опций заказа
        $oOptions = new Orders_Options();
        $options = Orders_Options::$default;
        $optionsPrices = $oOptions->calc($cart, $options);

        // Запоминаем данные юзера в сессию
        $user = array();
        if (!is_array($_SESSION['cart-user'])) {
            $_SESSION['cart-user'] = array();
        }
        foreach (array('fio', 'phone') as $f) {
            $user[$f] = trim($newData[$f]);
            $_SESSION['cart-user'][$f] = trim($newData[$f]);
        }

        // Сохраняем заказ
        $user['info'] = 'Покупка товара за 1 клик';
        $oOrders = new Orders();
        $orderId = $oOrders->newOrder(
            $cart,
            $optionsPrices,
            $user,
            Orders::PAYMETHOD_NO
        );

        if (self::$output == OUTPUT_FRAME) {
            $_SESSION['flash_msg'] = '<div class="notetext mb-6">
                <div class="text-2xl font-semibold">Ваш заказ успешно оформлен.</div>
                <div class="text-base font-normal text-gray-700 mt-2">
                  В ближайшее время с вами свяжется представитель для уточнения деталей.
                </div>
              </div>';
            echo '{"location":"' . Orders::a($orderId) . '"}';
            exit;
            //print Pages::msgOk('Ваш заказ успешно отправлен администрации сайта. В ближайшее время с вами свяжутся для уточнения деталей.');
        } else {
            Pages::flash(
                'Ваш заказ успешно отправлен администрации сайта. В ближайшее время с вами свяжутся для уточнения деталей.'
            );
        }
        exit;
    }
}
