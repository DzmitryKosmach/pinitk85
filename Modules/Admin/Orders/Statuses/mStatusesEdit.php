<?php

/** Админка: добавление / редактирование статуса заказов
 * @author	Seka
 */


class mStatusesEdit extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::ORDERS;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_ORDERS;

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
		$o = new self();
		$o->checkRights();

		$oStatuses = new Orders_Statuses();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование
			$init = $oStatuses->getRow(
				'*',
				'`id` = ' . self::$pId
			);
			if($init === false){
				Pages::flash('Запрошенный для редактирования статус заказов не найден.', true, Url::a('admin-orders-statuses'));
			}

		}else{
			// Добавление
			$init = array();
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'		=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mStatusesEdit::save');
	}


	/**
	 * @param $initData
	 * @param $newData
	 */
	static function save($initData, $newData){
		$save = array(
			'name'	=> $newData['name'],
			'color'	=> $newData['color']
		);

		$oStatuses = new Orders_Statuses();

		if(self::$pId){
			// Редактирование
			$oStatuses->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oStatuses->add($save);

			$msg = 'Статус заказов успешно добавлен.';
		}

		Pages::flash($msg, false, Url::a('admin-orders-statuses'));
	}
}

?>