<?php

/** Корзины товаров
 * @author	Seka
 */

class mCart {

  /**
   * @var int
   */
  static $output = OUTPUT_DEFAULT;

  static $cart = array();

  /**
   * @static
   * @param array $pageInf
   * @return string
   */
  static function main(&$pageInf = array()){

    Catalog_Cart::fix();

    if(isset($_GET['set-info'])){
      return self::setInfo();
    }

    if(intval($_GET['clear'])){
      // Очистка корзины
      self::clear();
      exit;
    }
    if(intval($_GET['recalc'])){
      if(!Dealers_Security::isAuthorized()){
        // Пересчёт к-ва товаров
        self::recalc($_GET['amount']);
      }else{
        // Пересчёт к-ва товаров
        self::recalc($_GET['amount'], false);

        // Ручное изменение дилером скидок на товары
        self::setDealerDiscounts($_GET['discount']);
      }

      exit;
    }
    if(intval($_GET['reset-discounts']) && Dealers_Security::isAuthorized()){
      self::resetDealerDiscounts();
      exit;
    }
    if(is_array($_GET['add']) && count($_GET['add'])){
      // Добавление нескольких товаров в корзину
      error_log('DEBUG: addSet called with: ' . print_r($_GET, true));
      return self::addSet($_GET['add'], $_GET['amount'], $_GET['material_id']);
    }
    if($itemId = intval($_GET['add'])){
      // Добавление товара в корзину
      error_log('DEBUG: add called with itemId: ' . $itemId);
      return self::add($itemId, intval($_GET['amount']), intval($_GET['material_id']));
    }
    if($itemId = intval($_GET['remove'])){
      // Удаление товара из корзины
      self::remove($itemId, intval($_GET['material_id']));
      exit;
    }

    // Данные корзины
    self::$cart = Catalog_Cart::get();

    if(intval($_GET['options-calc']) || intval($_POST['options-calc'])){
      // Пересчёт стоимости опций заказа
      if(intval($_GET['options-calc'])){
        return self::calcOptions($_GET['options']);
      }elseif(intval($_POST['options-calc'])){
        return self::calcOptions($_POST['options']);
      }
    }

    if(count(self::$cart)){
      if(Dealers_Security::isAuthorized()){
        // Редирект на оформление КП для дилера
        header('Location: ' . Url::a('dealer-offer'));
      }else{
        // Редирект на 1-й шаг оформления заказа
        header('Location: ' . Url::a('catalog-cart-step1'));
      }
      exit;
    }else{
      // Корзина пуста, выводим просто текстовую стр.
      $tpl = Pages::tplFile($pageInf);
      return pattExeP(fgc($tpl), array(
        'pageInf'	=> $pageInf,
        'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id']))
      ));
    }
  }


  /**
   * @static
   * @param	array	$options
   * @return	array
   */
  static function calcOptions($options){
    if(!is_array($options)){
      $options = Orders_Options::$default;
    }


    $oOptions = new Orders_Options();
    $optionsPrices = $oOptions->calc(self::$cart, $options);

    $_SESSION['cart-options'] = $options;

    if(intval($_GET['ajax'])){
      self::$output = OUTPUT_JSON;
      return $optionsPrices;
    }else{
      Pages::flash('Стоимость опций заказа рассчитана');
      exit;
    }
  }


  /**
   * @static
   */
  static function clear(){
    Catalog_Cart::clear();
    Pages::flash('Корзина очищена, все товары из неё удалены');
    exit;
  }


  /**
   * @static
   * @param	array	$newCart	(itemId => (matId => amount, matId => amount))
   * @param	bool	$exit
   */
  static function recalc($newCart, $exit = true){

    Catalog_Cart::clear();

    foreach($newCart as $itemId => $mat2amount){
      foreach($mat2amount as $matId => $amount){
        Catalog_Cart::add($itemId, $amount, $matId);
      }
    }

    if($exit){
      Pages::flash('Количество товаров и общая сумма в корзине обновлены');
      exit;
    }
  }


  /**
   * @param	array	$discounts
   */
  static function setDealerDiscounts($discounts){
    Dealers_Offers::discountsSet(
      Catalog_Cart::get(),
      $discounts
    );
    Pages::flash('Количество товаров и общая сумма в корзине обновлены. Скидки на товары установлены.');
    exit;
  }


  static function resetDealerDiscounts(){
    Dealers_Offers::discountsClear();
    Pages::flash('Скидки на товары восстановлены в начальные значения.');
    exit;
  }


  /**
   * @static
   * @param	int	$itemId
   * @param	int	$amount
   * @param	int	$materialId
   * @return	array
   */
  static function add($itemId, $amount, $materialId){
    $itemId = intval($itemId);
    $amount = intval($amount);
    $materialId = intval($materialId);

    Catalog_Cart::add($itemId, $amount, $materialId);

    if(intval($_GET['ajax'])){
      self::$output = OUTPUT_JSON;
      return Catalog_Cart::total();
    }else{
      Pages::flash('Товар успешно добавлен в корзину');
      exit;
    }
  }


  /**
   * @param	array	$itemsIds
   * @param	array	$i2amounts
   * @param	array	$i2materialsIds
   * @return	array
   */
  static function addSet($itemsIds, $i2amounts, $i2materialsIds){
    error_log('DEBUG: addSet function called');
    error_log('DEBUG: itemsIds: ' . print_r($itemsIds, true));
    error_log('DEBUG: i2amounts: ' . print_r($i2amounts, true));
    error_log('DEBUG: i2materialsIds: ' . print_r($i2materialsIds, true));

    if(is_array($itemsIds)){
      $itemsIds = array_map('intval', $itemsIds);
      $itemsIds = array_unique($itemsIds);
      foreach($itemsIds as $itemId){
        $amount = intval($i2amounts[$itemId]);
        $materialId = intval($i2materialsIds[$itemId]);
        error_log('DEBUG: Adding item ' . $itemId . ' amount ' . $amount . ' material ' . $materialId);
        Catalog_Cart::add($itemId, $amount, $materialId);
      }
    }

    if(intval($_GET['ajax'])){
      self::$output = OUTPUT_JSON;
      return Catalog_Cart::total();
    }else{
      Pages::flash('Товары успешно добавлены в корзину');
      exit;
    }
  }


  /**
   * @static
   * @param	int	$itemId
   * @param	int	$materialId
   */
  static function remove($itemId, $materialId){
    $itemId = intval($itemId);
    $materialId = intval($materialId);

    Catalog_Cart::remove($itemId, $materialId);

    Pages::flash('Товар удалён из корзины');
    exit;
  }


  /**
   * @return array
   */
  static function setInfo(){
    self::$output = OUTPUT_JSON;
    $set = Catalog_Cart::unpackSetString(trim($_GET['set-info']));
    return Orders_Options::deliveryAndAssemblyForSet($set);
  }
}

?>
