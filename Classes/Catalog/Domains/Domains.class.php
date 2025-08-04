<?php

/**
 * Управление доменами и поддоменами
 * Некоторые категории каталога могут располагаться на отдельных поддоменах,
 * соответственно нужны все необходимые редиректы и подстановки в URL'ах
 *
 * @author    Seka
 */

class Catalog_Domains extends ExtDbList
{

    /**
     * @var string
     */
    static string $tab = 'catalog_domains';

    /**
     * URL'ы (алиасы), доступные несмотря ни на что на всех доменах/поддоменах
     * @var array
     */
    static array $crossDomainsUrls = [
        'images-cache',
        'upload-images'
    ];

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /**
     * @param string $cond
     * @return bool
     * @see    DbList::delCond()
     */
    public function delCond($cond = ''): bool
    {
        $ids = $this->getCol('id', $cond);
        $result = parent::delCond($cond);

        if (count($ids)) {
            // Удаляем зависимые данные
            $oCategories = new Catalog_Categories();
            $oCategories->updCond(
                '`domain_id` IN (' . implode(',', $ids) . ')',
                array(
                    'domain_id' => 0
                )
            );
        }

        return $result;
    }


    /**
     * Метод должен вызываться из метода Url::parse() до осуществения парсинга (т.к. анализируемый $url может измениться)
     * Результатом выполнения метода является одно из двух:
     * - редирект на другой домен/поддомен;
     * - корректировка $url, переданного по ссылке, влияющая на дальнейший парсинг URL'а
     * @param string $url
     * @see    Url::parse()
     */
    public function checkRedirect(string $url): void
    {
        $domainId = $this->currentId();
        $url = trim($url);
        $urlParts = parse_url(trim($url));

        $e = explode('/', trim($urlParts['path'], '/'));
        $url1stWord = array_shift($e);

        $oCategories = new Catalog_Categories();
        $oSeries = new Catalog_Series();

        if (!$domainId) {
            // Мы сейчас на главном домене
            if ($url1stWord === '') {
                // Корень сайта
                return;
            } else {

                $oCategories->setTab($oCategories::getTab());
                $catDomainId = $oCategories
                    ->getCell(
                    'domain_id',
                    '`parent_id` = 0 AND `url` = \'' . MySQL::mres($url1stWord) . '\''
                );

                if ($catDomainId !== false) {
                    // Категория каталога
                    $catDomainId = intval($catDomainId);
                    if ($catDomainId) {
                        // Категория связана с поддоменом
                        // REDIRECT
                        $this->redirectTo($url, $catDomainId);
                    } else {
                        // Категория не связана с поддоменом
                        return;
                    }
                } else {
                    // Не категория каталога (какая-то другая страница)
                    return;
                }
            }
        } else {
            // Мы сейчас на поддомене
            $domainCatId = intval(
                $oCategories->getCell(
                    'id',
                    '`domain_id` = ' . $domainId . ' AND `parent_id` = 0'
                )
            );
            if ($domainCatId) {
                // С текущим поддоменом связана категория
                if ($url1stWord === '') {
                    // Мы в корне поддомена, нужно отобразить содержимое категории
                    // CORRECT
                    $url = $this->correctUrlForParsing($url);
                    return;
                } else {
                    // Ищем страницу, относящуюся к этой категории
                    if (
                        $oCategories->getCount(
                            '`parent_id` = ' . $domainCatId . ' AND `url` = \'' . MySQL::mres($url1stWord) . '\''
                        )
                        ||
                        $oSeries->getCount(
                            '`category_id` = ' . $domainCatId . ' AND `url` = \'' . MySQL::mres($url1stWord) . '\''
                        )
                    ) {
                        // Мы находимся в подкатегории (или серии) категории, связанной с текущим доменом
                        // CORRECT
                        $url = $this->correctUrlForParsing($url);
                        return;
                    } else {
                        // Текущая страница не является подкатегорией или серией, относящейся к категории данного поддомена
                        if (!intval($_GET['no-domain-redirect'])) {
                            static $noRedirectUrls;
                            if (!$noRedirectUrls) {
                                $noRedirectUrls = array();
                                foreach (self::$crossDomainsUrls as $alias) {
                                    $noRedirectUrls[] = Url::a($alias);
                                }
                            }
                            $redirect = true;
                            foreach ($noRedirectUrls as $u) {
                                if (strpos($url, $u) === 0) {
                                    $redirect = false;
                                }
                            }
                            if ($redirect) {
                                // REDIRECT
                                $this->redirectTo($url, 0);
                            }
                        }
                        return;
                    }
                }
            } else {
                // Категории, связанной с текущим поддоменом, не существует
                // REDIRECT
                $this->redirectTo($url, 0);
            }
        }
    }


    /**
     * Когда мы находимся на поддомене, перед парсингом URL'а к нему слева нужно приписать URL категории, относящейся к этому поддомену
     * Было:  /series/...
     * Стало: /category/series/...
     * Важно перед вызовом убедиться, что входной URL не содержит /category/
     * @param string $url
     * @return    string
     */
    function correctUrlForParsing($url)
    {
        $domainCatUrl = $this->currentDomainCatUrl();
        if ($domainCatUrl !== '') {
            $url = '/' . $domainCatUrl . $url;
        }
        return $url;
    }


