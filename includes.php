<?php

// Основной конфиг
include_once(dirname(__FILE__) . '/config.inc.php');

// Конфиг сессии
if ((!isset($_GET['no_session']) || !intval($_GET['no_session']))) {
    if (isset(Config::$session['lifetime'])) {
        ini_set('session.gc_maxlifetime', Config::$session['lifetime']);
        ini_set('session.cookie_lifetime', Config::$session['lifetime']);
    }
    if (isset(Config::$session['path'])) {
        ini_set('session.save_path', _ROOT . Config::$session['path']);
    }
    if (isset(Config::$session['domain'])) {
        ini_set('session.cookie_domain', Config::$session['domain']);
    }

    if (trim(Config::$session['name']) !== '') {
        session_name(Config::$session['name']);
    }

    // Запуск сессии
    @session_start();
}

// Пропускаем кроссдоменные AJAX-запросы со "своих" доменов
// Заодно корректируем session_id при необходимости
/*$oDomains = new Catalog_Domains();
$oDomains->checkCrossDomainsRequest();*/

// Стандартный заголовок
header('Content-Type: text/html; charset=utf-8');
header('Expires: Mon, 23 May 1995 02:00:0 GMT');
header('Vary: User-Agent');

// Подключаем базовые библиотеки
$files = glob(Config::path('includes') . '/*.php');
foreach ($files as $f) {
    include_once($f);
}

//print session_id();
//print_array(session_get_cookie_params());
//print_array($_SERVER); exit;
// ------------- NEW CODE -------------

/**
 * Исправляет пути к изображениям для локальной среды
 */
function fixImagePath($path)
{
    if (strpos($path, '/images/') === 0) {
        return '/pinitk85' . $path;
    }
    if (strpos($path, '/Uploads/') === 0) {
        return '/pinitk85' . $path;
    }
    return $path;
}

/**
 * Dump & Die
 * @param ...$vars
 * @return void
 */
function dd(...$vars)
{
    ini_set('xdebug.var_display_max_depth', '20');
    ini_set('xdebug.var_display_max_children', '256');
    ini_set('xdebug.var_display_max_data', '4096');
    $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    foreach ($vars as $element) {
        echo "<pre style='background-color: #000; color: yellowgreen; font-size: 12px; font-family: \"Courier New\", Courier, monospace; padding: 6px; margin-bottom: 10px'>";
        echo "<p style='color: yellow'>Call => <b>" . $trace[0]['file'] . ": " . $trace[0]['line'] . "</b></p>";
        var_dump($element);
        echo PHP_EOL . "</pre>" . PHP_EOL;
    }

    exit();
}

/**
 * Export & die
 * @param ...$vars
 * @return void
 */
function de(...$vars)
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    foreach ($vars as $element) {
        echo "<pre style='background-color: #000; color: yellowgreen; font-size: 12px; font-family: \"Courier New\", Courier, monospace; padding: 6px; margin-bottom: 10px'>";
        echo "<p style='color: yellow'>Call => <b>" . $trace[0]['file'] . ": " . $trace[0]['line'] . "</b></p>";
        var_export($element);
        echo PHP_EOL . "</pre>" . PHP_EOL;
    }

    exit();
}

/**
 * Do Print
 * @param ...$vars
 * @return void
 */
function dp(...$vars)
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    foreach ($vars as $element) {
        echo "<pre style='background-color: #000; color: yellowgreen; font-size: 12px; font-family: \"Courier New\", Courier, monospace; padding: 6px; margin-bottom: 10px'>";
        echo "<p style='color: yellow'>Call => <b>" . $trace[0]['file'] . ": " . $trace[0]['line'] . "</b></p>";
        var_dump($element);
        echo PHP_EOL . "</pre>" . PHP_EOL;
    }
}
