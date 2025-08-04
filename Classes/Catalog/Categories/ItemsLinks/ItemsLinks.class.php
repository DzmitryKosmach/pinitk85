<?php

/**
 * Связь категорий с товарами
 * Таблица содержит данные о том, какие товары (не серии) нужно отображать наряду со списком серий на
 * стр. категории или на теговых страницах
 * (на самом деле, не наряду со списком серий, а вместо серий)
 *
 * Поля category_id и page_id определяют, где будут отображаться товары
 * А какие будут отображаться товары, определяется одним из двух способов:
 * 1. Указанием серии и группы товароы (series_id и group_id)
 * 2. Прямой связью с товаром (item_id)
 *
 * todo: вот это везде закомменттировано:
 * Можно исключить конкретный товар со всех страниц категорий и теговых, путём установки соотв. флага в товаре
 *
 * todo: вот это пока не работает:
 * И можно исключить товар с определённых страниц (т.е. из определённых связок категория-серия-группа), для этого
 * используется класс Catalog_Categories_ItemsLinks_Selection
 *
 * @see	Catalog_Categories_ItemsLinks_Selection
 * @author	Seka
 */

class Catalog_Categories_ItemsLinks extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_categories_items_links';


	/**
	 * @see	DbList::delCond()
	 * @param string $cond
	 * @return bool
	 */
	function delCond($cond = ''){
		$ids = $this->getCol('id', $cond);

		// Если удаляются линки товаров в категориями, то надо удалить связи этих же товаров с теговыми страницами в этих категориях
		$data = $this->get('*', $cond);
		foreach($data as $d){
			if(!intval($d['page_id'])){
				$this->delCond('
					`category_id` = ' . $d['category_id'] . ' AND
					`series_id` = ' . $d['series_id'] . ' AND
					`group_id` = ' . $d['group_id'] . ' AND
					`item_id` = ' . $d['item_id'] . ' AND
					`page_id` != 0
				');
			}
		}

		$result = parent::delCond($cond);

		if(count($ids)){
			// Удаляем зависимые данные
			/*$oItemsLinksSel = new Catalog_Categories_ItemsLinks_Selection();
			$oItemsLinksSel->delCond('`link_id` IN (' . implode(',', $ids) . ')');*/
		}

		return $result;
	}


	/**
	 * При добавлении линка на теговую страницу, нужно создать такой же линк для страницы самой категории
	 * @param array $data
	 * @param string $method
	 * @return int
	 */
	function addArr($data = array(), $method = self::INSERT){
		$ids = array();
		foreach($data as $d){
			$ids[] = parent::addArr(array($d), $method);
		}

		if(count($ids) && implode(',', $ids)){
			$inserted = $this->get(
				'*',
				'`id` IN (' . implode(',', $ids) . ') AND `page_id` != 0'
			);
			foreach($inserted as $d){
				if(!intval($this->getCount('
					`category_id` = ' . $d['category_id'] . ' AND
					`page_id` = 0 AND
					`series_id` = ' . $d['series_id'] . ' AND
					`group_id` = ' . $d['group_id'] . ' AND
					`item_id` = ' . $d['item_id']
				))){
					$this->add(array(
						'category_id'	=> $d['category_id'],
						'page_id'		=> 0,
						'series_id'		=> $d['series_id'],
						'group_id'		=> $d['group_id'],
						'item_id'		=> $d['item_id']
					));
				}
			}
		}

		return array_pop($ids);
	}


	/**
	 * @param	int	$catId
	 * @param	int	$pageId
	 * @return	array
	 */
	function getItemsIds($catId, $pageId = 0){
		$catId = intval($catId);
		$pageId = intval($pageId);

		$cond = array();

		$itemsIds = array_unique($this->getCol(
			'item_id',
			'`category_id` = ' . $catId . ' AND `page_id` = ' . $pageId . ' AND `item_id` != 0'
		));
		if(count($itemsIds)){
			$cond[] = '`id` IN (' . implode(',', $itemsIds) . ')';
		}

		$seriesAndGroups = $this->get(
			'series_id, group_id',
			'`category_id` = ' . $catId . ' AND `page_id` = ' . $pageId . ' AND `item_id` = 0'
		);
		if(count($seriesAndGroups)){
			foreach($seriesAndGroups as $sg){
				$cond[] = '(`series_id` = ' . $sg['series_id'] . ' AND `group_id` = ' . $sg['group_id'] . ')';
			}
		}

		if(!count($cond)){
			return array();
		}

		$oItems = new Catalog_Items();
		return $oItems->getCol(
			'id',
			'(' . implode(' OR ', $cond) . ')'/* AND `items_links_exclude` = 0'*/
		);
	}
}
