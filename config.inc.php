<?php

//require_once($_SERVER['DOCUMENT_ROOT'] . '/antibot/code/include.php');

/******************************************************************************/
// КОДИРОВКА
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
ini_set('memory_limit', '5512M');
ini_set('pcre.backtrack_limit', 100000000);
date_default_timezone_set('Europe/Moscow');
/******************************************************************************/


/******************************************************************************/

// КОНСТАНТЫ
define('_N', "\r\n");
define('_ROOT', str_replace('\\', '/', dirname(__FILE__))); // виндовый локальный пользователь!
//define('_HOST', 'mebelioni.ru');	Эта константа определяется к конфигах отдельных серверов

define('SEND_MAIL', 1);
define('SEND_SMTP', 2);

define('SQL_LIB_MYSQL', 1);
define('SQL_LIB_MYSQLi', 2);

define('DECIMAL', 2);

require_once "config-app.php";

/******************************************************************************/


// В этом классе определяются параметры сайты, которые не должны меняться в зависимости от сервера
// Любой параметр можно вынести отсюда в класс-наследник в файлах config-local.inc и config-prod.inc
// И там определить отдельно для каждого сервера

class ConfigBase
{

    public static bool $debug = false;

    // Конфиг ПУТЕЙ
    public static array $pathsRel = [
        'includes' => '/Includes',
        'external' => '/ExternalLibs',
        'classes' => '/Classes',
        'modules' => '/Modules',
        'skins' => '/Skins',
        'images' => '/Uploads',
        'upload' => '/Uploads',
        'temp' => '/Uploads/_TMP'
    ];

    public static function pathRel($name): string
    {
        return self::$pathsRel[$name];
    }

    // Т.к. пути к каталогам нужно получать абсолютными, это будет происходить через метод path()
    public static function path($name): string
    {
        return _ROOT . self::$pathsRel[$name];
    }

    // Конфиг СЕССИИ
    public static array $session = [
        'name' => 'test',
        'lifetime' => 2678400,    // 86400 * 31
        //'path'		=> '/../_SESSIONS',
        //'domain'	=> '.test.ru'
    ];

    // Графика
    public static array $img = [
        'lib' => 'gd',
        //'lib'	=> 'imagick',
        'maxW' => 3200,
        'maxH' => 2400
    ];

    /******************************************************************************/
    // Далее следует динамическая часть конфига, которую можно переопределить в доп. конфиге

    // Конфиг БД
    public static array $db = [
        'log' => false,
        'lib' => SQL_LIB_MYSQLi,
        'host' => '',
        'user' => '',
        'pass' => '',
        'name' => ''
    ];

    // Конфиг отправки почты
    public static int $email = SEND_MAIL;
    public static array $smtp = [
        'host' => '',
        'port' => 465,
        'useSsl' => false,
        'useTls' => false,
        'localhost' => 'localhost',
        'timeout' => 60,
        'login' => '',
        'pass' => ''
    ];

    /******************************************************************************/
}

// Подключаем доп. конфиг в зависимости от текущего сервера

if (!file_exists(_ROOT . '/config-' . APP_ENV . '.inc.php')) {
    die("Config file missing.");
}

include_once(_ROOT . '/config-' . APP_ENV . '.inc.php');
