<?php

/**
 * Автоматическая перелинковка серий
 *
 * @author	Seka
 */

class Catalog_Series_Linkage_Auto extends Catalog_Series_Linkage_2Series {

	/**
	 * Генерация списка URL'ов по заданным параметрам для автозаполнения списка доноров и акцепторов
	 * @param	int		$categoryId
	 * @param	bool	$priceGrouping
	 * @param	int		$priceGroupingCnt	К-во серий в каждой группе
	 * @return	string
	 */
	function autoFillForm($categoryId, $priceGrouping, $priceGroupingCnt){
		$categoryId = intval($categoryId);
		$priceGrouping = (bool)$priceGrouping;
		$priceGroupingCnt = abs(intval($priceGroupingCnt)); if($priceGroupingCnt < 1) $priceStep = 1;

		$oSeries = new Catalog_Series();
		$urls = array();

		if($priceGrouping){
			// Разделяем серии на равные группы, предвариетльно отсортировав их по ценам
			$series = $oSeries->get(
				'id, category_id, url, IF((price_search_min OR price_search_max), price_search_min, price_min) AS `price`',
				'`category_id` = ' . $categoryId . ' AND `out_of_production` = 0',
				'IF(`price_search_min` OR `price_search_max`, `price_search_min`, `price_min`) ASC, `order` ASC'
			);

			$groups = array();
			$group = array();
			foreach($series as $sn => $s){
				$group[] = $s;
				if(count($group) == $priceGroupingCnt || $sn == count($series)-1){
					$groups[] = $group;
					$group = array();
				}
			}

			foreach($groups as $gn => $group){
				$prices = array();
				foreach($group as $s){
					$prices[] = $s['price'];
				}
				$pMin = round(min($prices));
				$pMax = round(max($prices));

				$urls[] = '------ Группа ' . ($gn+1) . ': от ' . Catalog::priceFormat($pMin) . ' до ' . Catalog::priceFormat($pMax) . ' руб. ------';
				foreach($group as $s){
					$urls[] = 'http://' . _HOST . Catalog_Series::a($s);
				}
				//$urls[] = '';
			}

		}else{
			// Получаем все серии категории
			$series = $oSeries->get(
				'id, category_id, url',
				'`category_id` = ' . $categoryId,
				'order'
			);
			foreach($series as $s){
				$urls[] = 'http://' . _HOST . Catalog_Series::a($s);
			}
		}
		return implode("\r\n", $urls);
	}


	/**
	 * Генерация линковок
	 * @param	int		$linkageId
	 * @param	int		$maxSeriesCnt
	 * @param	string	$donors
	 * @param 	string	$acceptors
	 * @return	int
	 */
	function generate($linkageId, $maxSeriesCnt, $donors, $acceptors){
		$linkageId = intval($linkageId);
		$maxSeriesCnt = abs(intval($maxSeriesCnt)); if(!$maxSeriesCnt) $maxSeriesCnt = 1;

		// Проверяем, существует ли заданный блок линковок
		$oLinkage = new Catalog_Series_Linkage();
		if(!$oLinkage->getCount('`id` = ' . $linkageId)) return 0;

		// Парсим списки доноров/акцепторов
		$donorsGroups = $this->parseUrlsList($donors);
		$acceptorsGroups = $this->parseUrlsList($acceptors);

		// Генерируем линковке для каждой группы доноров/акцепторов
		$linksCnt = 0;
		$groupsCnt = min(count($donorsGroups), count($acceptorsGroups));
		for($groupN = 0; $groupN < $groupsCnt; $groupN++){
			$linksCnt += $this->generateByIds(
				$linkageId,
				$maxSeriesCnt,
				$donorsGroups[$groupN],
				$acceptorsGroups[$groupN]
			);
		}
		return $linksCnt;
	}


