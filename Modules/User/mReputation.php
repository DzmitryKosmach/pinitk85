<?php
class ModuleMain
{
    /**
     * Количество писем для вывода
     */
    const LETTERS_CNT = 8;

    public static function main($pageInf)
    {
        // Отладка
        error_log('mReputation module called');
        error_log('pageInf: ' . print_r($pageInf, true));

        $tpl = Config::path('skins') . '/html/User/mReputation.htm';

        // Проверка существования шаблона
        if (!file_exists($tpl)) {
            error_log('Template not found: ' . $tpl);
            die('Template not found: ' . $tpl);
        }

        // Письма клиентов (по аналогии с mIndex)
        $oLetters = new Clients_Letters();
        $letters = $oLetters->imageExtToData(            
            $oLetters->get(
                '*',
                '',
                '`order` DESC'
            )
        );

        // Отладка данных
        $data = array(
            'pageInf' => $pageInf,
            'title'   => $pageInf['name'] ?? 'Репутация',
            'letters' => $letters  // <-- добавлено
        );
        error_log('Template data: ' . print_r($data, true));

        return pattExeP(fgc($tpl), $data);
    }
}
