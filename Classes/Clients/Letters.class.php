<?php

/**
 * Письма клиентов
 *
 * @author	Seka
 */

class Clients_Letters extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'clients_letters';

	/**
	 * @var string
	 */
	static $imagePath = '/Letters/';

	/**
	 *
	 */
	const LETTERS_PAGE_ID = 945;


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
}

?>
