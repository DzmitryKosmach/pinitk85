<?php


/** Автозагрузчик классов
 * 1. Файлы должны заканчиваться на .class.php, или на .abstract.php, или на .interface.php
 * 2. Если класс называется P1_P2_P3, то загрузчик будет искать его в двух возможных местах:
 *    /Classes/P1/P2/P3.class.php
 *    /Classes/P1/P2/P3/P3.class.php
 * @param $className
 * @return null
 */

//function __autoload($className)
spl_autoload_register(function ($className)
{
    if (strpos($className, '_sasl_') !== false) {
        return false;
    }

    if (strpos($className, 'PHPExcel') === 0) {
        return false;
    }

    // Парсим имя класса
    $className = str_replace('__', '_*', $className);
    $class = explode('_', $className);
    //$class = array_map(create_function('$p', 'return str_replace(\'*\', \'_\', $p);'), $class);
    $class = array_map(function ($p) { return str_replace('*', '_', $p);}, $class);

    $classFile = array_pop($class);        // Имя конечного файла с классом
    $classPath = implode('/', $class);    // Путь к файлу с классом
    if ($classPath) {
        $classPath .= '/';
    }


    $tmp = array();

    $classFileName = Config::path('classes') . '/' . $classPath . $classFile . '.class.php';
    $tmp[] = str_replace(_ROOT, '', $classFileName);

    if (!is_file($classFileName)) {
        $classFileName = Config::path('classes') . '/' . $classPath . $classFile . '/' . $classFile . '.class.php';
        $tmp[] = str_replace(_ROOT, '', $classFileName);
    }
    if (!is_file($classFileName)) {
        $classFileName = Config::path('classes') . '/' . $classPath . $classFile . '.abstract.php';
        $tmp[] = str_replace(_ROOT, '', $classFileName);
    }
    if (!is_file($classFileName)) {
        $classFileName = Config::path('classes') . '/' . $classPath . $classFile . '/' . $classFile . '.abstract.php';
        $tmp[] = str_replace(_ROOT, '', $classFileName);
    }
    if (!is_file($classFileName)) {
        $classFileName = Config::path('classes') . '/' . $classPath . $classFile . '.interface.php';
        $tmp[] = str_replace(_ROOT, '', $classFileName);
    }
    if (!is_file($classFileName)) {
        $classFileName = Config::path('classes') . '/' . $classPath . $classFile . '/' . $classFile . '.interface.php';
        $tmp[] = str_replace(_ROOT, '', $classFileName);
    }

    if (!is_file($classFileName)) {
        print 'Class file \'' . $className . '\' not found in these files:<br><em>' . implode(
                '<br>',
                $tmp
            ) . '</em><br><br>';

        $backtrace = debug_backtrace();
        for ($i = 0; $i <= 10; $i++) {
            if (isset($backtrace[$i])) {
                print $backtrace[$i]['file'] . ': ' . $backtrace[$i]['line'] . '<br>';
            }
        }

        exit;
    }

    include_once($classFileName);
    return true;
});


/** Псевдоним file_get_contents()
 * @param $f
 * @return string
 */
function fgc($f)
{
    try {
        $out = file_get_contents($f);
    } catch (\Exception $e) {
        $out = "";
    }

    return $out;
}


/**
 * @param mixed $data
 * @return    string
 */
function toJson($data)
{
    return str_replace('\\"', '\\\\\\"', json_encode($data));
}


/** Обрезает текст до $len символов, удаляет теги, добавляет в конце ...
 * @param $text
 * @param $len
 * @return mixed|string
 */
function short($text, $len)
{
    $text = strip_tags($text);
    $l = mb_strlen($text);
    $text = mb_substr($text, 0, $len);
    if ($l > $len) {
        $text .= '...';
    }
    return $text;
}


/** Аналог strtoupper(), который точно работает с русскими буквами
 * @param string $text
 * @return string
 */
function str_to_up($text)
{
    $small = array(
        'а',
        'б',
        'в',
        'г',
        'д',
        'е',
        'ё',
        'ж',
        'з',
        'и',
        'й',
        'к',
        'л',
        'м',
        'н',
        'о',
        'п',
        'р',
        'с',
        'т',
        'у',
        'ф',
        'х',
        'ц',
        'ч',
        'ш',
        'щ',
        'ъ',
        'ы',
        'ь',
        'э',
        'ю',
        'я'
    );
    $big = array(
        'А',
        'Б',
        'В',
        'Г',
        'Д',
        'Е',
        'Ё',
        'Ж',
        'З',
        'И',
        'Й',
        'К',
        'Л',
        'М',
        'Н',
        'О',
        'П',
        'Р',
        'С',
        'Т',
        'У',
        'Ф',
        'Х',
        'Ц',
        'Ч',
        'Ш',
        'Щ',
        'Ъ',
        'Ы',
        'Ь',
        'Э',
        'Ю',
        'Я'
    );
    return str_replace($small, $big, mb_strtoupper($text));
}


