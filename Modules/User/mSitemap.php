<?php

/** Карта сайта
 * @author	Seka
 */

class mSitemap {


	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){

		$oSitemap = new Sitemap();
		list($pages, $articles, $catalog) = $oSitemap->getLinks();

		// Выводим шаблон
		$tpl = Pages::tplFile($pageInf);
		return pattExeP(fgc($tpl), array(
			'pageInf'	=> $pageInf,
			'breadcrumbs'	=> BreadCrumbs::forPage(intval($pageInf['id'])),
			'pages'		=> $pages,
			'articles'	=> $articles,
			'catalog'	=> $catalog
		));
	}
}

?>