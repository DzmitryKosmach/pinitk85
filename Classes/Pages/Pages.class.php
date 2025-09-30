<?php

/**
 * Формиование страниц сайта
 *
 * @author    Seka
 */

define('OUTPUT_DEFAULT', 0);
define('OUTPUT_JSON', 1);
define('OUTPUT_FRAME', 2);
define('OUTPUT_DOC', 3);
define('OUTPUT_XLS', 4);

class Pages extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'pages';

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    /**
     * @return bool
     */
    public static function isMobile(): bool
    {
        return Pages_Mobile::isMobile();
    }


    /**
     * @param int|string $alias
     * @return    string
     */
    public static function name(int|string $alias): string
    {
        $o = new self();

        static $cache = array();

        if (!isset($cache[$alias])) {
            if (gettype($alias) == 'string') {
                $cache[$alias] = trim(
                    $o->getCell(
                        'name',
                        '`alias` = \'' . MySQL::mres(trim($alias)) . '\''
                    )
                );
            } else {
                $cache[$alias] = trim(
                    $o->getCell(
                        'name',
                        '`id` = ' . intval($alias)
                    )
                );
            }
        }

        return $cache[$alias];
    }


    /**
     * Переопределим метод для присвоения order
     * @param array  $data
     * @param string $method
     * @return    int
     * @see    DbList::addArr()
     */
    public function addArr($data = array(), $method = self::INSERT): int
    {
        $res = parent::addArr($data, $method);
        $this->setOrderValue();
        return $res;
    }


    /** Переопределяем удаление записи, чтобы удалялись также и дочерние для неё страницы
     * @param string $cond
     * @return    int        Число удалённых в итоге страниц
     */
    public function delCond($cond = ''): int
    {
        // Страницы с отметками 'spec' и 'admin' удалять нельзя
        if (trim($cond) !== '') {
            $cond = '(' . $cond . ') AND ';
        }
        $cond .= '`spec` = 0 AND `admin` = 0';

        // Получаем ID удаляемых страниц
        $pIds = $this->getCol('id', $cond);

        if (count($pIds)) {
            // Удаляем заданные страницы
            parent::delCond('`id` IN (' . implode(',', $pIds) . ')');

            // Рекурсивно удаляем дочерние страницы
            return count($pIds) + $this->delCond('`parent_id` IN (' . implode(',', $pIds) . ')');
        } else {
            return 0;
        }
    }


    /**
     * Основной метод, генерит и выводит страницу
     * @param int $pageId
     * @return bool
     */
    public function make(int $pageId): bool
    {
        // Получаем параметры страницы
        $data = $this->getRow('*', '`id` = ' . $pageId);

        $pageInf = is_array($data) && count($data) ? $this->imageExtToData($data) : [];

        if (!$pageInf) {
            trigger_error('Page ' . Url::$current . ' (ID ' . intval($pageId) . ') is not found.', E_USER_WARNING);
            exit;
        }

        // Подключаем файл с классом модуля
        $moduleFile = self::moduleFile($pageInf);

        if (!$moduleFile) {
            // Для страниц без модуля задаём модуль простых текстовых страниц
            $pageInf['module'] = 'User/mTextPage';
            $moduleFile = self::moduleFile($pageInf);
        }

        if (!is_file($moduleFile)) {
            trigger_error('Module file "' . $moduleFile . '" not found.', E_USER_WARNING);
            exit;
        }

        include_once($moduleFile);

        // Подключенный файл должен содержать класс [ModuleMain] или класс [ModuleName], где ModuleName.php - этот файл, проверяем это
        $className = 'ModuleMain';
        if (!class_exists($className, false)) {
            $e = explode('/', $moduleFile);
            $className = str_replace('.php', '', array_pop($e));
            if (!class_exists($className, false)) {
                trigger_error(
                    'Can\'t find class "ModuleMain" or "' . $className . '" in ' . $moduleFile . '.',
                    E_USER_WARNING
                );
                exit;
            }
        }

        // Определяем, требуется ли авторизация администратора
        /*$isAdminFunc = create_function(
            '',
            'return isset(' . $className . '::$admin) ? ' . $className . '::$admin : false;'
        );

        $isAdmin = $isAdminFunc();*/

        try {
            $prop = new ReflectionProperty($className, 'admin');
            if (!$prop->isStatic()) {
                return false;
            }
            $obj = new $className;
            $isAdmin = $obj::$admin;
        } catch (\Exception $e) {
            $isAdmin = false;
        }

        if ($isAdmin) {
            if (!Administrators::checkAuth()) {
                $backURL = Administrators::getBackURL();
                $url = Url::a('admin-login') . ($backURL ? '?back_url=' . $backURL : '');
                header('Location: ' . $url);
                exit;
            }
        }
        if (!$isAdmin) {
            // Ввиду особенностей разметки клиентсайда, Pages::flashRelease() надо приклеить к $pageInf['text'] именно здесь
            if ($flashMsg = Pages::flashRelease()) {
                $pageInf['text'] = $flashMsg . $pageInf['text'];
            }
        } else {
            // Изменяем шаблон для переключателя страниц
            self::$tooglePattern = '/html/interfacePagesToggle_admin.htm';
        }

        // Определяем, требуется ли авторизация дилера
        /*$isDealerFunc = create_function(
            '',
            'return isset(' . $className . '::$dealerSecure) ? ' . $className . '::$dealerSecure : false;'
        );
        $isDealer = $isDealerFunc();*/

        try {
            $prop = new ReflectionProperty($className, 'dealerSecure');
            if (!$prop->isStatic()) {
                return false;
            }
            $obj = new $className;
            $isDealer = $obj::$dealerSecure;
        } catch (\Exception $e) {
            $isDealer = false;
        }

        if ($isDealer) {
            if (!Dealers_Security::isAuthorized()) {
                header('Location: ' . Url::a('dealer-login'));
                exit;
            }
        }

        // Выполняем метод main класса $className
        //$mainMethod = create_function('&$pageInf', 'return ' . $className . '::main($pageInf);');

        $modResult = "";
        if (method_exists($className, "main")) {
            $modClass = new $className;
            $modResult = $modClass::main($pageInf);

            if (method_exists($className, "getPageInf")) {
                $pageInf = $className::getPageInf();
            }
        }

        // Проверяем, в каком формате следует вывести результат
        /*$outFormatFunc = create_function(
            '',
            'return isset(' . $className . '::$output) ? ' . $className . '::$output : OUTPUT_DEFAULT;'
        );
        $outFormat = $outFormatFunc();*/

        try {
            $prop = new ReflectionProperty($className, 'output');
            if (!$prop->isStatic()) {
                return false;
            }
            $obj = new $className;
            $outFormat = $obj::$output;
        } catch (\Exception $e) {
            $outFormat = false;
        }

        if ($outFormat == OUTPUT_JSON) {
            // Вывод AJAX-страницы в формате JSON
            $pgHtml = toJson($modResult);
        } elseif ($outFormat == OUTPUT_FRAME) {
            // Вывод AJAX-страницы "как есть" (подходит также для фреймов)
            $pgHtml = $modResult;
        } elseif ($outFormat == OUTPUT_DOC) {
            // Вывод в формате DOC
            //TODO: $pgHtml = self::makeDoc($modResult);
            $pgHtml = 'doc';
        } elseif ($outFormat == OUTPUT_XLS) {
            // Вывод в формате XLS
            $pgHtml = $this->makeXls($modResult);
        } else {
            // Стандартный вывод страницы

            // Формируем оболочку в соответствии с областью сайта, к которой относится данная страница
            if ($isAdmin) {
                // Админка
                $pgHtml = $this->makeAdmin($pageInf, $modResult, $className);
            } else {
                // Пользовательская часть сайта
                $pgHtml = $this->makeUser($pageInf, $modResult);
            }
        }

        // Выводим HTML здесь, т.к. в будущем, возможно, придётся его как-то обработать (вырезать пробелы и т.п.)
        /*$pgHtml = str_replace("\r\n", "\n", $pgHtml);
        $pgHtml = str_replace("\t", ' ', $pgHtml);
        //$pgHtml = preg_replace_callback('/<[^>]*>/s', create_function('$m', 'return str_replace("\n", \' \', $m[0]);'), $pgHtml);
        while(strpos($pgHtml, '  ') !== false) $pgHtml = str_replace('  ', ' ', $pgHtml);
        $pgHtml = str_replace("\n ", "\n", $pgHtml);
        while(strpos($pgHtml, "\n\n") !== false) $pgHtml = str_replace("\n\n", "\n", $pgHtml);*/

        print $pgHtml;
        exit;
    }


    /** Выводим результат в формате XLS
     * @param string $modResult
     * @return    string
     */
    protected function makeXls($modResult = '')
    {
        $tpl = Config::path('skins') . '/html/outerXls.htm';
        $pgHtml = pattExeP(fgc($tpl), array(
            'CONTENT' => $modResult
        ));

        // Заголовки
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . strlen($pgHtml));
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-disposition: attachment; filename="file-' . date('d-m-Y') . '.xls"');

        return $pgHtml;
    }



    /**
     *
     * ГЕНЕРАЦИЯ ШАБЛОНОВ ДЛЯ РАЗЛИЧНЫХ ЧАСТЕЙ САЙТА (гость, админка, финансист, юзер/магазин и т.д.)
     *
     */


    /** Формируем оболочку для страниц админского раздела
     * @param array  $pageInf
     * @param string $modResult
     * @param string $className Класс-модуль, отвечающий за данную страницу
     * @return    string
     */
    protected function makeAdmin(array $pageInf, string $modResult, string $className): string
    {
        $adminMenu = false;

        // Определяем текущий раздел админки
        try {
            $prop = new ReflectionProperty($className, 'adminMenu');

            if (!$prop->isStatic()) {
                return false;
            }

            $obj = new $className;
            $adminMenu = $obj::$adminMenu;

        } catch (\Exception $e) {
            $path = false;
        }

        /*
        $adminMenuSunc = create_function(
            '',
            'return isset(' . $className . '::$adminMenu) ? ' . $className . '::$adminMenu : false;'
        );

        $adminMenu = $adminMenuSunc();
        */

        $tpl = self::areaTplFile('admin');
        return pattExeP(fgc($tpl), array(
            //'currUser'	=> $currUser,	// todo: Возможно, данные админа в шаблоне не понадобятся
            'adminMenu' => $adminMenu,
            'PAGE' => $pageInf,
            'CONTENT' => $modResult
        ));
    }


    /** Формируем оболочку для страниц пользовательской части сайта
     * @param array  $pageInf
     * @param string $modResult
     * @return    string
     */
    protected function makeUser($pageInf, $modResult)
    {
        self::setTable('pages');
        // Верхнее меню
        $menuTop = $this->getWhtKeys(
            'id, name, in_menu1_hightlight',
            '`in_menu1` = 1',
            'order'
        );

        // Нижнее меню
        $menuBottom = $this->getWhtKeys(
            'id, name',
            '`in_menu2` = 1',
            'order'
        );

        // Верхнее меню категорий
        $oCategories = new Catalog_Categories();
        $catsMenu = $oCategories->getTree('id, name, in_index, use_noindex, use_nofollow', 3);

        // удаляем категории, которые не выводятся на главной
        for ($i = count($catsMenu) - 1; $i >= 0; $i--) {
            if ($catsMenu[$i]['in_index'] == 0) {
                unset($catsMenu[$i]);
            }
        };
        // удаляем категории, которые не выводятся на главной

        $tpl = self::areaTplFile('user');
        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'menuTop' => $menuTop,
            'menuBottom' => $menuBottom,
            'catsMenu' => $catsMenu,
            'CONTENT' => $modResult
        ));
    }

    /**
     * @param $pageInf
     * @param $modResult
     */
    function test($pageInf, $modResult)
    {
        print $this->makeUser($pageInf, $modResult);
        exit;
    }


    /**
     *
     * ОПРЕДЕЛЕНИЕ ФАЙЛОВ И ШАБЛОНОВ ДЛЯ МОДУЛЕЙ И РАЗДЕЛОВ САЙТА
     *
     */


    /** Определяем файл с классом модуля страницы
     * @static
     * @param array $pageInf
     * @return    bool|string
     */
    static function moduleFile($pageInf)
    {
        return $pageInf['module'] ? Config::path('modules') . '/' . $pageInf['module'] . '.php' : false;
    }


    /** Определяем файл с шаблоном модуля страницы
     * @static
     * @param array  $pageInf
     * @param string $postfix
     * @param string $ext
     * @return    string
     */
    static function tplFile($pageInf, $postfix = '', $ext = 'htm')
    {
        return Config::path('skins') . '/html/' . $pageInf['module'] . ($postfix ? '_' . $postfix : '') . '.' . $ext;
    }


    /** Определяем файл с шаблоном раздела сайта (админка, кабинет юзера и т.п.)
     * @static
     * @param string $area
     * @return    string
     */
    static function areaTplFile($area)
    {
        $area = ucfirst(strtolower($area));
        return Config::path('skins') . '/html/' . $area . '/Main.htm';
    }


    /**
     *
     * МЕТОДЫ ДЛЯ ЭЛЕМЕНТОВ ИНТЕРФЕЙСА (таблички, переключатели страниц и др.)
     *
     */


    /** Табличка с ошибкой
     * @static
     * @param string $text
     * @return    string
     */
    static function msgErr($text = '')
    {
        return pattExeP(fgc(Config::path('skins') . '/html/interfaceMsgError.htm'), array('text' => $text));
    }


    /** табличка "Всё ок"
     * @static
     * @param string $text
     * @return    string
     */
    static function msgOk($text = '')
    {
        return pattExeP(fgc(Config::path('skins') . '/html/interfaceMsgSuccess.htm'), array('text' => $text));
    }


    /** Сообщение, показываемое 1 раз (мгновенное сообщение)
     * @static
     * @param string $text
     * @param bool   $err Ошибка или "всё ок"
     * @param bool   $backUrl Страница, куда перебросит пользоватя из текущего места, прежде чем он увидит это сообщение
     */
    static function flash($text, $err = false, $backUrl = false)
    {
        if (trim($text) !== '') {
            $_SESSION['flash_msg'] .= $err ? self::msgErr($text) : self::msgOk($text);
        }

        if (!$backUrl) {
            $backUrl = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '/';
        }

        header('Location: ' . $backUrl);
        exit;
    }

    /** Отображение таблички из метода flash()
     * @static
     * @return    string
     * @see        Pages::flash()
     */
    static function flashRelease()
    {
        if ($_SESSION['flash_msg']) {
            $res = $_SESSION['flash_msg'];
            $_SESSION['flash_msg'] = false;
            return $res;
        }
        return '';
    }


    /** Путь к файлу с шаблоном переключателя страниц
     * @var string
     */
    static string $tooglePattern = '/html/interfacePagesToggle.htm';

    /** Сколько показывать номеров страниц в переключателе
     * @var int
     */
    static int $maxToggleLinks = 10;


    /**
     * Генерация блока с переключателем страниц
     * @static
     * @param int $total
     * @param int $pgNum
     * @param int $pgSize
     * @return    string
     */
    static function toggle(int $total = 0, int $pgNum = 1, int $pgSize = 1): string
    {
        // Общее к-во страниц
        $totalPages = ceil($total / $pgSize);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        // Проверяем корректность номера текущей страницы
        if ($pgNum > $totalPages) {
            $pgNum = $totalPages;
        }
        if ($pgNum != -1 && $pgNum < 1) {
            $pgNum = 1;
        }

        // Используем шаблон pagination.htm
        ob_start();
        $currentPage = $pgNum;
        include(Config::path('skins') . '/html/Components/pagination.htm');
        return ob_get_clean();
    }

    /** Формирует URL для ссылки в переключателе страниц
     * @static
     * @param int $pgNum
     * @return    mixed
     */
    static function toggleUrl($pgNum = 1)
    {
        static $prevCurrentUrl = '';    // Страница, на которой в последний раз вызывался данный метод
        static $urlPattern = '';        // Шаблон, сформированный в последний раз

        if ($prevCurrentUrl != Url::$current) {
            $prevCurrentUrl = Url::$current;
            $urlPattern = Url::buildUrl(
                Url::a(Url::$currentID),
                array_merge($_GET),
                array('page' => '-num-')
            );
        }

        if ($pgNum === 0) {
            return str_replace('/page_-num-/', '/page_all/', $urlPattern);
        } else {
            return $pgNum == 1 ?
                str_replace('/page_-num-/', '/', $urlPattern) :
                str_replace('/page_-num-/', '/page_' . $pgNum . '/', $urlPattern);
        }
    }
}
