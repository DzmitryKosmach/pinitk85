<?php
class ModuleMain
{
    public static function main($pageInf)
    {
        // Загружаем письма клиентов
        $oLetters = new Clients_Letters();
        $letters = $oLetters->imageExtToData(
            $oLetters->get(
                '*',
                '',
                '`order` DESC'
            )
        );

        $tpl = Config::path('skins') . '/html/User/mLetters.htm';

        if (!file_exists($tpl)) {
            error_log('Template not found: ' . $tpl);
            return '';
        }

        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf,
            'letters' => $letters,
        ));
    }
}