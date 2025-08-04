<?php

/**
 * Класс для формирования карты сайта
 *
 * @author    Seka
 */

class Sitemap
{

    /**
     * Параметры для XML карты сайта
     */
    const XML_HOST = 'mebelioni.ru';
    const PRIORITY_PAGES = 0.5;
    const PRIORITY_ARTICLES = 0.5;
    const PRIORITY_CATEGORIES = 0.9;
    const PRIORITY_SERIES = 0.7;

    /**
     * Куда сохранять XML карту сайта
     * @var string
     */
    protected static $xmlOutput = '/DomainDiff/%domain%/sitemap.xml';

    /**
     * Шаблон для XML карты сайта
     * @var string
     */
    protected static $xmlTpl = '/Skins/html/User/mSitemap.xml';


    /**
     * @param bool     $getCatalogPages Получить в списке ссылок теговые страницы категогрий каталога
     * @param bool|int $domainId
     * @return array ($pages, $articles, $catalog)
     */
    function getLinks($getCatalogPages = false, $domainId = false)
    {
        if (!$domainId) {
            $pages = $this->getLinksPages();

            $getArticles = false;
            foreach ($pages as $p) {
                if ($p['id'] == Articles::ARTICLES_PAGE_ID) {
                    $getArticles = true;
                    break;
                }
            }
            if ($getArticles) {
                $articles = $this->getLinksArticles();
            } else {
                $articles = array();
            }
        } else {
            $pages = array();
            $articles = array();
        }

        $catalog = $this->getLinksCatalog($getCatalogPages, $domainId);

        return array(
            $pages,
            $articles,
            $catalog
        );
    }


    function makeXML()
    {
        $oDomains = new Catalog_Domains();
        $domains = $oDomains->getHash('id, domain');
        $domains[0] = _HOST;
        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
        foreach ($domains as $dId => $d) {
            list($pages, $articles, $catalog) = $this->getLinks(1, $dId);
//			file_put_contents(
//				_ROOT . str_replace('%domain%', $d, self::$xmlOutput),
//				pattExeP(fgc(_ROOT . self::$xmlTpl), array(
//					'pages'		=> $pages,
//					'articles'	=> $articles,
//					'catalog'	=> $catalog
//				))
//			);
            foreach ($pages as $item) {
                $output .= '<url>';
                $output .= '<loc>https://' . $_SERVER['HTTP_HOST'] . $item['url'] . '</loc>';
                $output .= '</url>';
            }
            foreach ($articles as $item) {
                $output .= '<url>';
                $output .= '<loc>https://' . $_SERVER['HTTP_HOST'] . $item['url'] . '</loc>';
                $output .= '</url>';
            }
            foreach ($catalog as $item) {
                $output .= '<url>';
                $output .= '<loc>https://' . $_SERVER['HTTP_HOST'] . $item['url'] . '</loc>';
                $output .= '</url>';
                foreach ($item['series'] as $series) {
                    $output .= '<url>';
                    $output .= '<loc>https://' . $_SERVER['HTTP_HOST'] . $series['url'] . '</loc>';
                    $output .= '</url>';
                }
            }
        }
        $output .= '</urlset>';
        return $output;
    }


    /**
     * @return array
     */
    protected function getLinksPages()
    {
        $oPages = new Pages();
        $pages = $oPages->get(
            'id, name',
            '`in_sitemap` = 1',
            'order'
        );
        foreach ($pages as &$p) {
            $p['url'] = Url::a($p['id']);
        }
        unset($p);
        return $pages;
    }


    /**
     * @return array
     */
    protected function getLinksArticles()
    {
        $oArticles = new Articles();
        $articles = $oArticles->get(
            'id, a_title',
            '`in_sitemap` = 1',
            'order'
        );
        foreach ($articles as &$a) {
            $a['url'] = Articles::a($a['id']);
        }
        unset($a);
        return $articles;
    }


    /**
     * @param bool     $getCatalogPages Получить в списке ссылок теговые страницы категогрий каталога
     * @param bool|int $domainId
     * @return array
     */
    protected function getLinksCatalog($getCatalogPages = false, $domainId = false)
    {
        $oCategories = new Catalog_Categories();

        //$categories = $oCategories->getFloatTree('id, name, domain_id');

        $top = $oCategories->get(
            'id, name, domain_id, has_subcats',
            '`parent_id` = 0' . ($domainId !== false ? ' AND `domain_id` = ' . intval($domainId) : ''),
            'order'
        );
        $categories = array();
        foreach ($top as $ct) {
            $ct['level'] = 1;
            $categories[] = $ct;
            if (intval($ct['has_subcats'])) {
                $sub = $oCategories->getFloatTree('id, name', 1000, $ct['id']);
                foreach ($sub as &$cs) {
                    $cs['domain_id'] = $ct['domain_id'];
                }
                unset($cs);
                $categories = array_merge($categories, $sub);
            }
        }

        $oSeries = new Catalog_Series();
        $oPagesGroups = new Catalog_Pages_Groups();
        $oPages = new Catalog_Pages();
        $oDomains = new Catalog_Domains();

        $domains = $oDomains->getHash('id, domain');

        foreach ($categories as &$c) {
            $c['domain_id'] = intval($c['domain_id']);
            if (!isset($domains[$c['domain_id']])) {
                $c['domain_id'] = 0;
            }

            $c['url'] = Catalog_Categories::a($c['id']);
            $c['url-full'] = 'http://' . _HOST . $c['url'];
            if ($c['domain_id']) {
                $c['url-full'] = str_replace(
                    'http://' . _HOST . '/' . Catalog_Domains::domainCatUrl($c['domain_id']) . '/',
                    'http://' . $domains[$c['domain_id']] . '/',
                    $c['url-full']
                );
            }

            if (!$c['has_subcats']) {
                $c['series'] = $oSeries->get(
                    'id, name, category_id, url',
                    '`category_id` = ' . $c['id'] . ' AND `out_of_production` = 0',
                    'order'
                );
                foreach ($c['series'] as &$s) {
                    $s['url'] = Catalog_Series::a($s);
                    $s['url-full'] = 'http://' . _HOST . $s['url'];
                    if ($c['domain_id']) {
                        $s['url-full'] = str_replace(
                            'http://' . _HOST . '/' . Catalog_Domains::domainCatUrl($c['domain_id']) . '/',
                            'http://' . $domains[$c['domain_id']] . '/',
                            $s['url-full']
                        );
                    }
                }
                unset($s);

                if ($getCatalogPages) {
                    // Теговые страницы
                    $pagesGroups = $oPagesGroups->get(
                        'id, name',
                        '`category_id` = ' . $c['id'],
                        'order'
                    );
                    foreach ($pagesGroups as $n => &$pg) {
                        $pg['pages'] = $oPages->get(
                            'id, name',
                            '`group_id` = ' . $pg['id'],
                            'order'
                        );
                        foreach ($pg['pages'] as &$pp) {
                            $pp['url'] = Catalog_Pages::a($pp['id']);
                            $pp['url-full'] = 'http://' . _HOST . $pp['url'];
                            if ($c['domain_id']) {
                                $pp['url-full'] = str_replace(
                                    'http://' . _HOST . '/' . Catalog_Domains::domainCatUrl($c['domain_id']) . '/',
                                    'http://' . $domains[$c['domain_id']] . '/',
                                    $pp['url-full']
                                );
                            }
                        }
                        unset($pp);
                        if (!count($pg['pages'])) {
                            unset($pagesGroups[$n]);
                        }
                    }
                    unset($pg);
                    $c['pages'] = $pagesGroups;
                }
            }
        }
        unset($c);
        return $categories;
    }
}
