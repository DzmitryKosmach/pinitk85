<?php

/**
 * Таблица Администраторов
 *
 * @author    Seka
 */

class Administrators extends ExtDbList
{

    /**
     * @var string
     */
    static string $tab = 'administrators';

    static private string $backURL = '';

    /**
     * Возможные права доступа
     */
    const R_PAGES = 1;
    const R_NEWS = 2;
    const R_CATALOG = 4;
    const R_SUPPLIERS = 8;
    const R_ORDERS = 16;
    const R_SETTINGS = 32;
    const R_CLIENTS = 64;
    const R_REVIEWS = 128;
    const R_DEALERS = 256;

    const SALT = "-=kl(PH;*&og67t8F67ghFy^gfGF=-";

    /**
     * @var array
     */
    static $rights = [
        self::R_PAGES => 'Наполнение страниц',
        self::R_NEWS => 'Новости / Статьи',
        self::R_CATALOG => 'Каталог',
        self::R_SUPPLIERS => 'Поставщики',
        self::R_ORDERS => 'Заказы',
        self::R_SETTINGS => 'Настройки',
        self::R_CLIENTS => 'Наши проекты',
        self::R_REVIEWS => 'Отзывы',
        self::R_DEALERS => 'Дилеры'
    ];


    /**
     * Авторизован как админ или нет
     * @return bool
     */
    static function checkAuth(): bool
    {

        self::$backURL = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if (!isset($_SESSION['token']) || !ctype_alnum($_SESSION['token'])) {
            return false;
        }

        $user = (new Administrators)->info();

        if (!isset($user['id'])) {
            return false;
        }

        $oStat = new Administrators_Stat();
        $oStat->log($user['id']);

        return true;
    }


    /**
     * Вход администратора
     *
     * @param string $login
     * @param string $pass
     * @return bool
     */
    static function login(string $login, string $pass): bool
    {
        $oAdministrators = new self();

        $iUser = $oAdministrators
            ->query('select `id`, `password` from `administrators` where `login` = \'' . MySQL::mres($login) . '\' limit 1');

        if (!isset($iUser[0]['id'])) {
            return false;
        }

        if (!password_verify($pass, $iUser[0]['password'])) {
            return false;
        }

        try {
            $token = random_bytes(20);
            $token = bin2hex($token);
        } catch (Exception $e) {
            return false;
        }

        $oAdministrators->upd($iUser[0]['id'], ['token' => $token, 'last_enter' => time()]);

        $_SESSION['token'] = $token;

        return true;
    }


    /**
     * Выход администратора
     */
    static function logout(): void
    {
        unset($_SESSION['token']);

        header('Location: /');

        exit;
    }

    /**
     * Генерирует hash пароля
     *
     * @param string $password Пароль
     * @return string hash пароля
     */
    public function createPassword(string $password): string
    {
        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        } catch (Exception $e) {
            return '';
        }

        return $passwordHash;
    }


    /**
     * Возвращает всю инфу о текущем администраторе
     * @return array|bool
     */
    function info(): array|bool
    {
        if (!isset($_SESSION['token']) || !ctype_alnum($_SESSION['token'])) {
            return false;
        }

        $token = trim($_SESSION['token']);

        return $this->getRow('id, login, last_enter, rights', '`token` = \'' . $token . '\'');
    }


    /** Получаем массив прав запрошенного (или текущего авторизованного) админа
     * @param int $adminId
     * @return array
     * Ключи массива - права, значения - единицы (1), для быстрого поиска
     */
    public function getRights(int $adminId = 0): array
    {
        $rights = [];

        if (!$adminId) {
            if (isset($_SESSION['token']) && ctype_alnum($_SESSION['token'])) {
                $u = $this->getRow('id', '`token` = \'' . $_SESSION['token'] . '\'');
                if (!isset($u['id'])) {
                    return [];
                }
                $adminId = $u['id'];
            } else {
                return $rights;
            }
        }

        $rights[$adminId] = [];

        $r = intval($this->getCell('rights', '`id` = ' . $adminId));
        $r = decbin($r);
        $l = strlen($r);

        for ($i = 0; $i < $l; $i++) {
            $c = substr($r, $l - $i - 1, 1);
            if (intval($c)) {
                $rights[$adminId][intval(pow(2, $i))] = 1;
            }
        }

        return $rights[$adminId];
    }


    /**
     * Проверка, есть ли у заданного (текущего) админа запрошенное право
     * @static
     * @param int $right
     * @param int $adminId
     * @return    bool
     */
    static function checkRights(int $right, int $adminId = 0): bool
    {
        $rights = (new Administrators)->getRights($adminId);

        return isset($rights[$right]);
    }

    static function getBackURL(): string
    {
        return self::$backURL;
    }
}
