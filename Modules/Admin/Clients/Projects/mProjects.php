<?php

/** Админка: Наши проекты
 * @author  Seka
 */

class mProjects extends Admin {

	/**
	 * @var int
	 */
	static $adminMenu = Admin::CLIENTS;

	/**
	 * @var string
	 */
	var $mainClass = 'Clients_Projects';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CLIENTS;


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();
		$o->getOperations();

		// Получаем список
		$oProjects = new Clients_Projects();
		$projects = $oProjects->get(
			'*',
			'',
			'`order` DESC'
		);

		$oPics = new Clients_Projects_Pics();
		$projects = $oPics->get1stPhotos($projects);

		// Для каждого проекта получаем к-во картинок
		$pIds = array();
		foreach($projects as $p) $pIds[] = $p['id'];
		if(count($pIds)){
			// Картинки
			$imagesCnt = $oPics->getHash(
				'project_id, COUNT(*)',
				'`project_id` IN (' . implode(',', $pIds) . ')',
				'',
				0,
				'',
				'project_id'
			);

			foreach($projects as &$p){
				$p['images_cnt'] = isset($imagesCnt[$p['id']]) ? intval($imagesCnt[$p['id']]) : 0;
			}
			unset($p);
		}

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'projects'	=> $projects
		));
	}


	/** Удаление
	 * @param $iId
	 */
	function delItem($iId){
		$oProjects = new Clients_Projects();
		$oProjects->del(intval($iId));
		Pages::flash('Проект успешно удалён.');
	}
}

?>