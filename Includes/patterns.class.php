<?php

/**
 * КЛАСС ДЛЯ ОБРАБОТКИ ШАБЛОНОВ
 * @author    Seka
 */

class Pattern
{
    /** Итоговый html-код выполненного шаблона
     * @var string
     */
    protected string $html;


    /**
     * @param string $html
     * @param array  $vars
     * @param bool   $pre шаблон уже прошёл предварительную обработку
     */
    function __construct(string $html, array $vars = [], bool $pre = false)
    {
        // Предварительная обработка
        if (!$pre) {
            $html = $this->pre($html);
        }

        // Переменные
        $html = $this->getVars($vars) . $html;

        // Выполняем код
        ob_start();

        eval($html);

        $this->html = ob_get_contents();

        // Автоматически исправляем пути к изображениям для локальной среды
        $this->html = $this->fixImagePaths($this->html);

        // Работает некорректно
        //$this->html = $this->sanitize($this->html);

        ob_end_clean();
    }

    /**
     * Исправляет пути к изображениям для локальной среды
     */
    private function fixImagePaths(string $html): string
    {
        // Исправляем пути только в локальной среде
        if (defined('APP_ENV') && APP_ENV === 'local') {
            // Заменяем все пути к изображениям
            $html = preg_replace('/src="\/images\//', 'src="/pinitk85/images/', $html);
            $html = preg_replace('/href="\/images\//', 'href="/pinitk85/images/', $html);

            // Заменяем все пути к папке Uploads
            $html = preg_replace('/src="\/Uploads\//', 'src="/pinitk85/Uploads/', $html);
            $html = preg_replace('/href="\/Uploads\//', 'href="/pinitk85/Uploads/', $html);
        }

        return $html;
    }

    private function sanitize(string $buffer): string
    {
        preg_match_all('#\<textarea.*\>.*\<\/textarea\>#Uis', $buffer, $foundTxt);
        preg_match_all('#\<pre.*\>.*\<\/pre\>#Uis', $buffer, $foundPre);

        // replacing both with <textarea>$index</textarea> / <pre>$index</pre>
        $buffer = str_replace(
            $foundTxt[0],
            array_map(function ($el) {
                return '<textarea>' . $el . '</textarea>';
            }, array_keys($foundTxt[0])),
            $buffer
        );
        $buffer = str_replace(
            $foundPre[0],
            array_map(function ($el) {
                return '<pre>' . $el . '</pre>';
            }, array_keys($foundPre[0])),
            $buffer
        );

        // your stuff
        $search = array(
            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
            '/(\s)+/s'       // shorten multiple whitespace sequences
        );

        $replace = array(
            '>',
            '<',
            '\\1'
        );

        // на мастерхосте из-за превышения лимита PREG_RECURSION_LIMIT_ERROR выпадает ошибка
        // в .htaccess добавлено: php_value pcre.recursion_limit 100000
        // если будет ошибка, то вернем исходное состояние
        $buffer = preg_replace($search, $replace, $buffer);
        if (preg_last_error() != PREG_NO_ERROR) {
            return $buffer;
        }

        // Replacing back with content
        $buffer = str_replace(
            array_map(function ($el) {
                return '<textarea>' . $el . '</textarea>';
            }, array_keys($foundTxt[0])),
            $foundTxt[0],
            $buffer
        );

        $buffer = str_replace(
            array_map(function ($el) {
                return '<pre>' . $el . '</pre>';
            }, array_keys($foundPre[0])),
            $foundPre[0],
            $buffer
        );

        $buffer = str_replace('> <', '><', $buffer);

        return $buffer;
    }


    /** Результат работы объекта класса, при выводе его в качестве строки
     * @return string
     */
    function __toString()
    {
        return $this->html;
    }


    /** Собираем переменные в сериализованные строки для передачи в исполняемый код
     * @param array $vars
     * @return string
     */
    function getVars($vars = array())
    {
        $varsCode = '';
        foreach ($vars as $n => $v) {
            $varsCode .= '$' . $n . ' = unserialize(\'' . str_replace(
                array('\\', '\''),
                array('\\\\', '\\\''),
                serialize($v)
            ) . '\');' . _N;
        }
        return $varsCode;
    }


    /** Предварительная обработка шаблона
     * @static
     * @param string $html
     * @return    string
     */
    static function pre($html)
    {
        // ориентируемся на то, что шаблон начинается с html-кода, и расставляем теги < ? и ? > соответственно
        if (strpos($html, '?>') === 0) {
            $html = mb_substr($html, 2);
        }
        if (strpos($html, '<?') !== false && strpos($html, '?>') === false) {
            $html .= '?>';
        }
        $html = '?>' . $html;

        return $html;
    }
}


/** Основная функция: обрабатывает шаблон с PHP-вставками
 * @param string $html
 * @param array  $vars
 * @return    string
 */
function pattExeP($html, $vars = array())
{
    return (string)new Pattern($html, $vars);
}
