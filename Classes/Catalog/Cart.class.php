<?php

/**
 * Корзина товаров
 *
 * @author	Seka
 */

class Catalog_Cart {

	/**
	 * Внутри данного класса корзина хранится в сессии в простом массиве такой структуры:
	 * (
	 * 	itemId => array(matId => amount, matId => amount, matId => amount, ...),
	 * 	itemId => array(matId => amount, matId => amount, ...),
	 * 	...
	 * )
	 */
	const SESS_KEY = 'cart';


	/**
	 * @static
	 * @param	int	$itemId
	 * @param	int	$amount
	 * @param	int	$materialId
	 */
	static function add($itemId, $amount = 1, $materialId = 0){
		$itemId = intval($itemId);
		$amount = intval($amount);
		$materialId = intval($materialId);
		if(!$amount) return;

		$oItems = new Catalog_Items();
		if(!$oItems->getCell('id', '`id` = ' . $itemId)){
			return;
		}

		$oItems2Materials = new Catalog_Items_2Materials();
		if($materialId){
			$oMaterials = new Catalog_Materials();
			$topMatId = $oMaterials->getFirstLevelId($materialId);
			if(!$oItems2Materials->getCell('id', '`material_id` = ' . $topMatId . ' AND `item_id` = ' . $itemId)){
				return;
			}
		}

		if(!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		if(!isset($_SESSION[self::SESS_KEY][$itemId][$materialId])){
			$_SESSION[self::SESS_KEY][$itemId][$materialId] = 0;
		}
		$_SESSION[self::SESS_KEY][$itemId][$materialId] += $amount;
	}


	/**
	 * @static
	 * @param	int	$itemId
	 * @param	int	$materialId
	 */
	static function remove($itemId, $materialId = 0){
		$itemId = intval($itemId);
		$materialId = intval($materialId);
		unset($_SESSION[self::SESS_KEY][$itemId][$materialId]);
		if(isset($_SESSION[self::SESS_KEY][$itemId]) && !count($_SESSION[self::SESS_KEY][$itemId])){
			unset($_SESSION[self::SESS_KEY][$itemId]);
		}
		if(Dealers_Security::isAuthorized()){
			Dealers_Offers::discountsRemove($itemId, $materialId);
		}
	}


	/**
	 * @static
	 */
	static function clear(){
		$_SESSION[self::SESS_KEY] = array();
		if(Dealers_Security::isAuthorized()){
			Dealers_Offers::discountsClear();
		}
	}


	/**
	 * Получаем содержимое корзины в виде массива-таблицы с полями:
	 * 'item':		Сведения о товаре (из Catalog_Items)
	 * 'material':	Сведения о материале (из Catalog_Materials)
	 * 'price':		Конечная расчитанная выходная цена для данного товара с выбранным материалом (для 1 штуки товара)
	 * 'amoiunt':	К-во товара
	 * @static
	 * @param	array	$tmpCart
	 * @return	array
	 */
	static function get($tmpCart = array()){
		$srcCart = count($tmpCart) ? $tmpCart : $_SESSION[self::SESS_KEY];

		if(!is_array($srcCart)) $srcCart = array();
		if(!count($srcCart)) return array();

		static $prevCart, $prevResult;
		if($prevCart && serialize($prevCart) === serialize($srcCart)){
			return $prevResult;
		}

		$oItems = new Catalog_Items();
		$items = $oItems->imageExtToData($oItems->getWhtKeys(
			'*',
			'`id` IN (' . implode(',', array_keys($srcCart)) . ')'
		));

		$oMaterials = new Catalog_Materials();

		$cart = array();
		foreach($srcCart as $itemId => $mat2amount){
			$materials = $oMaterials->imageExtToData($oMaterials->getWhtKeys(
				'*',
				'`id` IN (' . implode(',', array_keys($mat2amount)) . ')'
			));

			foreach($mat2amount as $matId => $amount){
				$d = Dealers_Security::isAuthorized() ? Dealers_Offers::discount4item($itemId, $matId) : false;

				list($priceIn, $priceOut) = $oMaterials->price4item($itemId, $matId, $d);
				$cart[] = array(
					'item'			=> $items[$itemId],
					'material'		=> isset($materials[$matId]) ? $materials[$matId] : false,
					'price-in'		=> $priceIn,
					'price'			=> $priceOut,
					'amount'		=> $amount
				);
			}
		}

		// Получаем названия и URL'ы серий для каждого товара в корзине
		$seriesIds = array();
		foreach($items as $i) $seriesIds[] = $i['series_id'];
		$seriesIds = array_unique($seriesIds);
		if(count($seriesIds)){
			$oSeries = new Catalog_Series();
			$series = $oSeries->getWhtKeys(
				'id, name, category_id, url',
				'`id` IN (' . implode(',', $seriesIds) . ')'
			);
			foreach($cart as &$c){
				$sId = intval($c['item']['series_id']);
				$c['series'] = isset($series[$sId]) ? $series[$sId] : false;
			}
			unset($c);
		}

		$prevCart = $srcCart;
		$prevResult = $cart;

		return $cart;
	}


	/**
	 * Корректировка содержимого корзины (чтобы для всех товаров были выбраны корректные материалы)
	 * Рассинхронизация может произойти, если админ будет редактировать данные, когда у пользователя что-то в корзине
	 * Запускать метод следует периодически, особенно, при обновлении корзины, и ОСОБЕННО - перед оформлением заказа
	 * @static
	 */
	static function fix(){
		if(!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		if(!count($_SESSION[self::SESS_KEY])) return;

		$oItems = new Catalog_Items();
		$items = $oItems->getCol(
			'id',
			'`id` IN (' . implode(',', array_keys($_SESSION[self::SESS_KEY])) . ')'
		);

		$oItems2Materials = new Catalog_Items_2Materials();
		$tmp = $oItems2Materials->get(
			'item_id, material_id',
			'`item_id` IN (' . implode(',', array_keys($_SESSION[self::SESS_KEY])) . ')',
			'`' . Catalog_Items_2Materials::$tab . '`.id ASC',
			0,
			'
				JOIN `' . Catalog_Items::$tab . '` AS `ci` ON (`ci`.id = `' . Catalog_Items_2Materials::$tab . '`.item_id)
				JOIN `' . Catalog_Series_2Materials::$tab . '` AS `cs2m` ON (`cs2m`.series_id = `ci`.series_id AND `cs2m`.material_id = `' . Catalog_Items_2Materials::$tab . '`.material_id)
			'
		);
		$items2materials = array();
		foreach($tmp as $im){
			$items2materials[$im['item_id']][] = $im['material_id'];
		}

		$oMaterials = new Catalog_Materials();

		foreach($_SESSION[self::SESS_KEY] as $itemId => $mat2amount){
			if(!count($mat2amount) || !in_array($itemId, $items)){
				foreach($mat2amount as $matId => $amount){
					self::remove($itemId, $matId);
				}
				continue;
			}
			foreach($mat2amount as $matId => $amount){
				//list($tmp, $price) = $oMaterials->price4item($itemId, $matId);
				if(!$amount/* || !$price*/){
					self::remove($itemId, $matId);
					continue;
				}

				if($matId){
					// Материал выбран
					if(isset($items2materials[$itemId]) && count($items2materials[$itemId])){
						$topMatId = $oMaterials->getFirstLevelId($matId);
						if(!in_array($topMatId, $items2materials[$itemId])){
							// Выбранный материал не предусмотрен для данного товара
							self::remove($itemId, $matId);
							continue;
						}
						if($oMaterials->getCell('has_sub', '`id` = ' . $matId)){
							// Выбранный материал не является конечным материалом
							self::remove($itemId, $matId);
							continue;
						}

					}else{
						// Материал для товара указан, хотя не должен быть
						self::remove($itemId, $matId);
						self::add($itemId, $amount, 0);
						continue;
					}
				}else{
					// Материал не выбран
					if(isset($items2materials[$itemId]) && count($items2materials[$itemId])){
						// Материал для товара не указан, хотя должен быть
						self::remove($itemId, $matId);
						continue;
					}else{
						continue;
					}
				}
			}
		}
	}


	/**
	 * @static
	 * @return	array ($amount, $price, $priceOld, $itemsIds)
	 */
	static function total(){
		if(!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		if(!count($_SESSION[self::SESS_KEY])) return array(0, 0, 0);

		$oMaterials = new Catalog_Materials();

		$totalAmount = 0;
		$totalPrice = 0;
		$totalPriceOld = 0;
		foreach($_SESSION[self::SESS_KEY] as $itemId => $mat2amount){
			foreach($mat2amount as $matId => $amount){
				$d = Dealers_Security::isAuthorized() ? Dealers_Offers::discount4item($itemId, $matId) : false;

				list($priceIn, $priceOut, $priceOld) = $oMaterials->price4item($itemId, $matId, $d);
				$totalPrice += $amount * $priceOut;
				$totalPriceOld += $amount * $priceOld;
				$totalAmount += $amount;
			}
		}

		return array(
			$totalAmount,
			$totalPrice,
			$totalPriceOld,
			array_keys($_SESSION[self::SESS_KEY])
		);
	}


	/** Получаем слово "товаров" в правильном падеже, в зависимости от к-ва товаров
	 * @param	int	$amount
	 * @return	string
	 */
	static function itemsAmountWord($amount = 1){
		$amount = intval($amount);

		if($amount >= 10){
			$amount = intval(substr($amount, strlen($amount) - 2));
		}
		if(10 <= $amount && $amount <= 20){
			return 'товаров';
		}

		$amount = intval(substr($amount, strlen($amount) - 1));
		if(in_array($amount, array(0, 5, 6, 7, 8, 9))){
			return 'товаров';
		}
		if($amount == 1){
			return 'товар';
		}
		if(in_array($amount, array(2, 3, 4))){
			return 'товарa';
		}
		return 'товаров';
	}


	/**
	 * @param $str
	 * @return array
	 */
	static function unpackSetString($str){
		$tmp = explode(';', trim($str));
		$set = array();
		foreach($tmp as &$c){
			if(trim($c) === '') continue;
			$c = explode('-', trim($c));
			$set[$c[0]] = array(
				$c[1]	=> $c[2]
			);
		}
		return $set;
	}


	/**
	 * @param	int	$itemId
	 * @return	bool
	 */
	static function isItemInCart($itemId){
		$itemId = intval($itemId);
		if(!is_array($_SESSION[self::SESS_KEY])) $_SESSION[self::SESS_KEY] = array();
		return isset($_SESSION[self::SESS_KEY][$itemId]);
	}


	/**
	 * @param	array	$itemsIds
	 * @return	bool
	 */
	static function isSetInCart($itemsIds){
		if(!is_array($itemsIds) || !count($itemsIds)) return false;
		$itemsIds = array_map('intval', $itemsIds);

		$res = true;
		foreach($itemsIds as $itemId){
			$res = $res && self::isItemInCart($itemId);
		}
		return $res;
	}
}
