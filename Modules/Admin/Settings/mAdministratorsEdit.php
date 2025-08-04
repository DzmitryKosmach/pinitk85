<?php

/** Админка: Администраторы - редактирование/добавление
 * @author    Seka
 */

class mAdministratorsEdit extends Admin
{

    /**
     * @var string
     */
    static $adminMenu = Admin::SETTINGS;

    /**
     * @var int
     */
    var $rights = Administrators::R_SETTINGS;

    /**
     * @var array
     */
    static $current = array();


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();

        $oAdministrators = new Administrators();

        $aId = intval($_GET['id']);

        self::$current = $oAdministrators->info();

        if (self::$current['id'] != 1) {
            if ($aId != self::$current['id']) {
                Pages::flash('Вы можете редактировать только свою учётную запись администратора.', true);
            }
        }

        $init = array();

        if ($aId) {
            // Редактирование
            $init = $oAdministrators->getRow('id, login, last_enter, rights', '`id` = ' . $aId);

            if ($init === false) {
                Pages::flash('Редактируемая учётная запись администратора не найдена', true);
            }

            if (self::$current['id'] == 1) {
                $init['rights'] = $oAdministrators->getRights($init['id']);
            }
        }


        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'aId' => $aId,
            'current' => self::$current
        ));

        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        $frm->setInit($init);
        return $frm->run('mAdministratorsEdit::save', 'mAdministratorsEdit::check');
    }


    /**
     * @param $initData
     * @return array|bool
     */
    static function check($initData): array|bool
    {
        $_POST['login'] = trim($_POST['login']);
        $_POST['pass_new'] = trim($_POST['pass_new']);

        // Проверяем уникальность логина
        $oAdministrators = new Administrators();
        if ($oAdministrators->getCount(
            '`login` = \'' . MySQL::mres($_POST['login']) . '\' AND `id` != ' . intval($initData['id'])
        )) {
            return array(
                array(
                    'name' => 'login',
                    'msg' => 'Указанный логин уже занят другим администратором.'
                )
            );
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        $oAdministrators = new Administrators();

        $save = array(
            'login' => $_POST['login']
        );

        if ($_POST['pass_new']) {

            if (strlen($_POST['pass_new']) < 8) {
                Pages::flash('Пароль не может быть менее 8 символов', true);
                return;
            }

            $save['password'] = $oAdministrators->createPassword($_POST['pass_new']);
        }

        if (self::$current['id'] == 1) {
            $rights = 0;
            foreach (Administrators::$rights as $r => $dsc) {
                if (intval($initData['id']) == 1 || intval($newData['rights'][$r])) {
                    $rights += $r;
                }
            }
            $save['rights'] = $rights;
        }

        if ($initData['id']) {
            $oAdministrators->upd($initData['id'], $save);
            $msg = 'Учётная запись администратора сохранена.';
        } else {
            $oAdministrators->add($save);
            $msg = 'Новый администратора создан.';
        }

        Pages::flash($msg, false, Url::a('admin-settings-administrators'));
    }

}
