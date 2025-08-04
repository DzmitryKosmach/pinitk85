<?php

/**
 * Обработка URL'ов страниц
 *
 * @author    Seka
 */

class Url extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'pages';

    /**
     * Текущий URL
     * @var string
     */
    static string $current = '';

    /**
     * ID текущей страницы в таблице страниц
     * @var int
     */
    static int $currentID = 0;

    /**
     * Цепочка IDшников страниц от верхнего уровня к текущей странице
     * @var array
     */
    static array $currentIDs = [];

    /**
     * Остающиеся после разбора URL части, не относящиеся к таблице pages
     * @var array
     */
    static array $leftUrlParts = [];

    /**
     * Кеш метода Url::pageUrl()
     * @see    Url::pageUrl()
     * @var array
     */
    static array $pageUrls = [];

    /**
     * Кеш метода Url::parse()
     * @see    Url::parse()
     * @var array
     */
    static array $parsedUrls = [];

    /**
     * Результат работы функции parse() для совместимости данных
     * @var array
     */
    static array $parsed = [];


    /**
     * Запоминаем текущий URL (не знаю, зачем, вдруг пригодиться)
     */
    function __construct()
    {
        if ($_SERVER['REDIRECT_URL']) {
            self::$current = $_SERVER['REDIRECT_URL'];
            if (isset($_SERVER['REDIRECT_QUERY_STRING']) && trim($_SERVER['REDIRECT_QUERY_STRING']) !== '') {
                self::$current .= '?' . $_SERVER['REDIRECT_QUERY_STRING'];
            }
        } else {
            self::$current = $_SERVER['REQUEST_URI'];
        }

        self::setTable(self::$tab);
    }


    /** Определяем ID запрашиваемой страницы по REDIRECT_URL, а также формируем правильный список GET-параметров
     * @return int
     * @see    Url::parse()
     */
    public function getPageId(): int
    {
        $this->checkEndingSlash(self::$current);

        $oHttpHeaders = new Url_HttpHeaders();
        $oHttpHeaders->check(self::$current);

        $this->checkOldUrls();

        $pgFound = $this->parse(
            self::$current,
            self::$currentID,
            self::$currentIDs,
            $_GET,
            self::$leftUrlParts
        );

        if (count($pgFound)) {
            return self::$currentID;
        } else {
            $oPages = new Pages();
            $oPages->make(404);
            exit;
        }
    }


    /**
     * @param string $url
     */
    protected function checkEndingSlash(string $url): void
    {
        $e = explode('?', $url);
        $url = array_shift($e);

        if (mb_substr($url, mb_strlen($url) - 1) !== '/') {
            $url .= '/';

            if (count($_GET)) {
                $url .= '?' . http_build_query($_GET);
            }

            header(
                'Location: ' . $url,
                true,
                301
            );
            exit;
        }
    }


    /**
     * Временная фигня, чтобы при попытке зайти на страницу по старому урлу (с нижними подчёркиваниями) происходил редирект на новый (с дефисами)
     * Заглушка написана 2014-09-23; через полгода её можно удалить
     * Update 11-04-2024: функция все еще актуальна.
     */
    public function checkOldUrls(): void
    {
        $replaces = [
            '/catalog/kresla_dlja_rukovoditelja/' => '/catalog/ofisnye_kresla/dlya_rukovoditelya/',
            '/catalog/kresla_dlja_personala/' => '/catalog/ofisnye_kresla/dlya_personala/',
            '/catalog/stulja_dlja_posetitelej/' => '/catalog/ofisnye_stulya/'
        ];

        foreach ($replaces as $from => $to) {
            self::$current = str_replace($from, $to, self::$current);
        }

        $old = [
            '\/dostavka_i_sborka\/?.*',
            '\/o_garantii\/?.*',
            '\/articles\/[a-zA-Z0-9]*_',
            '\/catalog\/[a-zA-Z0-9]*_',
            '\/catalog\/aksessuary\/[a-zA-Z0-9]*_'
        ];

        foreach ($old as $p) {
            if (preg_match('/' . $p . '/ius', self::$current)) {
                header(
                    'Location: ' . str_replace('_', '-', self::$current),
                    true,
                    301
                );
                exit;
            }
        }
    }


    /** Определяем из заданного URL'а ID страницы и извлекаем массив GET_переменных
     * @param string $url URL, который нужно распарсить
     * @param int    $pageId Сюда запишется ID страницы по URL'у
     * @param array  $pagePath Сюда запишется массив ID страниц от корня до текущей ($pageId)
     * @param array  $getVars Сюда запишутся GET-переменные, извлечённые из URL'а
     * @param array  $leftUrlParts Сюда запишутся части URL, не относящиеся к таблице pages (собсна, из них извлекаются GET-переменные)
     * @param bool   $allowRedirect Разрешить редирект и прекращение скрипта до возврата результата, если это необходимо
     * @return array      Массив с данными страницы. Если страница не найдена, то массив будет пустой.
     */
    function parse(
        string $url,
        int $pageId = 0,
        array $pagePath = [],
        array $getVars = [],
        array $leftUrlParts = [],
        bool $allowRedirect = true
    ): array {
        $url = trim($url);

        $parsedUrls = self::$parsedUrls;
        /*
                if (isset($parsedUrls[$url])) {
                    dp($parsedUrls);
                    // Получаем результат из кеша
                    // -> Откуда он берется при динамическом вызове?..
                    $pageId = $parsedUrls[$url]['id'];
                    $pagePath = $parsedUrls[$url]['path'];
                    $leftUrlParts = $parsedUrls[$url]['left'];

                    if (!is_array($getVars)) {
                        $getVars = array();
                    }

                    $getVars = array_merge($getVars, $parsedUrls[$url]['vars']);

                    return $parsedUrls[$url];
                }
        */
        if ($allowRedirect) {
            $oDomains = new Catalog_Domains();
            $oDomains->checkRedirect($url);
        }

        $pageId = 0;
        $pagePath = $leftUrlParts = [];
        if (!is_array($getVars)) {
            $getVars = [];
        }

        $parsedUrls[$url] = [
            'id' => 0,
            'path' => [],
            'vars' => [],
            'left' => []
        ];

        $urlParts = parse_url($url);
        $urlPath = trim($urlParts['path'], '/');
        //dp($urlPath);
        if ($urlPath === '') {
            // Главная страница
            $pageId = 1;
            $pagePath[] = 1;

            self::$parsedUrls[$url]['id'] = $pageId;
            self::$parsedUrls[$url]['path'] = $pagePath;
            self::$parsedUrls[$url]['result'] = true;

            self::$currentID = $pageId;
            self::$leftUrlParts = $leftUrlParts;

            return self::$parsedUrls;
        }

        // Разбиваем URL на части
        $urlPath = explode('/', $urlPath);
        //dp($urlPath);
        // Ищем страницу среди категорий каталога верхнего уровня
        $oCategories = new Catalog_Categories();
        $res = $oCategories->getCount('`parent_id` = 0 AND `url` = \'' . MySQL::mres($urlPath[0]) . '\'');

        //dp($urlPath[0], $res);
        if ($res) {
            $pageId = Catalog::CATALOG_PAGE_ID;
            $pagePath[] = Catalog::CATALOG_PAGE_ID;
            $leftUrlParts = $urlPath;
        } else {
            // Ищем страницу в таблице pages
            $urlWord = array_shift($urlPath);
            $parent = 0;
            do {
                $pid = $this->getCell(
                    'id',
                    '`parent_id` = ' . $parent . ' AND `url` = \'' . MySQL::mres($urlWord) . '\''
                );
                if ($pid === false) {
                    break;
                }

                // Запоминаем ID найденной страницы, а также принимаем его как parent_id для следующей итерации
                $pageId = $parent = $pid;
                $pagePath[] = $pid;

                // Получаем следующий кусок URL'а
                $urlWord = count($urlPath) ? array_shift($urlPath) : false;
            } while ($pid && $urlWord);

            // Если в $urlWord что-то осталось, его нужно вернуть в $urlPath
            if ($urlWord !== false) {
                array_unshift($urlPath, $urlWord);
            }

            if (!$pageId) {
                // Страница не найдена
                $parsedUrls[$url]['result'] = false;
                return [];
            }

            // Записываем оставшиеся части $urlPath в $leftUrlParts
            $leftUrlParts = $urlPath;
        }

        // Извлекаем GET-переменные из оставшихся частей $urlPath
        $pNum = 0;
        foreach ($urlPath as $part) {
            if (strpos($part, '_') !== false) {
                $key = explode('_', $part);
                $val = array_pop($key);
                $key = implode('_', $key);
            } else {
                $key = 'p' . $pNum;
                $val = $part;
                $pNum++;
            }
            $getVars[$key] = $val;

            $parsedUrls[$url]['vars'][$key] = $val;
        }

        // Кешируем результат
        $parsedUrls[$url]['id'] = $pageId;
        $parsedUrls[$url]['path'] = $pagePath;
        $parsedUrls[$url]['left'] = $leftUrlParts;
        $parsedUrls[$url]['result'] = true;

        self::$currentID = $pageId;
        self::$leftUrlParts = $leftUrlParts;

        self::$parsedUrls = $parsedUrls;

        $_GET = array_merge($getVars, $_GET);

        self::$parsed = [
            'url' => $url,
            'pageId' => $pageId,
            'pagePath' => $pagePath,
            'getVars' => $getVars,
            'leftUrlParts' => $leftUrlParts,
            'allowRedirect' => $allowRedirect
        ];

        return $parsedUrls;
    }


    /** Получаем URL страницы по alias или ID
     * @param string|int $alias
     * @return    string
     */
    function pageUrl($alias)
    {
        if (isset(self::$pageUrls[$alias])) {
            return self::$pageUrls[$alias];
        }

        $id = false;
        if (gettype($alias) == 'string') {
            $this->get('id', '`alias` = \'' . MySQL::mres($alias) . '\'', '', 1);
            if ($this->len) {
                $id = $this->nul['id'];
            }
        }
        if (!$id) {
            $id = intval($alias);
        }
        if ($id == 1) {
            // Главная страница, или страница каталога (категория / товар)
            self::$pageUrls[$alias] = '/';
            return '/';
        }

        $res = '/';
        while ($id) {
            $this->get('url, parent_id', '`id` = ' . $id);
            if (!$this->len) {
                self::$pageUrls[$alias] = $res;
                return $res;
            }
            $res = '/' . $this->nul['url'] . $res;
            $id = $this->nul['parent_id'];
        }
        self::$pageUrls[$alias] = $res;
        return $res;
    }


    /** static-вариант предыдущего метода
     * @static
     * @param string|int $alias
     * @return    string
     */
    static function a($alias)
    {
        /*static $o = false;
        if ($o === false) {
            $o = new self();
        }*/
        $o = new self();
        return $o->pageUrl($alias);
    }


    /** Формируем полный URL из собственного URL'а страницы и набора GET-параметров
     * @static
     * @param       $url
     * @param array $GETvars GET-параметры
     * @param array $replace Массив, частично замещаюищй или удаляющий значения из $GETvars
     * @return    string
     *
     * Подробнее про $replace:
     * Чтобы заменить элемент в $GETvars, в $replace передаём его обычноым способом: ('ключ' => 'новове значение');
     * Чтобы удалить элемент из $GETvars, в $replace пишем так: ('ключ' => null)
     */
    static function buildUrl($url, $GETvars = array(), $replace = array())
    {
        if (!$url) {
            $url = Url::a(Url::$currentID);
        }

        if (!function_exists('_buildUrlReplaceVars')) {
            function _buildUrlReplaceVars(&$GETvars, $replace)
            {
                foreach ($replace as $k => $v) {
                    if ($v === null) {
                        if (isset($GETvars[$k])) {
                            unset($GETvars[$k]);
                        }
                    } else {
                        if (is_array($v)) {
                            _buildUrlReplaceVars($GETvars[$k], $v);
                        } else {
                            $GETvars[$k] = $v;
                        }
                    }
                }
            }
        }
        _buildUrlReplaceVars($GETvars, $replace);

        // Разделяем GET-параметры на именованные и неименованные (p0, p1, p2 ...)
        $namedVars = array();
        $notNamedVars = array();
        foreach ($GETvars as $k => $v) {
            if (preg_match('/^p[0-9]+$/', $k)) {
                $notNamedVars[intval(str_ireplace('p', '', $k))] = $v;
            } else {
                $namedVars[$k] = $v;
            }
        }
        ksort($notNamedVars);    // Сортируем неименованные параметры

        // Проверяем, чтобы значения неименованных параметров были корректными url-кусками
        $notNamedIsCorrect = true;
        foreach ($notNamedVars as $v) {
            if (is_array($v) || !trim($v) || $v != urlencode($v) || mb_strpos($v, '_') !== false) {
                $notNamedIsCorrect = false;
            }
        }

        // Собираем GET-параметры обратно в один массив
        $GETvars = array();
        foreach ($notNamedVars as $k => $v) {
            $GETvars['p' . $k] = $v;
        }
        if (count($namedVars)) {
            $GETvars = array_merge($GETvars, $namedVars);
        }

        $nonUrlVars = array();
        foreach ($GETvars as $k => $v) {
            if (!is_array($v)) {
                $v = is_null($v) ? "" : $v;
                // Значение $_GET параметра - строка
                $vEnc = urlencode($v);
                if ($v == $vEnc && mb_strpos($v, '_') === false) {
                    if (preg_match('/^p[0-9]+$/', $k) && $notNamedIsCorrect) {
                        $url .= $vEnc . '/';
                    } else {
                        $url .= $k . '_' . $vEnc . '/';
                    }
                } else {
                    $nonUrlVars[$k] = $v;
                }
            } elseif (is_array($v) && count($v)) {
                // Значение $_GET параметра - массив
                $nonUrlVars[$k] = $v;
            }
        }
        if (count($nonUrlVars)) {
            $url .= '?' . http_build_query($nonUrlVars);
        }

        return $url;
    }


    /**
     * @param string $url
     * @return    string
     */
    static function seoHide($url)
    {
        static $shift = 15;
        static $chars = array(
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            'g',
            'h',
            'i',
            'j',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'q',
            'r',
            's',
            't',
            'u',
            'v',
            'w',
            'x',
            'y',
            'z',
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            ':',
            '/',
            '-',
            '_',
            '.',
            '?'
        );
        static $charsCnt;
        if (!$charsCnt) {
            $charsCnt = count($chars);
        }

        $url = mb_strtolower(trim($url));
        $l = mb_strlen($url);
        $code = '';
        for ($i = 0; $i < $l; $i++) {
            $ch = mb_substr($url, $i, 1);
            $n = array_search($ch, $chars);
            if ($n !== false) {
                $n += $shift;
                if ($n >= $charsCnt) {
                    $n -= $charsCnt;
                }
                $ch = $chars[$n];
            }
            $code .= $ch;
        }
        return $code;
    }
}