/** Аналог strtolower(), который точно работает с русскими буквами
 * @param string $text
 * @return string
 */
function str_to_low($text)
{
    $small = array(
        'а',
        'б',
        'в',
        'г',
        'д',
        'е',
        'ё',
        'ж',
        'з',
        'и',
        'й',
        'к',
        'л',
        'м',
        'н',
        'о',
        'п',
        'р',
        'с',
        'т',
        'у',
        'ф',
        'х',
        'ц',
        'ч',
        'ш',
        'щ',
        'ъ',
        'ы',
        'ь',
        'э',
        'ю',
        'я'
    );
    $big = array(
        'А',
        'Б',
        'В',
        'Г',
        'Д',
        'Е',
        'Ё',
        'Ж',
        'З',
        'И',
        'Й',
        'К',
        'Л',
        'М',
        'Н',
        'О',
        'П',
        'Р',
        'С',
        'Т',
        'У',
        'Ф',
        'Х',
        'Ц',
        'Ч',
        'Ш',
        'Щ',
        'Ъ',
        'Ы',
        'Ь',
        'Э',
        'Ю',
        'Я'
    );
    return str_replace($big, $small, mb_strtolower($text));
}


/** Псевдоним htmlspecialchars(), действует и для массивов
 * @param array|string $data
 * @return    array|string
 */
function hsch($data)
{
    $type = gettype($data);
    if ($type == 'string') {
        // Строка
        return htmlspecialchars($data);
    } elseif ($type == 'array') {
        // Массив
        return array_map('hsch', $data);
    }

    // Неподходящий тип данных
    return $data;
}


/** Псевдоним htmlspecialchars_decode(), действует и для массивов
 * @param array|string $data
 * @return    array|string
 */
function dehsch($data)
{
    $type = gettype($data);
    if ($type == 'string') {
        // Строка
        return htmlspecialchars_decode($data);
    } elseif ($type == 'array') {
        // Массив
        return array_map('dehsch', $data);
    }

    // Неподходящий тип данных
    return $data;
}


/** Аналог wordwrap() для UTF-8
 * Разделитель всегда пробел, длинные слова режутся
 * @param string $str
 * @param int    $width
 * @return mixed
 * @see        wordwrap()
 */
function mb_wordwrap($str, $width = 75)
{
    return preg_replace('/([^\s]{' . $width . '})/ius', '${1} ', $str);
}


/** Примитивное кодирование URL внешних ссылок
 * @param     $url
 * @param int $key
 * @return string
 */
function code_url($url, $key = 100)
{
    $len = strlen($url);
    $res = '';
    for ($i = 0; $i < $len; $i++) {
        $res .= chr(ord(substr($url, $i, 1)) + $key);
    }
    return '/goto.php?url=' . urlencode($res);
}

/** Примитивное декодирование URL внешних ссылок
 * @param     $url
 * @param int $key
 * @return string
 */
function encode_url($url, $key = 100)
{
    $len = strlen($url);
    $res = '';
    for ($i = 0; $i < $len; $i++) {
        $res .= chr(ord(substr($url, $i, 1)) - $key);
    }
    return $res;
}


// РАБОТА С ДАТАМИ

/** Русское название месяца по номеру
 * @param int $m
 * @param int $case 1 - именительный падеж, 2 - родительный падеж
 * @param int $first_char 0 - с маленькой буквы, 2 - с большой буквы
 * @return string
 */
$_MONTHS1 = array(
    1 => 'Январь',
    2 => 'Февраль',
    3 => 'Март',
    4 => 'Апрель',
    5 => 'Май',
    6 => 'Июнь',
    7 => 'Июль',
    8 => 'Август',
    9 => 'Сентябрь',
    10 => 'Октябрь',
    11 => 'Ноябрь',
    12 => 'Декабрь'
);
$_MONTHS2 = array(
    1 => 'Января',
    2 => 'Февраля',
    3 => 'Марта',
    4 => 'Апреля',
    5 => 'Мая',
    6 => 'Июня',
    7 => 'Июля',
    8 => 'Августа',
    9 => 'Сентября',
    10 => 'Октября',
    11 => 'Ноября',
    12 => 'Декабря'
);
foreach ($_MONTHS1 as $n => $v) {
    $_MONTHS1[$n] = array(str_to_low($v), $v);
}
foreach ($_MONTHS2 as $n => $v) {
    $_MONTHS2[$n] = array(str_to_low($v), $v);
}
function get_month_name($m = 1, $case = 1, $first_char = 0)
{
    global $_MONTHS1, $_MONTHS2;
    if ($m != abs(round($m))) {
        return '';
    }
    if ($m > 12 || $m < 1) {
        return '';
    }

    return $case == 1 ? $_MONTHS1[$m][$first_char] : $_MONTHS2[$m][$first_char];
}


