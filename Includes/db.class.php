<?php

/** КЛАСС для работы с БД MySQL
 * @author    Seka
 */

class MySQL
{
    /** Cсылка на коннект
     * @var resource
     */
    protected static $link;


    /** Псевдоним функции mysql_real_escape_string()
     * @param string $s
     * @param bool   $fulltext Нужно ли из строки исключить символы, которые при полнотекстовом
     * поиске могут восприниматься как логические операторы
     * @return string
     */
    static function mres($s, $fulltext = false)
    {
        self::connect();

        if (is_null($s)) {
            $s = '';
        }

        $s = iconv('UTF-8', 'UTF-8//IGNORE', $s);
        if ($fulltext) {
            $s = str_replace(array('+', '-', '<', '>', '(', ')', '~', '*', '"'), ' ', $s);
        }
        return self::real_escape_string($s, self::$link);
    }


    /** Форматирование даты для поля datetime в БД
     * @static
     * @param int|string $ts
     * @return    string
     */
    static function dateTime($ts = 0)
    {
        if (gettype($ts) == 'string') {
            $ts = strtotime($ts);
        } elseif (!$ts) {
            $ts = time();
        }
        return date('Y-m-d H:i:s', $ts);
    }

    /** TimeStamp из поля datetime
     * @static
     * @param string $dbDateTime
     * @return    int
     */
    static function fromDateTime($dbDateTime = '')
    {
        return strtotime($dbDateTime);
    }


    /** Форматирование даты для поля date в БД
     * @static
     * @param int|string $ts
     * @return    string
     */
    static function date($ts = 0)
    {
        if (gettype($ts) == 'string') {
            $ts = strtotime($ts);
        } elseif (!$ts) {
            $ts = time();
        }
        return date('Y-m-d', $ts);
    }

    /** TimeStamp из поля date
     * @static
     * @param string $dbDate
     * @return    int
     */
    static function fromDate($dbDate = '')
    {
        return intval(strtotime($dbDate));
    }


    /** Форматирование даты для поля time в БД
     * @static
     * @param int|string $ts
     * @return    string
     */
    static function time($ts = 0)
    {
        if (gettype($ts) == 'string') {
            $ts = strtotime($ts);
        } elseif (!$ts) {
            $ts = time();
        }
        return date('H:i:s', $ts);
    }

    /** TimeStamp из поля time
     * @static
     * @param string $dbTime
     * @return    int
     */
    static function fromTime($dbTime = '')
    {
        return strtotime($dbTime, 0);
    }


    /** Приводит число знаков после запятой к корректному количеству, отбрасывает ".000000"
     * @static
     * @param number $number
     * @return    number
     */
    static function decimal($number)
    {
        $number = trim($number);
        $number = str_replace(',', '.', $number);
        $number = number_format($number, DECIMAL, '.', '');

        while (mb_strpos($number, '.') !== false && in_array(
                mb_substr($number, mb_strlen($number) - 1, 1),
                array('0', '.')
            )) {
            $number = mb_substr($number, 0, mb_strlen($number) - 1);
        }

        return $number;
    }


    /**
     * @var bool
     */
    static $retryQuery = false;

    /** Обработка ошибки при выполнении запроса
     * @static
     * @param string $query Запрос, вызвавший ошибку
     * @param bool   $connectErr Если это ошибка коннекта
     * @return    null|array|bool|int        Если код ошибки 1194 (Table '...' is marked as crashed...),
     * то таблица чинится и возвращается результат повтора запроса
     * @see    MySQL::query()
     */
    protected static function halt($query = '', $connectErr = false)
    {
        self::connect();

        if (!$connectErr) {
            $eNum = self::errno(self::$link);
            $eMsg = self::error(self::$link);

            if ($eNum == 1194 || $eNum == 145 && !self::$retryQuery) {
                // Если ошибка 1194/145 (Table '...' is marked as crashed and should be repaired)
                self::$retryQuery = true;

                // Чиним таблицу
                preg_match('/\'(.*)\'/', $eMsg, $m);
                $table = $m[1];
                self::query('REPAIR TABLE `' . $table . '`');

                // Повторяем запрос
                return self::query($query, true);
            }

            if (!Config::$debug) {
                exit('MySQL error.');
            } else {
                $msg = '';
                if (trim($query) !== '') {
                    $msg .= $query . '<br><br>';
                }
                $msg .= 'MYSQL error ' . $eNum . ': ' . $eMsg;
                trigger_error($msg);
                exit;
            }
        } else {
            if (!Config::$debug) {
                exit('MySQL error.');
            } else {
                trigger_error(
                    'MYSQL connect error ' . self::connect_errno(self::$link) . ': ' . self::connect_error(self::$link)
                );
                exit;
            }
        }
    }


    /** Установка соединения с СУБД
     * @static
     * @return    bool
     */
    protected static function connect()
    {
        if (self::$link !== null) {
            return true;
        }

        @self::$link = self::m_connect(
            Config::$db['host'],
            Config::$db['user'],
            Config::$db['pass'],
            Config::$db['name']
        );
        if (!self::$link) {
            return self::halt('', true);
        }

        if (!self::set_charset('utf8', self::$link)) {
            return self::halt();
        }

        self::query('SET SQL_BIG_SELECTS = 1');

        return true;
    }


    static function reset()
    {
        self::m_close(self::$link);
        //var_dump(self::$link);
        self::$link = null;
        self::connect();
    }


