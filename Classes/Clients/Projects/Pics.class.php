<?php

/**
 * Готовые проекты клиентов - схемы и фотографии
 *
 * @author	Seka
 */

class Clients_Projects_Pics extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'clients_projects_pics';

	/**
	 * @var string
	 */
	static $imagePath = '/Projects/';

	/**
	 *
	 */
	const TYPE_PHOTO = 'photo';
	const TYPE_SCHEME = 'scheme';


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
		$this->imageDel($ids);
		return $result;
	}


	/** Получаем 1-е фотографии для заданного массива проектов
	 * @param	array	$projects
	 * @return	array
	 */
	function get1stPhotos($projects = array()){
		if(!is_array($projects) || !count($projects)) return $projects;

		$pIds = array(); $pKeys = array();
		foreach($projects as $pk => &$p){
			$p['id'] = intval($p['id']);
			$pIds[] = $p['id'];
			$pKeys[$p['id']] = $pk;

			$p['1st_photo'] = false;
		}
		unset($p);
		if(!count($pIds)) return $projects;

		// Получаем 1-е фотографии
		$photos = $this->get(
			'`' . self::$tab . '`.*',
			'`project_id` IN (' . implode(',', $pIds) . ')',
			'',
			0,
			'
				JOIN (
					SELECT MIN(`order`) AS `order`
					FROM `' . self::$tab . '`
					WHERE `type` = \'' . self::TYPE_PHOTO . '\'
					GROUP BY `project_id`
				) AS `tmptab` ON (`' . self::$tab . '`.order = `tmptab`.order)
			'
		);
		$photos = $this->imageExtToData($photos);
		foreach($photos as $p){
			$pk = $pKeys[$p['project_id']];
			$projects[$pk]['1st_photo'] = $p;
		}

		return $projects;
	}
}

?>
