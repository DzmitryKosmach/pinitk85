<?php

/** Страница авторизации админа
 * @author    Seka
 */

class mLogin
{

    static $output = OUTPUT_FRAME;

    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main($pageInf = array())
    {
        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array());

        // Выводим форму
        $frm = new Form($formHtml, false, 'login-error');
        $frm->setInit(array());
        return $frm->run('mLogin::login', 'mLogin::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        if (!Administrators::login($_POST['login'], $_POST['pass'])) {
            return array(array('msg' => 'Неверный логин или пароль.'));
        }

        $back_url = "";

        if (isset($_POST['back_url'])) {
            $back_url = $_POST['back_url'];
        } elseif (isset($_GET['back_url'])) {
            $back_url = $_GET['back_url'];
        }

        if ($back_url && substr($back_url, 0, 6) !== '/admin') {
            $back_url = "";
        }

        if ($back_url) {
            header('Location: ' . $back_url);
            exit();
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function login($initData, $newData)
    {
        header('Location: ' . Url::a('admin-content'));
        exit;
    }

}