	/**
	 * Из списка ссылок на страницы серий (каждая ссылка с новой строки) получаем массивы групп ID серий
	 * Ссылки разделяются на группы строкой, начинающейся с "--"
	 * @param	string	$urls
	 * @return	array
	 */
	protected function parseUrlsList($urls){
		$urls = "\n".trim($urls);
		$urls = str_replace("\n--", "\n----", $urls);
		$groups = array_map('trim', explode("\n--", $urls));

		foreach($groups as $gn => &$g){
			if($g === ''){
				unset($groups[$gn]);
				continue;
			}

			$urls = array_map('trim', explode("\n", $g));
			foreach($urls as $un => $u){
				if(strpos($u, '--') === 0) unset($urls[$un]);
			}
			if(count($urls) < 2){
				unset($groups[$gn]);
				continue;
			}

			foreach($urls as $un => &$u){
				list($catId, $seriesId) = Catalog::detectByUrl($u, false);
				if($seriesId){
					$u = $seriesId;
				}else{
					unset($urls[$un]);
				}
			}
			unset($u);

			$g = array_values(array_unique($urls));
		}
		unset($g);

		return array_values($groups);
	}


	/**
	 * Генерация линковок для одной группы доноров и акцепторов (передаются в виде массивов ID серий)
	 * @param	int		$linkageId
	 * @param	int		$maxSeriesCnt
	 * @param	array	$donorsIds
	 * @param	array	$acceptorsIds
	 * @return	int
	 */
	protected function generateByIds($linkageId, $maxSeriesCnt, $donorsIds, $acceptorsIds){
		$linkageId = intval($linkageId);
		$maxSeriesCnt = abs(intval($maxSeriesCnt)); if(!$maxSeriesCnt) $maxSeriesCnt = 1;

		if(!count($donorsIds)) return 0;
		$donorsIds = array_map('intval', $donorsIds);

		// Удаляем прежние линковки для доноров
		$this->delCond('`linkage_id` = ' . $linkageId . ' AND `series1_id` IN (' . implode(',', $donorsIds) . ')');

		if(!count($acceptorsIds)) return 0;

		// Массив ID акцепторов преобразуем так, чтобы ID стали ключами, а в значения будем записывать,
		// сколько раз акцептор был использован (для равномерного распределения)
		$acceptorsIds = array_fill_keys(array_map('intval', $acceptorsIds), 0);

		// Генерируем ссылки для каждого донора
		$linksCnt = 0;
		foreach($donorsIds as $dN => $dId){
			$dId = intval($dId);

			$aIds = array();
			if(count($acceptorsIds) <= $maxSeriesCnt || (isset($acceptorsIds[$dId]) && count($acceptorsIds)-1 <= $maxSeriesCnt)){
				// К-во акцепторов (за вычетом ID донора, если он среди них) не более макс. к-ва ссылок в доноре
				$aIds = $acceptorsIds;
				if(isset($aIds[$dId])) unset($aIds[$dId]);
				$aIds = array_keys($aIds);
				shuffle($aIds);

				foreach($aIds as $aId){
					$acceptorsIds[$aId]++;	// Отмечаем, что акцептор был использован
				}
			}else{
				// Выбираем акцепторов случайным образом
				while(count($aIds) < $maxSeriesCnt){
					$aId = $this->findRandomKeyWithMinValue($acceptorsIds, $dId);
					$acceptorsIds[$aId]++;	// Отмечаем, что акцептор был использован
					$aIds[] = $aId;
				}
			}

			$links = array();
			foreach($aIds as $aId){
				$links[] = array(
					'linkage_id'	=> $linkageId,
					'series1_id'	=> $dId,
					'series2_id'	=> $aId
				);
			}
			$linksCnt += count($links);

			$this->addArr($links);
		}
		return $linksCnt;
	}


	/**
	 * @param	array	$array		Требуется хеш (key => value)
	 * @param	int		$exceptKey
	 * @return	int|bool
	 */
	protected function findRandomKeyWithMinValue($array, $exceptKey = 0){
		if(!count($array)) return false;
		$exceptKey = intval($exceptKey);

		$minVal = min($array);
		foreach($array as $k => $v){
			if($v !== $minVal || $k === $exceptKey){
				unset($array[$k]);
			}
		}
		return array_rand($array);
	}
}

?>