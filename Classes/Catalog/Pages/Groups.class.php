<?php

/**
 * Группы теговых страниц для категорий
 * Теговая страница - страница со списком товаров категории, подходящая под заданную комбинацию фильтров и имеющую свои метатеги и заголовки
 *
 * @author	Seka
 */

class Catalog_Pages_Groups extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'catalog_pages_groups';

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
			$oPages = new Catalog_Pages();
			$oPages->delCond('`group_id` IN (' . implode(',', $ids) . ')');
		}

		return $result;
	}
}
