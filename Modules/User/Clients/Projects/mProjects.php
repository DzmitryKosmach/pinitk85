<?php

/** Наши проекты
 * @author	Seka
 */

class mProjects {

	/**
	 * К-во на странице
	 */
	const ON_PAGE = 20;

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
    static function main(&$pageInf = array()){
		$oProjects = new Clients_Projects();
		$projectId = $oProjects->detectByUrl();

		// Проект не найден
		if($projectId === false){
			// Проект не найдена
			header('HTTP/1.0 404', true, 404);
			$tpl = Pages::tplFile($pageInf, 'notfound');
			return pattExeP(fgc($tpl), array(
				'pageInf'	=> $pageInf
			));
		}

		if(!$projectId){
			// Список проектов
			list($projects, $toggle, $pgNum) = $oProjects->getByPage(
				intval($_GET['page']),
				self::ON_PAGE,
				'*',
				'',
				'`order` DESC'
			);
			$oPics = new Clients_Projects_Pics();
			$projects = $oPics->get1stPhotos($projects);

			//
			if($pgNum > 1){
				$pageInf['dscr'] = '';
				$pageInf['kwrd'] = '';
			}

			$oPages = new Pages();
			$lettersHeader = $oPages->getCell('header', '`id` = ' . Clients_Letters::LETTERS_PAGE_ID);

			// Выводим страницу
			$tpl = Pages::tplFile($pageInf, 'list');
			return pattExeP(fgc($tpl), array(
				'pageInf'		=> $pageInf,
				'lettersHeader'	=> $lettersHeader,
				'projects'	=> $projects,
				'toggle'	=> $toggle
			));

		}else{
			// Один проект
			$projectInf = $oProjects->getRow(
				'*',
				'`id` = ' . intval($projectId)
			);

			// Фотографии/схемы проекта
			$oPics = new Clients_Projects_Pics();
			$photos = $oPics->imageExtToData($oPics->get(
				'*',
				'`project_id` = ' . intval($projectId) . ' AND `type` = \'' . Clients_Projects_Pics::TYPE_PHOTO . '\'',
				'order'
			));
			$schemes = $oPics->imageExtToData($oPics->get(
				'*',
				'`project_id` = ' . intval($projectId) . ' AND `type` = \'' . Clients_Projects_Pics::TYPE_SCHEME . '\'',
				'order'
			));

			// Параметры страницы
			if($projectInf['title']){
				$pageInf['title'] = $projectInf['title'];
			}
			if($projectInf['dscr']){
				$pageInf['dscr'] = $projectInf['dscr'];
			}
			if($projectInf['kwrd']){
				$pageInf['kwrd'] = $projectInf['kwrd'];
			}

			// Выводим страницу
			$tpl = Pages::tplFile($pageInf, 'view');
			return pattExeP(fgc($tpl), array(
				'pageInf'		=> $pageInf,
				'projectInf'	=> $projectInf,
				'photos'		=> $photos,
				'schemes'		=> $schemes
			));
		}
    }
}

?>