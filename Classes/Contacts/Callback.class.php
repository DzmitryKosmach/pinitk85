<?php

/**
 * Заявки на обратный звонок
 *
 * @author    Seka
 */

class Contacts_Callback
{
    /*const MAX_LEN_NAME = 1000;
    const MAX_LEN_PHONE = 100;*/

    const SESS_KEY = 'callback-data';

    /**
     * @static
     * @param array $data
     */
    static function setLastData($data)
    {
        $_SESSION[self::SESS_KEY] = $data;
    }

    /**
     * @static
     * @return array
     * array('name' => ..., 'phone' => ...) или пустой массив
     */
    static function getLastData()
    {
        if (isset($_SESSION[self::SESS_KEY]) && is_array($_SESSION[self::SESS_KEY])) {
            return $_SESSION[self::SESS_KEY];
        } else {
            return array();
        }
    }

    /** Отправка заявки
     * @static
     * @param string $name
     * @param string $phone
     */
    static function make($name, $phone)
    {
        // Всё ок, отправляем заявку
        Email_Tpl::send(
            'callback',
            Options::name('admin-callback-email'),
            array(
                'name' => trim($name),
                'phone' => trim($phone)
            )
        );
    }

}
