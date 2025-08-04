<?php

/** Благодарственные письма
 * @author	Seka
 */

class mLetters {

	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
    static function main(&$pageInf = array()){
		$oLetters = new Clients_Letters();

		// Получаем список
		$letters = $oLetters->imageExtToData($oLetters->get(
			'*',
			'',
			'`order` DESC'
		));

		$oPages = new Pages();
		$projectsHeader = $oPages->getCell('header', '`id` = ' . Clients_Projects::PROJECTS_PAGE_ID);

			// Выводим страницу
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'		=> $pageInf,
			'projectsHeader'=> $projectsHeader,
			'letters'	=> $letters
		));
    }
}

?>