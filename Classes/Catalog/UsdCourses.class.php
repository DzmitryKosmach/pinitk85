<?php

/**
 * Курсы валют
 *
 * @author	Seka
 */

class Catalog_UsdCourses extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_usdcourses';


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
			$oSeries = new Catalog_Series();
			$oSeries->updCond(
				'`usd_course_id` IN (' . implode(',', $ids) . ')',
				array(
					'usd_course_id'	=> 0
				)
			);
		}

		return $result;
	}


	/** При изменении значения курса это значение автоматически передаётся в параметры всех серий, связанных с данным курсом
	 * @param	string	$cond
	 * @param	array	$updArr
	 */
	function updCond($cond = '', $updArr = array()){
		$id2courseOld = $this->getHash('id, course', $cond);

		parent::updCond($cond, $updArr);

		$id2courseNew = $this->getHash('id, course', $cond);

		$oSeries = new Catalog_Series();
		foreach($id2courseOld as $id => $courseOld){
			if(!isset($id2courseNew[$id])) continue;
			$courseNew = $id2courseNew[$id];
			if(abs($courseOld) !== abs($courseNew)){
				$oSeries->updCond(
					'`usd_course_id` = ' . $id,
					array(
						'usd_course'	=> $courseNew
					)
				);
			}
		}
	}
}

?>
