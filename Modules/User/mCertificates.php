<?php
class ModuleMain
{
  public static function main($pageInf)
  {
    // Отладка
    error_log('mCertificates module called');
    error_log('pageInf: ' . print_r($pageInf, true));

    $tpl = Config::path('skins') . '/html/User/mCertificates.htm';

    // Проверка существования шаблона
    if (!file_exists($tpl)) {
      error_log('Template not found: ' . $tpl);
      die('Template not found: ' . $tpl);
    }

    // Отладка данных
    $data = array(
      'pageInf' => $pageInf,
      'title' => $pageInf['name'] ?? 'Сертификаты качества'
    );
    error_log('Template data: ' . print_r($data, true));

    return pattExeP(fgc($tpl), $data);
  }
}
