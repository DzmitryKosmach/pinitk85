<?php

class ModuleMain
{
  public static function main($pageInf)
  {
    // Получаем путь к шаблону
    $tpl = Config::path('skins') . '/html/User/mReputation.htm';

    // Добавим отладку
    if (!file_exists($tpl)) {
      die('Template not found: ' . $tpl);
    }

    // Вставляем переменные
    return pattExeP(fgc($tpl), array(
      'pageInf' => $pageInf,
      'title' => $pageInf['name'] ?? 'Репутация'
    ));
  }
}

?>
