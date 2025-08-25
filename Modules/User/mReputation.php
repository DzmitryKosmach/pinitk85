<?php
class ModuleMain
{
    public static function main($pageInf)
    {
        // Получаем путь к шаблону
        $tpl = Config::path('skins') . '/html/User/mReputation.htm';
        // Вставляем переменные, если нужно
        return pattExeP(fgc($tpl), array(
            'pageInf' => $pageInf
        ));
    }
}
