<?php

/** Новости
 * @author	Seka
 */

class mNews {

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
		$oNews = new News();
		$newsId = $oNews->detectByUrl();

		// Новость не найдена
		if($newsId === false){
			// Новость не найдена
			header('HTTP/1.0 404', true, 404);
			$tpl = Pages::tplFile($pageInf, 'notfound');
			return pattExeP(fgc($tpl), array(
				'pageInf'	=> $pageInf
			));
		}

		if(!$newsId){
			// Список новостей
			list($news, $toggle, $pgNum) = $oNews->getByPage(
				intval($_GET['page']),
				self::ON_PAGE,
				'*',
				'`date` < NOW( ) ',
				'`date` DESC, `id` DESC'
			);
			$news = $oNews->imageExtToData($news);

			//
			if($pgNum > 1){
				$pageInf['dscr'] = '';
				$pageInf['kwrd'] = '';
			}

			// Выводим страницу
			$tpl = Pages::tplFile($pageInf, 'list');
			return pattExeP(fgc($tpl), array(
				'pageInf'	=> $pageInf,
				'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
				'news'		=> $news,
				'toggle'	=> $toggle
			));

		}else{
			// Одна новость
			$newsInf = $oNews->imageExtToData($oNews->getRow(
				'*',
				'`id` = ' . intval($newsId)
			));

			// Параметры страницы
			$pageInf['header'] = $newsInf['n_title'];

			if($newsInf['title']){
				$pageInf['title'] = $newsInf['title'];
			}
			if($newsInf['dscr']){
				$pageInf['dscr'] = $newsInf['dscr'];
			}
			if($newsInf['kwrd']){
				$pageInf['kwrd'] = $newsInf['kwrd'];
			}

			// Выводим страницу
			$tpl = Pages::tplFile($pageInf, 'view');
			return pattExeP(fgc($tpl), array(
				'pageInf'	=> $pageInf,
				'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
				'newsInf'	=> $newsInf
			));
		}
    }
}

?>