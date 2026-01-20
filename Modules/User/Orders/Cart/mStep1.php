<?php

/** Оформление заказа, Шаг 1
 * @author	Seka
 */

class mStep1 {

  /**
   * @var int
   */
  static $output = OUTPUT_DEFAULT;

  /**
   * @static
   * @param array $pageInf
   * @return string
   */
  static function main(&$pageInf = array()){

    Catalog_Cart::fix();

    // Данные корзины
    $cart = Catalog_Cart::get();
    if(!count($cart) || Dealers_Security::isAuthorized()){
      header('Location: ' . Url::a('catalog-cart'));
      exit;
    }
    list($totalAmount, $totalPrice, $totalPriceOld) = Catalog_Cart::total();

    // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
    // Добавляем материалы — аккуратно, без потери price
    // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

    // Получаем дерево материалов для всех серий в корзине
    $seriesIds = array();
    foreach($cart as $c){
      if(!empty($c['series']['id'])){
        $seriesIds[] = (int)$c['series']['id'];
      }
    }
    $seriesIds = array_unique($seriesIds);
    $materials = array();
    if(!empty($seriesIds)){
      $oMaterials = new Catalog_Materials();
      $materials = $oMaterials->getTree($seriesIds);
    }

    // Получаем материалы для каждого товара и добавляем ТОЛЬКО ключ 'materials'
    $oItems2Materials = new Catalog_Items_2Materials();
    foreach($cart as &$c){
      // Сохраняем исходный item — на всякий случай
      $itemId = (int)($c['item']['id'] ?? 0);
      if ($itemId > 0) {
        // Добавляем ТОЛЬКО подключ 'materials', не трогая остальное
        $c['item']['materials'] = $oItems2Materials->get('*', '`item_id` = ' . $itemId);
      }
    }
    unset($c); // разрыв ссылки

    // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

    // Название страницы "Корзина" для хлебных крошек
    $oPages = new Pages();
    $cartPageName = $oPages->getCell('name', '`alias` = \'catalog-cart\'');

    // Исходные данные
    $init = array();
    if(isset($_SESSION['cart-user']) && is_array($_SESSION['cart-user'])){
      $init['user'] = $_SESSION['cart-user'];
    }

    // Собираем шаблон
    $tpl = Pages::tplFile($pageInf);
    $formHtml = pattExeP(fgc($tpl), array(
      'pageInf'		=> $pageInf,
      'cartPageName'	=> $cartPageName,
      'cart'			=> $cart,
      'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
      'totalAmount'	=> $totalAmount,
      'totalPrice'	=> $totalPrice,
      'totalPriceOld'	=> $totalPriceOld,
      'materials'		=> $materials,

      'userInf'	=> $init['user']
    ));

    // Выводим форму
    $frm = new Form($formHtml, 'cart-form1');
    $frm->setInit($init);
    return $frm->run('mStep1::save');
  }


  /** Переход к следующему шагу оформления заказа
   * @param $initData
   * @param $newData
   */
  static function save($initData, $newData){
    $_SESSION['cart-user'] = $newData['user'];

    header('Location: ' . Url::a('catalog-cart-step2'));
    exit;
  }
}

?>