    /** Выполнение запроса к БД
     * @static
     * @param string $query
     * @param bool   $retryOff Если запрос выполняется повтоно (из-за краша таблицы), то после успешного выполнения нужно снять отметку self::$retryQuery
     * @return    array|bool|int        Массив данных для SELECT, последний ID для INSERT, true/false для других запросов
     */
    static function query($query = '', $retryOff = false)
    {
        self::connect();

        $log = Config::$db['log'];

        if ($log) {
            mtime('query-time');
        }

        @$res = self::m_query($query, self::$link);
        //print $query . '<br><br>';

        if ($log) {
            file_put_contents(
                _ROOT . '/sql.log',
                mtime('query-time') . "\n" . $query . "\n\n",
                FILE_APPEND
            );
        }


        if ($res === false) {
            @self::free_result($res);
            return self::halt($query);
        }

        if ($retryOff) {
            self::$retryQuery = false;
        }

        if ($res === true) {
            @self::free_result($res);
            if (stripos($query, 'INSERT INTO') !== false) {
                return self::insert_id(self::$link);
            } else {
                return true;
            }
        }

        $arr = array();
        while ($str = self::fetch_array($res)) {
            $arr[] = $str;
        }
        @self::free_result($res);
        return $arr;
    }


    /** Возвращает значение глобальной системной переменной MySQL
     * @static
     * @param string $varName
     * @return    bool|string
     */
    static function getSystemVar($varName)
    {
        $value = self::query('SELECT @@' . $varName);
        if ($value) {
            $value = $value[0];
            $value = array_shift($value);
            return $value;
        } else {
            return false;
        }
    }


















    /**
     * КОПИИ ОСНОВНЫХ ФУНКЦИЙ В ДВУХ ВАРИАНТАХ: ДЛЯ MYSQL И MYSQLi
     */

    /**
     * @static
     * @param string   $unescaped_string
     * @param resource $link_identifier
     * @return string|void
     */
    protected static function real_escape_string($unescaped_string, $link_identifier = null)
    {
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_real_escape_string($link_identifier, $unescaped_string);
        } else {
            return mysql_real_escape_string($unescaped_string, $link_identifier);
        }
    }

    /**
     * @static
     * @param resource $link_identifier
     * @return int|void
     */
    protected static function errno($link_identifier = null)
    {
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_errno($link_identifier);
        } else {
            return mysql_errno($link_identifier);
        }
    }

    /**
     * @static
     * @param resource $link_identifier
     * @return int|void
     */
    protected static function connect_errno($link_identifier = null)
    {
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_connect_errno();
        } else {
            return mysql_errno($link_identifier);
        }
    }

    /**
     * @static
     * @param resource $link_identifier
     * @return string|void
     */
    protected static function error($link_identifier = null)
    {
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_error($link_identifier);
        } else {
            return mysql_error($link_identifier);
        }
    }

    /**
     * @static
     * @param resource $link_identifier
     * @return string|void
     */
    protected static function connect_error($link_identifier = null)
    {
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_connect_error();
        } else {
            return mysql_error($link_identifier);
        }
    }

    /**
     * @static
     * @param string $host
     * @param string $username
     * @param string $passwd
     * @param string $dbname
     * @return false|mysqli
     */
    protected static function m_connect($host = null, $username = null, $passwd = null, $dbname = null)
    {
        //exit("$host, $username, $passwd, $dbname");
        try {
            $conn = mysqli_connect($host, $username, $passwd, $dbname);
        } catch (\Exception $e) {
            exit($e->getMessage() . " #" . $e->getCode());
        }

        return $conn;

        /*
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_connect($host, $username, $passwd, $dbname);
        } else {
            $link = mysql_connect($host, $username, $passwd);
            mysql_select_db($dbname, $link);
            return $link;
        }
        */
    }

    protected static function m_close(&$link_identifier = null)
    {
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_close($link_identifier);
        } else {
            return mysql_close($link_identifier);
        }
    }


    /**
     * @static
     * @param string   $charset
     * @param resource $link_identifier
     * @return bool|void
     */
    protected static function set_charset($charset, $link_identifier = null)
    {
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_set_charset($link_identifier, $charset);
        } else {
            return mysql_set_charset($charset, $link_identifier);
        }
    }

    /**
     * @static
     * @param string   $query
     * @param resource $link_identifier
     * @return resource|void
     */
    protected static function m_query($query, $link_identifier = null)
    {
        try {
            if ($link_identifier) {
                $result = mysqli_query($link_identifier, $query);
            } else {
                $result = mysqli_query($query);
            }
        } catch (\Exception $e) {
            exit($e->getMessage() . "<br>" . $query);
        }

        return $result;

        /*
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {

        } else {
            return mysql_query($query, $link_identifier);
        }
        */
    }

    /**
     * @static
     * @param resource $link_identifier
     * @return int|string
     */
    protected static function insert_id($link_identifier = null): int|string
    {
        return mysqli_insert_id($link_identifier);
        /*
        if (Config::$db['lib'] == SQL_LIB_MYSQLi) {
            return mysqli_insert_id($link_identifier);
        } else {
            return mysql_insert_id($link_identifier);
        }
        */
    }

    /**
     * @static
     * @param resource $result
     * @param int      $result_type
     * @return array|void
     */
    protected static function fetch_array($result, $result_type = MYSQLI_BOTH)
    {
        return mysqli_fetch_assoc($result);
        //return mysqli_fetch_array($result, $result_type);
    }

    /**
     * @static
     * @param resource $result
     */
    protected static function free_result($result)
    {
        if ( is_bool($result)) {
            return;
        }
        mysqli_free_result($result);
    }
}
