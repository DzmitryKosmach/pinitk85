<?php
/**
 * !!! ЭТО НЕ CRON СКРИПТ !!!
 *
 * Данный скрипт нужен для того, чтобы найти файлы на диске и заполнить значение поля filename в таблице catalog_items
 *
 */

const SQL_LIB_MYSQLi = 2;
const SEND_MAIL = 1;
const SEND_SMTP = 2;

class ConfigBase
{
    public static array $db = [
        'log' => false,
        'lib' => SQL_LIB_MYSQLi,
        'host' => '',
        'user' => '',
        'pass' => '',
        'name' => ''
    ];
}
class UpdateFilenames {

    private MysqliDb $db;

    public function __construct()
    {
        $path = dirname(__FILE__);

        include_once $path . "/../config-app.php";
        include_once $path . '/../config-' . APP_ENV . '.inc.php';

        require_once "Includes/MysqliDb.php";
        $this->db = new MysqliDb (Config::$db['host'], Config::$db['user'], Config::$db['pass'], Config::$db['name']);

        $this->run();
    }

    public function run(): void
    {
        $limit = 100;
        $offset = 0;
        $end = false;

        while (!$end) {
            $ids = $this->db
                ->orderBy('id')
                ->get('catalog_items', [$offset, $limit], ["id"]);

            foreach ($ids as $id) {

            }
        }

    }
}

$updateFilenames = new UpdateFilenames();
