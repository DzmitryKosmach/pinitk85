<?php

/** Админка: Список администраторов
 * @author    Seka
 */

class mAdministrators extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::SETTINGS;

    /**
     * @var string
     */
    var $mainClass = 'Administrators';

    /**
     * @var int
     */
    var $rights = Administrators::R_SETTINGS;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();
        $o->getOperations();

        $oAdministrators = new Administrators();
        $administrators = $oAdministrators->get('id, login, last_enter, rights');

        foreach ($administrators as $i => $a) {
            $administrators[$i]['rights'] = $oAdministrators->getRights($a['id']);
        }

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);

        return pattExeP(fgc($tpl), array(
            'administrators' => $administrators,
            'current' => $oAdministrators->info()
        ));
    }


    /**
     * Удаление администратора
     * Удалять админов может только главный админ.
     * Главного админа удалить нельзя
     * @param $iId
     */
    function delItem($iId)
    {
        $oAdministrators = new Administrators();
        $current = $oAdministrators->info();

        if ($current['id'] != 1 || intval($iId) == 1) {
            return;
        }

        $oAdministrators->del(intval($iId));

        Pages::flash('Аккаунт администратора удалён.');
    }
}
