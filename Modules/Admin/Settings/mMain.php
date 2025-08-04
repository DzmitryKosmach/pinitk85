<?php

/** Админка: начало раздела "Настройки"
 * @author    Seka
 */

class mMain {
    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main($pageInf = array()){
        //Выход админа
        if (isset($_GET['logout'])) {
            Administrators::logout();
        }

        header('Location: ' . Url::a('admin-settings-administrators'));
        exit;
    }
}

?>