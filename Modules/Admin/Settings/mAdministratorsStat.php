<?php

/** Админка: Администраторы - статистика активности
 * @author	Seka
 */


class mAdministratorsStat extends Admin {

	/**
	 * @var string
	 */
	static $adminMenu = Admin::SETTINGS;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_SETTINGS;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oAdministrators = new Administrators();

		$adminId = intval($_GET['aid']);

		$currentAdmin = $oAdministrators->info();
		if($currentAdmin['id'] != 1 && $adminId != $currentAdmin['id']){
			Pages::flash('Вы можете просматривать активность только своей учётной записи администратора.', true);
		}

		$admin = $oAdministrators->getRow('*', '`id` = ' . $adminId);
		if($admin === false){
			Pages::flash('Запрошенная учётная запись администратора не найдена', true);
		}

		$oStat = new Administrators_Stat();
		$stat = $oStat->get(
			'*',
			'`administrator_id` = ' . $adminId,
			'`time_start` DESC'
		);

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'admin'	=> $admin,
			'stat'	=> $stat
		));
	}
}

?>