<?php

/**
 * Статусы заказов
 *
 * @author	Seka
 */

class Orders_Statuses extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'orders_statuses';

	/**
	 * @var array
	 */
	static $default = array(
		'id'	=> 0,
		'name'	=> 'Заказ оформлен',
		'color'	=> '#000'
	);

	/**
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
}

?>