/** Аналог date(), только название месяца будет по-русски
 * @param      $format
 * @param      $ts
 * @param bool $use_words Нужно ли сегодняшнюю и вчерашнюю дату заменить словами "Сегодня" или "Вчера"
 * @return mixed|string
 */
$_ENG_MNTHS = array(
    1 => 'Jan',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Apr',
    5 => 'May',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Aug',
    9 => 'Sep',
    10 => 'Oct',
    11 => 'Nov',
    12 => 'Dec'
);
$_D_TODAY = date('j M Y');
$_D_YESTERDAY = date('j M Y', time() - 86400);
function date_str($format, $ts = -1, $use_words = true)
{
    global $_MONTHS2, $_ENG_MNTHS, $_D_TODAY, $_D_YESTERDAY;

    $format = str_replace('d', 'j', $format);
    $format = str_replace('m', 'M', $format);
    if ($ts == -1) {
        $ts = time();
    }

    $res = date($format, $ts);

    if ($use_words) {
        $res = str_replace($_D_TODAY, 'Сегодня', $res);
        $res = str_replace($_D_YESTERDAY, 'Вчера', $res);
    }

    $mm = array();
    for ($i = 1; $i <= 12; $i++) {
        $mm[$i] = $_MONTHS2[$i][0];
    }
    $res = str_replace($_ENG_MNTHS, $mm, $res);
    return $res;
}


/** Преобразуем строку(например название товара, бренда) в строку для URL браузера
 * @param $title
 * @return string
 */
function stringToUrl($title)
{
    $title = strtolower(trans($title));
    $title = preg_replace('/[^a-z0-9]/isu', '-', $title);
    $title = preg_replace('/-{1,}/', '-', $title);
    $title = trim($title, '-');

    return $title;
}


/** Транслит
 * @param $st
 * @return string
 */
function trans(string $st): string
{
    $rus = 'абвгдежзийклмнопрстуфыэАБВГДЕЖЗИЙКЛМНОПРСТУФЫЭ';
    $eng = 'abvgdegziyklmnoprstufieABVGDEGZIYKLMNOPRSTUFIE';

    $len = mb_strlen($st);

    for ($i = 0; $i < $len; $i++) {
        $ch = mb_substr($st, $i, 1);
        $rusPos = mb_strpos($rus, $ch);
        if ($rusPos !== false) {
            $st = str_replace($ch, mb_substr($eng, $rusPos, 1), $st);
        }
    }

    $st = strtr($st, array(
        'ё' => 'yo',
        'х' => 'h',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'shch',
        'ъ' => '',
        'ь' => '',
        'ю' => 'yu',
        'я' => 'ya',
        'Ё' => 'Yo',
        'Х' => 'H',
        'Ц' => 'Ts',
        'Ч' => 'Ch',
        'Ш' => 'Sh',
        'Щ' => 'Shch',
        'Ъ' => '',
        'Ь' => '',
        'Ю' => 'Yu',
        'Я' => 'Ya'
    ));
    return $st;
}


/** Аналог array_unique(), только здесь происходит сравнение serialize($elem1) == serialize($elem2)
 * В оригинале было так: (string) $elem1 === (string) $elem2
 * @param $arr
 * @return array
 */
function array_unique_extra($arr)
{
    $orig = $arr;
    foreach ($arr as &$a) {
        $a = serialize($a);
    }
    $arr = array_unique($arr);
    $keys = array_keys($arr);

    $result = array();
    foreach ($keys as $k) {
        $result[$k] = $orig[$k];
    }
    return $result;
}


function print_array($array)
{
    ob_start();
    print_r($array);
    $r = '<pre>' . ob_get_clean() . '</pre>';

    print $r;
}


function arrToStr($array)
{
    ob_start();
    print_r($array);
    return ob_get_clean();
}


// ф-ция для засечения времени в миллисекундах
$MTIME_START = array();
function mtime($id)
{
    global $MTIME_START;

    list($msec, $sec) = explode(' ', microtime());
    $res = $sec + $msec;

    if (isset($MTIME_START[$id]) && $MTIME_START[$id]) {
        $res = round($res - $MTIME_START[$id], 5);
        $MTIME_START[$id] = 0;

        return $res;
    } else {
        $MTIME_START[$id] = $res;
    }
    return 0;
}
