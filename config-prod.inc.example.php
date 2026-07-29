<?php

// КОНФИГ ДЛЯ РАБОТЫ НА ПРОДАКШН СЕРВЕРЕ

define('_HOST', $_SERVER['HTTP_HOST']);

class Config extends ConfigBase {

    static bool $debug = true;

    // Конфиг БД
    public static array $db = [
        'log'	=> false,
        'lib'	=> SQL_LIB_MYSQLi,
        'host'	=> 'db',
        'user'	=> 'mebelioni',
        'pass'	=> 'secret',
        'name'	=> 'mebelioni'
    ];


    // Redis-кеш (на Beget: данные подключения из панели «Redis»)
    public static array $redis = [
        'enabled' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'socket' => '',
        'password' => '', // пароль из панели Beget
        'database' => 1,
        'prefix' => 'mebelioni:cache:',
        'timeout' => 2.0,
        'series_page_ttl' => 3600,
    ];


    // Конфиг СЕССИИ
    public static array $session = [
        'name'		=> 'casapro',
        'lifetime'	=> 2678400
        // 'path'		=> '/../.sessions',
        //'domain'	=> '.mebelioni.ru'
    ];


    // Графика
    public static array $img = [
        'lib'	=> 'gd',
        //'lib'	=> 'imagick',
        'maxW'	=> 3200,
        'maxH'	=> 2400
    ];


    // Конфиг отправки почты
    public static int $email = SEND_MAIL;

    /*
    static $email = SEND_SMTP;
    static $smtp = array(
        'host'		=> 'smtp.gmail.com',
        'port'		=> 465,
        'useSsl'	=> true,
        'useTls'	=> false,
        'localhost'	=> 'localhost',
        'timeout'	=> 60,
        'login'		=> '***@gmail.com',
        'pass'		=> '***'
    );
    */
}
