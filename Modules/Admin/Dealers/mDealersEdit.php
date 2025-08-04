<?php

/** Админка: добавление / редактирование дилера
 * @author	Seka
 */

class mDealersEdit extends Admin {

	/**
	 * @var int
	 */
	static $adminMenu = Admin::DEALERS;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_DEALERS;

	/**
	 * @var int
	 */
	static $pId;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){

		$oDealers = new Dealers();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oDealers->getRow('*', '`id` = ' . self::$pId);
			if($init === false){
				Pages::flash('Дилер для редактирования не найден.', true, '/Modules/Admin/Dealers/mDealers.php');
			}

			// Наценки по поставщикам
			$init['extra'] = Dealers_Extra::getForDealer(self::$pId);

		}else{
			// Добавление
			$init = array(
				'extra'	=> array()
			);
		}

		// Поставщики
		$oSuppliers = new Catalog_Suppliers();
		$suppliers = $oSuppliers->get(
			'id, name',
			'',
			'`name` ASC'
		);

		// Переводим все наценки в %
		foreach($init['extra'] as &$e){
			$e = Catalog::num2percent($e, Catalog::PC_INCREASE);
		}
		unset($e);

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init,
			'suppliers'	=> $suppliers
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mDealersEdit::save', 'mDealersEdit::check');
	}


	/**
	 * @param $initData
	 * @return array
	 */
	static function check($initData){

		// Проверяем свободность логина и емэйла
		$oDealers = new Dealers();
		$check = $oDealers->newAccCheck($_POST, self::$pId);

		if($check == Dealers::CHECK_ERR_LOGIN) return array(array(
			'name'	=> 'login',
			'msg'	=> 'Указанный логин уже занят другим дилером! Используйте другой логин.'
		));
		if($check == Dealers::CHECK_ERR_EMAIL) return array(array(
			'name'	=> 'email',
			'msg'	=> 'Указанный E-mail уже используется для другого аккаунта!'
		));

		return true;
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$oDealers = new Dealers();

		$save = array(
			'name'			=> $newData['name'],
			'login'			=> $newData['login'],
			'pass'			=> $newData['pass'],
			'email'			=> $newData['email'],
			'notes'			=> $newData['notes'],
			'show_in_price'=> intval($newData['show_in_price']) ? 1 : 0,
			'status'		=> $newData['status']
		);

		if(self::$pId){
			// Редактирование
			$oDealers->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';
		}else{
			// Добавление
			$save['regdate'] = MySQL::dateTime();

			self::$pId = $oDealers->add($save);

			$msg = 'Дилер успешно добавлен.';
		}

		// Сохраняем наценки на отображаемую входную цену
		$oExtra = new Dealers_Extra();
		$oExtra->delCond('`dealer_id` = ' . self::$pId);
		if(intval($newData['show_in_price'])){
			$extra = $newData['extra'];
			foreach($extra as $sId => &$e){
				$e = Catalog::percent2num($e, Catalog::PC_INCREASE);
				if($e == 1){
					unset($extra[$sId]);
				}else{
					$e = array(
						'dealer_id'		=> self::$pId,
						'supplier_id'	=> $sId,
						'extra'			=> $e
					);
				}
			}
			unset($e);
			if(count($extra)){
				$oExtra->addArr($extra);
			}
		}

		Pages::flash($msg, false, Url::a('admin-dealers'));
	}
}

?>