    /**
     * Когда мы находимся на поддомене, при генерации URL'а для его вывода в html-коде, из него необходимо вырезать начальную часть,
     * если она указывает на категорию, связанную с текущим поддоменом
     * Было:  /category/series/...
     * Стало: /series/...
     * @param string $url
     * @return    string
     */
    function correctUrlForDisplay($url)
    {
        $domainId = $this->currentId();
        if ($domainId) {
            $domainCatUrl = $this->currentDomainCatUrl();
            if ($domainCatUrl !== '') {
                $domainCatUrl = '/' . $domainCatUrl . '/';
                if (strpos($url, $domainCatUrl) === 0) {
                    $url = substr_replace($url, '/', 0, strlen($domainCatUrl));
                }
            }
        }
        return $url;
    }


    /**
     * Метод проверяет ситуацию, когда происходит кросдоменный AJAX-запрос с одного из поддоменов
     * Если запрос допустим (т.е. источник запроса "свой", то формируется заголовок Access-Control-Allow-Origin
     * Метод нужно вызывать где-то в начале работы скрипта (до запуска сессии)
     */
    /*function checkCrossDomainsRequest(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            $d = str_replace(array('http://', 'https://', 'www.'), '', trim(mb_strtolower($_SERVER['HTTP_ORIGIN'])));
            if($d === _HOST){
                $domainId = 0;
            }else{
                $domainId = $this->getCell(
                    'id',
                    '`domain` = \'' . MySQL::mres($d) . '\''
                );
            }
            if($domainId !== false){
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);

                $cookie = trim($_GET['ajax-cookie']);
                $cookie = explode(';', $cookie);
                foreach($cookie as $c){
                    $c = trim($c);
                    if($c === '') continue;
                    list($k, $v) = array_map('trim', explode('=', $c));
                    setcookie($k, $v);
                    if($k === session_name()){
                        session_id($v);
                    }
                }
            }
        }
    }*/


    /**
     * Возвращает ID текущего домена
     * 0 - основной домен сайта (_HOST) или неизвестный системе поддомен
     * @return int
     */
    protected function currentId()
    {
        static $domainId = false;
        if ($domainId !== false) {
            return intval($domainId);
        }

        $d = str_replace('www.', '', trim(mb_strtolower($_SERVER['HTTP_HOST'])));

        if ($d === _HOST) {
            $domainId = 0;
        } else {
            $domainId = intval(
                $this->getCell(
                    'id',
                    '`domain` = \'' . MySQL::mres($d) . '\''
                )
            );
        }
        return $domainId;
    }


    /**
     * Кусочек URL категории, связанной с текущим поддоменом
     * @return string
     */
    protected function currentDomainCatUrl()
    {
        return self::domainCatUrl($this->currentId());
    }


    static function domainCatUrl($domainId)
    {
        $domainId = intval($domainId);
        if (!$domainId) {
            return '';
        }
        static $cache = array();

        if (!isset($cache[$domainId])) {
            static $oCategories;
            if (!$oCategories) {
                $oCategories = new Catalog_Categories();
            }
            $cache[$domainId] = trim(
                $oCategories->getCell(
                    'url',
                    '`domain_id` = ' . $domainId . ' AND `parent_id` = 0'
                )
            );
        }
        return $cache[$domainId];
    }


    /**
     * @param string $url
     * @param int    $domainId
     */
    protected function redirectTo($url, $domainId)
    {
        $domainId = intval($domainId);
        $urlParts = parse_url(trim($url));

        if ($domainId) {
            $d = $this->getCell('domain', '`id` = ' . $domainId);
            if (!$d) {
                $d = _HOST;
            }
            $urlParts['host'] = $d;

            // Удаляем из начала URL'а часть, указывающую на категорию, связанную с доменом, на который происходит редирект
            // Было:      site.ru/category/series/...
            // Стало: sub.site.ru/series/...
            $oCategories = new Catalog_Categories();
            $domainCatUrl = trim(
                $oCategories->getCell(
                    'url',
                    '`domain_id` = ' . $domainId . ' AND `parent_id` = 0'
                )
            );
            if ($domainCatUrl !== '') {
                $domainCatUrl = '/' . $domainCatUrl . '/';
                if (strpos($urlParts['path'], $domainCatUrl) === 0) {
                    $urlParts['path'] = substr_replace($urlParts['path'], '/', 0, strlen($domainCatUrl));
                }
            }
        } else {
            $urlParts['host'] = _HOST;
        }

        if (!isset($urlParts['scheme'])) {
            $urlParts['scheme'] = 'http';
        }
        if (isset($urlParts['query']) && trim($urlParts['query']) !== '') {
            $urlParts['query'] = '?' . $urlParts['query'];
        } else {
            $urlParts['query'] = '';
        }
        if (isset($urlParts['fragment']) && trim($urlParts['fragment']) !== '') {
            $urlParts['fragment'] = '#' . $urlParts['fragment'];
        } else {
            $urlParts['fragment'] = '';
        }

        $url = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . $urlParts['query'] . $urlParts['fragment'];
        header(
            'Location: ' . $url,
            true,
            301
        );
        exit;
    }
}
