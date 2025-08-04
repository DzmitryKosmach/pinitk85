<?php

/**
 * Материалы мебели
 *
 * @author	Seka
 */

class Catalog_Materials extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_materials';

	/**
	 * @var string
	 */
	static $imagePath = '/Material/';

	/**
	 * Макс. уровень вложенности материалов
	 * 3 - есть материалы, под-материалы и под-под-материалы
	 */
	const MAX_DEEP_LEVEL = 3;

    public function __construct()
    {
        self::setTable(self::$tab);
    }

	/** Переопределим метод для присвоения order
	 * @see	DbList::addArr()
	 * @param	array	$data
	 * @param	string	$method
	 * @return	int
	 */
	function addArr($data = array(), $method = self::INSERT){
		$res = parent::addArr($data, $method);
		$this->setOrderValue();
		return $res;
	}

	/**
	 * @see	DbList::delCond()
	 * @param string $cond
	 * @return bool
	 */
	function delCond($cond = ''){
		$ids = $this->getCol('id', $cond);
		$result = parent::delCond($cond);

		if(count($ids)){
			// Удаляем зависимые данные
			$this->delCond('`parent_id` IN (' . implode(',', $ids) . ')');

			$oSeries2Materials = new Catalog_Series_2Materials();
			$oSeries2Materials->delCond('`material_id` IN (' . implode(',', $ids) . ')');

			$oItems2Materials = new Catalog_Items_2Materials();
			$oItems2Materials->delCond('`material_id` IN (' . implode(',', $ids) . ')');
		}

		$this->imageDel($ids);
		return $result;
	}


	/** Получаем уровень вложенности материала
	 * 1 - материал верхнего уровня
	 * @param	int	$matId
	 * @return	int
	 */
	function getDeepLevel($matId){
		$matId = intval($matId);
		$level = 0;
		while($matId){
			$level++;
			$matId = $this->getCell('parent_id', '`id` = ' . $matId);
			if($matId === false) return 0;
			$matId = intval($matId);
		}
		return $level;
	}


	/** Получаем ID материала верхнего уровня, кот. является родителем для заданного материала
	 * @param	int	$matId
	 * @return	int
	 */
	function getFirstLevelId($matId){
		static $cache = array();

		$matId = intval($matId);
		if(isset($cache[$matId])) return $cache[$matId];

		$firstId = $matId;
		$parentId = $this->getCell('parent_id', '`id` = ' . $matId);
		if($parentId){
			$firstId = $this->getFirstLevelId($parentId);
		}
		$cache[$matId] = $firstId;
		return $firstId;
	}


	/**
	* @static
 	* @param	int	$matId
 	* @return	array	Массив названий материалов от верхнего уровня до заданного
 	*/
	static function getFullName($matId){
		static $o; if(!$o) $o = new self();
		static $cache = array();

		$matId = intval($matId);
		if(isset($cache[$matId])) return $cache[$matId];

		$m = $o->getRow('name, parent_id', '`id` = ' . $matId);
		$name = array($m['name']);
		if($m['parent_id']){
			$name = array_merge($o->getFullName($m['parent_id']), $name);
		}

		$cache[$matId] = $name;
		return $name;
	}


	/** Входная и ВЫходная цена в руб. для заданного товара с заданным материалом
	 * @param	int		$itemId
	 * @param	int		$matId
	 * @param	bool|float	$dealerDiscount
	 * @return	array	($priceIn, $priceOut, $priceOld)
	 */
	function price4item($itemId, $matId, $dealerDiscount = false){
		static $items = array();
		static $items2materials = array();

		$itemId = intval($itemId);
		$matId = intval($matId);

		if(!isset($items[$itemId])){
			$oItems = new Catalog_Items();
			$item = $oItems->getRow(
				'id, series_id, currency, price, price_min_material_id, extra_charge, discount',
				'`id` = ' . $itemId
			);
			if(!$item){
				return array(0, 0, 0);
			}
			$items[$itemId] = $item;
		}
		$item = $items[$itemId];
		$usdCourse = Catalog_Series::usdCourse($item['series_id']);

		if(!isset($items2materials[$itemId])){
			$oItems2Materials = new Catalog_Items_2Materials();
			$items2materials[$itemId] = $oItems2Materials->getWhtKeys(
				'material_id, currency, price',
				'`item_id` = ' . $itemId,
				'', 0, '', '',
				'material_id'
			);
		}

		if(!$matId){
			$matId = $item['price_min_material_id'];
		}
		if($matId){
			$matId = $this->getFirstLevelId($matId);
			if(!isset($items2materials[$itemId][$matId])){
				return array(0, 0, 0);
			}
			$p = $items2materials[$itemId][$matId];
			if(!round($p['price'], Catalog::PRICES_DECIMAL)){
				$p = $item;
			}
		}else{
			$p = $item;
		}

		$priceIn = round(
			$p['currency'] == Catalog::RUB ? ($p['price']) : ($p['price'] * $usdCourse),
			Catalog::PRICES_DECIMAL
		);

		$discount = abs($dealerDiscount ? $dealerDiscount : $item['discount']);

		if($discount != 1){
			$priceOld = $priceIn * $item['extra_charge'];
		}else{
			$priceOld = 0;
		}

		$priceOut = $priceIn * $item['extra_charge'] * $discount;

		return array($priceIn, $priceOut, $priceOld);
	}


	/** Получаем дерево материалов для заданной серии
	 * Вызывать метод нужно только с 1-м аргументом, второй используется для рекурсии
	 * Метод также получает картинки для всех материалов:
	 * Если у материала нет своей картинки, берётся картинка 1-го подварианта.
	 * Картинка в любом случае записывается в поле ['image'] => array('id'	=> ..., 'ext' => ['_img_ext'])
	 * @param	array|int	$seriesId	Можно передать массив ID серий
	 * @param	int			$parentId
	 * @return	array
	 */
	function getTree($seriesId, $parentId = 0){
		$seriesId = is_array($seriesId) ? array_map('intval', $seriesId) : intval($seriesId);
		$parentId = intval($parentId);

		if((is_array($seriesId) && count($seriesId)) || $seriesId !== 0){
			if(!is_array($seriesId)) $seriesId = array($seriesId);
			$oSeries2Materials = new Catalog_Series_2Materials();
			$mIds = $oSeries2Materials->getCol(
				'material_id',
				'`series_id` IN (' . implode(',', $seriesId) . ')'
			);
			if(count($mIds)){
				$materials = $this->imageExtToData($this->getWhtKeys(
					'*',
					'`id` IN (' . implode(',', $mIds) . ')',
					'order'
				));
			}else{
				$materials = array();
			}
		}else{
			$materials = $this->imageExtToData($this->getWhtKeys(
				'*',
				'`parent_id` = ' . $parentId,
				'order'
			));
		}

		foreach($materials as &$m){
			if($m['has_sub']){
				$m['sub'] = $this->getTree(0, $m['id']);
				if($m['_img_ext'] || !count($m['sub'])){
					$m['image'] = array(
						'id'	=> $m['id'],
						'ext'	=> $m['_img_ext']
					);
				}else{
                    $e = array_keys($m['sub']);
					$m['image'] = $m['sub'][array_shift($e)]['image'];
				}
			}else{
				$m['image'] = array(
					'id'	=> $m['id'],
					'ext'	=> $m['_img_ext']
				);
			}
		}
		unset($m);

		return $materials;
	}
}
