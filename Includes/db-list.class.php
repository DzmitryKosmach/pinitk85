<?php

/** КЛАСС для работы со списками данных из БД
 * @author    Seka
 */

class DbList
{
    /** Текущая таблица БД
     * @var string
     */
    static string $tab = '';

    /** Ключевое поле текущей таблицы (primary key)
     * @var string
     */
    static string $idFld = 'id';

    // Свойства класса для вывода результатов

    /** Массив с результатом выборки из таблицы
     * @var array
     */
    var array $arr = [];

    /** Нулевой элемент массива $arr
     * @var bool|array
     */
    var bool|array $nul = false;

    /** Длина массива $arr
     * @var int
     */
    var int $len = 0;

    private array $tables = [];

    // Возможные варианты вставки данных в таблицу
    const INSERT = 'INSERT';

    const REPLACE = 'REPLACE';


    /** Спец. слова и спец. символы, используемые в блоке имён столбцов в запросе SELECT     *
     * @var array
     * @see    DbList::prepareFlds()
     */
    static $selectSpecWords = array(
        ' ',
        '.',
        ',',
        '(',
        ')',
        '+',
        '-',
        '/',
        '*',
        '=',
        'STRAIGHT_JOIN',
        'SQL_SMALL_RESULT',
        'SQL_BIG_RESULT',
        'SQL_BUFFER_RESULT',
        'SQL_CACHE',
        'SQL_NO_CACHE',
        'SQL_CALC_FOUND_ROWS',
        'HIGH_PRIORITY',
        'DISTINCT',
        'DISTINCTROW',
        'ALL',
        'AS'
    );


    /** Копия функции MySQL:query()
     * @param string $query
     * @return    array|bool|int
     */
    function query($query = '')
    {
        return MySQL::query($query);
    }

    public static function setTable($table)
    {
        self::$tab = $table;
    }

    /** Получение данных из БД
     * @param string     $fields Требуемые поля через запятую, например: 'name, date, text'; '*' - все поля; '' (пустая строка) - тоже, что и 'id'
     * @param string     $cond Условие поиска, без слова WHERE
     * @param string     $order Сортировка результата. Или просто поле, например 'date', или поле и доп. параметры (или неск. полей) - тогда поле надо писать с опострофами: '`date` DESC, `name`'
     * @param int|string $limit Ограничение количества результатов. Либо просто число, либо строка вида '10, 100' (100 записей, начиная с 10-й). Слово 'LIMIT' не нужно!
     * @param string     $joins Блок с JOIN'ами, идёт после FROM table
     * @param string     $group Поле, по которому выборку следует сгруппировать (GROUP BY ...)
     * @return    array
     */
    public function get(
        string $fields = '',
        string $cond = '',
        string $order = '`id` ASC',
        int|string $limit = 0,
        string $joins = '',
        string $group = ''): array
    {
        $fields = self::prepareFlds(
            $fields,
            $this->idFld(),
            $this->tab(),
            trim($joins)
        );

        $cond = self::prerareCond($cond);
        $order = self::prepareOrder($order, $this->idFld());
        $limit = self::prepareLimit($limit);
        $group = self::prepareGroupBy($group, $this->tab(), trim($joins));
        //**************

        // формируем запрос
        $query = 'SELECT ' . $fields . ' FROM `' . $this->tab() . '` ' .
            $joins . ' ' .
            $cond .
            $group .
            ' ORDER BY ' . $order .
            $limit;

        //dp($query);
        //print $query . '<br><br>';
        //**************

        // Выполняем запрос
        $this->arr = $this->query($query);
        $this->len = count($this->arr);
        $this->nul = $this->len ? $this->arr[0] : false;

        return $this->arr;
    }

    /* **************************************************************************************** */
    /* ********************************* Изменение таблицы ************************************ */
    /* **************************************************************************************** */

    /**
     * Удаление записи из БД
     * Для перехвата этого метода нужно переопределять delCond()
     * @param    $id
     * @return    bool
     * @see DbList::delCond()
     */
    final function del($id = -1)
    {
        return $this->delCond('`' . $this->idFld() . '` = ' . intval($id));
    }


    /** Множественное удаление по условию
     * Этот метод можно переопределять для перехвата удаления данных из БД
     * @param string $cond
     * @return    bool
     */
    function delCond($cond = '')
    {
        // формируем строку с условием запроса
        $cond = self::prerareCond($cond);
        return $this->query('DELETE FROM `' . $this->tab() . '` ' . $cond);
    }


    /** Обновление записи в БД (UPDATE) по id (primary key)
     * Для перехвата этого метода нужно переопределять updCond()
     * @param int   $id
     * @param array $updArr Ассоциативный массив (поле => значение)
     * @see DbList::updCond()
     */
    final function upd($id = -1, $updArr = array())
    {
        $this->updCond('`' . $this->idFld() . '` = ' . intval($id), $updArr);
    }


    /** Множественное обновление записей в БД по условию
     * Этот метод можно переопределять для перехвата изменения данных в БД
     * @param string $cond
     * @param array  $updArr Ассоциативный массив (поле => значение)
     */
    function updCond($cond = '', $updArr = array())
    {
        // формируем строку с условием запроса
        $cond = self::prerareCond($cond);

        $updStr = array();
        foreach ($updArr as $fld => $val) {
            $updStr[] = '`' . $fld . '` = \'' . MySQL::mres($val) . '\'';
        }
        //print 'UPDATE `' . $this->tab() . '` SET ' . implode(', ', $updStr) . ' ' . $cond . '<br><br>'; exit;
        $this->query('UPDATE `' . $this->tab() . '` SET ' . implode(', ', $updStr) . ' ' . $cond);
    }


    /** Добавление одной записи в БД
     * Для перехвата этого метода нужно переопределять addArr()
     * @param array  $data
     * @param string $method
     * @return    int
     * @see DbList::addArr()
     */
    final function add($data = array(), $method = self::INSERT)
    {
        return $this->addArr(array($data), $method);
    }


    /** Добавление множества записей в БД
     * Этот метод можно переопределять для перехвата добавления данных в БД
     * @param array  $data Массив ассоциативных массивов
     * @param string $method Метод вставки данных в БД: self::INSERT или self::REPLACE
     * @return    int                    ID последнего из списка вставленных элементов
     */
    function addArr($data = array(), $method = self::INSERT)
    {
        if ($method != self::INSERT && $method != self::REPLACE) {
            trigger_error('Incorrect value of $method in DbList::addArr()', E_USER_WARNING);
            exit;
        }

        if (!count($data)) {
            return 0;
        }

        $arr = array_keys($data);
        $fields = array_keys($data[array_shift($arr)]);

        $values = array();
        foreach ($data as $d) {
            $v = array();
            foreach ($fields as $f) {
                $val = isset($d[$f]) ? $d[$f] : '';
                $v[] = MySQL::mres($val);
            }
            $values[] = '\'' . implode('\', \'', $v) . '\'';
        }

        return $this->query(
            $method . ' INTO `' . $this->tab() . '`
			(`' . implode('`, `', $fields) . '`)
			VALUES
			(' . implode('), (', $values) . ')
		'
        );
    }



    /* **************************************************************************************** */
    /* ********************************* Служебные функции ************************************ */
    /* **************************************************************************************** */

    public function setTab(string $tab): void
    {
        self::$tab = $tab;
    }

    /** Получаем имя таблицы текущего класса
     * @return    string
     */
    public function tab(): string
    {
        $className = get_class($this);
        if (isset($this->tables[$className])) {
            self::$tab = $this->tables[$className];
            return $this->tables[$className];
        }

        $obj = new $className;
        self::$tab = isset($obj::$tab) ? $obj::$tab : "";
        $this->tables[$className] = self::$tab;

        return self::$tab;
    }


    /** Получаем idFld текущего класса
     * @return    string
     */
    function idFld()
    {
        static $idFld = null;
        if ($idFld === null) {
            // Для экономии памяти значение вычисляется только один раз
            $className = get_class($this);
            /*$func = create_function(
                '',
                'return isset(' . $className . '::$idFld) ? ' . $className . '::$idFld : false;'
            );*/
            $idFld = isset($className::$idFld) ? $className::$idFld : false;
        }
        return $idFld;
    }


    /** Формируем строку с условием запроса
     * @static
     * @param string $cond
     * @return    string
     */
    static function prerareCond($cond = '')
    {
        if ($cond) {
            $cond = 'WHERE ' . $cond;
        }
        return $cond;
    }


    /** Обрабатываем список полей
     * @static
     * @param string $fields
     * @param string $idFld
     * @param string $tabName Имя таблицы. Его необходимо указать, если $hasJoins !== false
     * @param bool   $hasJoins Значение, отличное от false считается положительным: в запросе будут JOIN'ы и
     * к именам столбцов нужно добавить имя таблицы (`таблица`.`поле`).
     * В сложных случаях, если столбец содержит:
     *  - функции [MIN(`price`)];
     *  - формулы [`price` / 2];
     *  - алиасы [MIN(`price`) AS `min_price`];
     *  - условия [IF(`a`, 1, 2)];
     *  - вложенные запросы [..., (SELECT ...), ...]
     *  - ключевые слова [DISTINCT `name`];
     * имена таблиц здесь добавлены не будут, это необходимо предусмотреть.
     * @return    string
     * @see    DbList::$selectSpecWords
     * ВНИМАНИЕ! Ключевые слова должны быть в верхнем регистре! Это нужно для корректного их распознавания, а также для красоты и порядка!
     */
    static function prepareFlds($fields, $idFld = 'id', $tabName = '', $hasJoins = false)
    {
        // Если !$fields, то вписываем туда одно поле - id
        if (!$fields || (is_array($fields) && !count($fields))) {
            $fields = $idFld;
        }

        if (!is_array($fields)) {
            // Заменяем запятые внутри скобок на ^$^$^, чтобы explode на них не сработал
            $fields = preg_replace(
                '/\(((?>[^()]+)|(?R))*\)/i',
                str_replace(",", "^$^$^", '$0'),
                $fields
            );

            // Разделяем поля на массив
            $fields = is_null($fields) || !strlen($fields) ? [] : explode(',', $fields);
        }

        // Обрабатываем имена полей
        foreach ($fields as &$f) {
            $f = trim($f);
            $f = str_replace('^$^$^', ',', $f); // Возвращаем запятые
            foreach (self::$selectSpecWords as $w) {
                // Если в имени поля есть ключевое слово, алиас, точка, запятая, скобки или знаки арифметических операций,
                // то это поле не обрабатывается
                if (strpos($f, $w) !== false) {
                    continue 2;
                }
            }
            if ($hasJoins !== false) {
                // В запросе есть JOIN'ы: добавляем к именам столбцов имя таблицы
                $f = str_replace('`', '', $f);
                if ($f === '*') {
                    $f = '`' . $tabName . '`.*';
                } else {
                    $f = '`' . $tabName . '`.`' . trim($f) . '`';
                }
            } else {
                // Просто оборачиваем поле в опострофы
                if (strpos($f, '`') === false && trim($f) !== '*') {
                    $f = '`' . $f . '`';
                }
            }
        }
        unset($f);

        unset($idFld, $tabName, $hasJoins, $w);

        return implode(', ', $fields);
    }


    /** Формируем строку с критерием и направлением сортировки результатов
     * @static
     * @param string $order
     * @param string $idFld
     * @return    string
     */
    static function prepareOrder($order, $idFld = 'id')
    {
        if (!$order) {
            $order = '`id` ASC';
        }
        if (
            strpos($order, ' ASC') === false &&
            mb_stripos($order, ' DESC') === false &&
            mb_stripos($order, '(') === false &&
            mb_stripos($order, ')') === false
        ) {
            if (strpos($order, '`') === false) {
                $order = '`' . $order . '` ASC';
            } else {
                $order = $order . ' ASC';
            }
        }

        if ($idFld != 'id') {
            if (strpos($order, '`id`') !== false) {
                $order = str_replace('`id`', '`' . $idFld . '`', $order);
            }
            if (strpos($order, 'id ') !== false) {
                $order = str_replace('id ', $idFld . ' ', $order);
            }
        }

        return $order;
    }


    /** Предел количества результатов
     * @static
     * @param int $limit
     * @return    string
     */
    static function prepareLimit($limit)
    {
        return $limit !== 0 ? ' LIMIT ' . $limit : '';
    }


    /** Поле группировки GROUP BY ...
     * @static
     * @param string $group
     * @param string $tabName
     * @param bool   $hasJoins
     * @return    string
     */
    static function prepareGroupBy($group, $tabName = '', $hasJoins = false)
    {
        $group = trim($group);

        if (!$group) {
            return '';
        } else {
            if ($hasJoins !== false && $tabName) {
                // В запросе есть JOIN'ы

                foreach (self::$selectSpecWords as $w) {
                    // Если в имени поля есть ключевое слово, алиас, точка, запятая, скобки или знаки арифметических операций,
                    // то это поле не обрабатывается
                    if (strpos($group, $w) !== false) {
                        return ' GROUP BY ' . $group;
                    }
                }

                // Добавляем к именам столбцов имя таблицы
                $group = str_replace('`', '', $group);
                $group = '`' . $tabName . '`.`' . $group . '`';
            } else {
                if (strpos($group, '`') === false) {
                    $group = '`' . $group . '`';
                }
            }

            return ' GROUP BY ' . $group;
        }
    }
}


/** Расширение для класса DbList
 * Все эти методы можно было объявить и в DbList, но тогда их получается слишком много и становится сложно ориентироваться
 * @author    Seka
 */
class ExtDbList extends DbList
{
    public function __call($methodName, $arguments) {
        if (! property_exists($this, $methodName)) {
            throw new Exception("Bad Method Name Exception: $methodName");
        }

        return $this->$methodName;
    }

    public function table(string $table): void
    {
        self::$tab = $table;
    }

    /** Получаем ВСЕ поля по запросу
     * @param string $cond
     * @param string $order
     * @param int    $limit
     * @param string $joins
     * @param string $group
     * @return    array
     * @see DbList::get()
     */
    function getFull($cond = '', $order = '`id` ASC', $limit = 0, $joins = '', $group = '')
    {
        return $this->get('*', $cond, $order, $limit, $joins, $group);
    }


    /** Обычный запрос get(), только массив с результатом имеет ключи, соответствующие ID элементов
     * @param string      $fields
     * @param string      $cond
     * @param string      $order
     * @param int         $limit
     * @param string      $joins
     * @param string      $group
     * @param string|bool $key
     * @return    array
     * @see DbList::get()
     */
    function getWhtKeys($fields = '', $cond = '', $order = '`id` ASC', $limit = 0, $joins = '', $group = '', $key = false)
    {
        $this->get($fields, $cond, $order, $limit, $joins, $group);
        if (!$this->len) {
            return array();
        }

        if (count($this->nul) < 2) {
            trigger_error('Field set in <b>getWhtKeys()</b> must contain at least two fields.', E_USER_WARNING);
        }

        if (!$key) {
            $key = $this->idFld();
        }
        if ($this->len && !isset($this->nul[$key])) {
            trigger_error(
                'The result obtained in <b>getWhtKeys()</b> doesn\'t contains the key fields.',
                E_USER_WARNING
            );
        }

        $whtKeys = array();
        foreach ($this->arr as &$line) {
            $keyVal = $line[$key];
            $whtKeys[$keyVal] = $line;
        }
        $this->arr = $whtKeys;
        return $this->arr;
    }


    /** Получаем результат с двумя полями в каждой записи и возвращаем их в виде массива вида array(fld1 => fld2)
     * @param string $fields
     * @param string $cond
     * @param string $order
     * @param int    $limit
     * @param string $joins
     * @param string $group
     * @return array
     * @see DbList::get()
     */
    function getHash($fields = '', $cond = '', $order = '`id` ASC', $limit = 0, $joins = '', $group = '')
    {
        $this->get($fields, $cond, $order, $limit, $joins, $group);

        if (!$this->len) {
            return array();
        }

        if (count($this->nul) != 2) {
            //trigger_error('Field set in <b>getHash()</b> must contain exactly two fields.', E_USER_WARNING);
        }

        $res = array();
        $keys = array_keys($this->nul);
        foreach ($this->arr as $line) {
            $res[$line[$keys[0]]] = $line[$keys[1]];
        }

        return $res;
    }


    /** Получаем результат с одним полем в виде одномерного массива
     * @param string $fld
     * @param string $cond
     * @param string $order
     * @param int    $limit
     * @param string $joins
     * @param string $group
     * @return    array
     * @see DbList::get()
     */
    function getCol($fld = '', $cond = '', $order = '`id` ASC', $limit = 0, $joins = '', $group = '')
    {
        $this->get($fld, $cond, $order, $limit, $joins, $group);
        if (!$this->len) {
            return array();
        }

        if (count($this->nul) != 1) {
            trigger_error('Field set in <b>getCol()</b> must contain exactly one field.', E_USER_WARNING);
        }

        $res = array();
        $keys = array_keys($this->nul);
        foreach ($this->arr as $line) {
            $res[] = $line[$keys[0]];
        }

        return $res;
    }


    /** Получаем результат в виде единственной строки из таблицы (или false)
     * @param string $fld
     * @param string $cond
     * @param string $order
     * @param string $joins
     * @param string $group
     * @return    array|bool
     * @see DbList::get()
     */
    public function getRow(
        string $fld = '',
        string $cond = '',
        string $order = '`id` ASC',
        string $joins = '',
        string $group = ''): array|bool
    {
        $this->get($fld, $cond, $order, 1, $joins, $group);

        return $this->len ? $this->nul : false;
    }


    /** Получаем значение из единственной ячейки
     * @param string $fld
     * @param string $cond
     * @param string $order
     * @param string $joins
     * @param string $group
     * @return    mixed
     * @see DbList::get()
     */
    public function getCell(
        string $fld = '',
        string $cond = '',
        string $order = '`id` ASC',
        string $joins = '',
        string $group = '' ): mixed
    {
        $this->get($fld, $cond, $order, 1, $joins, $group);

        if (!$this->len) {
            return false;
        }

        if (count($this->nul) != 1) {
            //trigger_error('Field set in <b>getCell()</b> must contain exactly one field.', E_USER_WARNING);
        }

        $keys = array_keys($this->nul);

        return $this->nul[$keys[0]];
    }


    /** Получаем к-во записей по запросу
     * @param string $cond
     * @param string $joins
     * @param string $group
     * @return int
     */
    public function getCount(string $cond = '', string $joins = '', string $group = ''): int
    {
        $cond = self::prerareCond($cond);
        $group = self::prepareGroupBy($group, $this->tab(), trim($joins));
        $query = 'SELECT COUNT(*) AS `_count_` FROM `' . $this->tab() . '` ' . $joins . ' ' . $cond . $group;
        $res = $this->query($query);

        if ($group) {
            return count($res);
        } else {
            return count($res) ? intval($res[0]['_count_']) : 0;
        }
    }


    /** Запрос get() с разбиением на страницы
     * @param int    $pgNum
     * @param int    $pgSize
     * @param string $fields
     * @param string $cond
     * @param string $order
     * @param string $joins
     * @param string $group
     * @return array
     * @see DbList::get()
     */
    function getByPage(
        int $pgNum = 1,
        int $pgSize = 100,
        string $fields = '',
        string $cond = '',
        string $order = '`id` ASC',
        string $joins = '',
        string $group = ''
    ): array
    {
        $total = $this->getCount($cond, $joins, $group); // Общее к-во элементов

        // Возвращаем список данных и блок переключателя страниц
        return [
            0 => $this->get($fields, $cond, $order, self::byPageLim($total, $pgNum, $pgSize), $joins, $group),
            1 => Pages::toggle($total, $pgNum, $pgSize),
            2 => $pgNum, // Возвращаем возможно изменённый номер страницы
            3 => $total
        ];
    }


    /** Запрос getFull() с разбиением на страницы
     * @param int    $pgNum
     * @param int    $pgSize
     * @param string $cond
     * @param string $order
     * @param string $joins
     * @param string $group
     * @return array
     * @see ExtDbList::getFull()
     */
    function fullByPage($pgNum = 1, $pgSize = 100, $cond = '', $order = '`id` ASC', $joins = '', $group = '')
    {
        $total = $this->getCount($cond, $joins, $group); // Общее к-во элементов

        // Возвращаем список данных и блок переключателя страниц
        return array(
            0 => $this->getFull($cond, $order, self::byPageLim($total, $pgNum, $pgSize), $joins, $group),
            1 => Pages::toggle($total, $pgNum, $pgSize),
            2 => $pgNum,
            3 => $total
        );
    }

    /* **************************************************************************************** */
    /* ********************************* Служебные функции ************************************ */
    /* **************************************************************************************** */

    /** LIMIT для разбиения запроса на страницы
     * @static
     * @param int $totalCnt
     * @param int $pgNum
     * @param int $pgSize
     * @return    string
     */
    static function byPageLim($totalCnt, &$pgNum = 1, $pgSize = 100)
    {
        $pgCount = ceil($totalCnt / $pgSize); // Количество страниц

        // Проверяем корректость номера текущей страницы
        if ($pgNum > $pgCount) {
            $pgNum = $pgCount;
        }
        if ($pgNum < 1) {
            $pgNum = 1;
        }

        // Определяем параметр LIMIT для запроса
        return ($pgSize * ($pgNum - 1)) . ', ' . $pgSize;
    }


    /** Устанавливает значение поля `order` равным полю `id` для строк, где `order` == 0
     * @param string $orderField
     */
    function setOrderValue($orderField = 'order')
    {
        $this->query(
            'UPDATE `' . $this->tab() . '` SET `' . $orderField . '` = `' . $this->idFld(
            ) . '` WHERE `' . $orderField . '` = 0'
        );
    }


    /* **************************************************************************************** */
    /* *************** Сохранение / Удаление картинок, связанных с записями в БД ************** */
    /* **************************************************************************************** */


    /** Для использования методов для быстрой работы с картинками, нужно в классе-потомке
     * определить $imagePath относительно папки Config::path('images')
     * Например, так: '/Photos/'
     * @var string
     */
    static $imagePath = '';
    static $imageExt = 'jpg';


    /**
     * Получаем значение $imagePath для текущего класса (потомка)
     * @return bool|string
     */
    protected function imagePath(): bool|string
    {
        $className = get_class($this);

        try {
            $prop = new ReflectionProperty($className, 'imagePath');

            if (!$prop->isStatic()) {
                return false;
            }

            $obj = new $className;
            return $obj::$imagePath;

        } catch (\Exception $e) {
            $path = false;
        }

        return $path;
    }


    /**
     * Определяем расширения файла, связанного с записью
     * @param int $id
     * @return    bool|string
     */
    public function imageExt(int $id): bool|string
    {
        $path = $this->imagePath();

        if (!$path) {
            return false;
        }

        $id = intval($id);
        if (!$id) {
            return false;
        }

        foreach (Images::$extToMime as $ext => $mime) {
            $file = Config::path('images') . $path . $id . '.' . $ext;

            if (is_file($file)) {
                return $ext;
            }
        }

        return false;
    }


    /** Сохранение картинки, связанной с записью в БД
     * @param int    $id
     * @param string $fileName Имя временного файла на сервере
     * @param string $fileRealName Имя файла
     * @return    bool
     */
    function imageSave(int $id, string $fileName, string $fileRealName=""): bool
    {
        $path = $this->imagePath();
        if (!$path) {
            return false;
        }

        $id = intval($id);
        if (!$id) {
            return false;
        }

        if (!is_file($fileName)) {
            return false;
        }

        $this->imageDel($id);

        $mime = getimagesize($fileName);
        $mime = $mime['mime'];
        $ext = array_search($mime, Images::$extToMime);
        if (!$ext) {
            $ext = 'jpg';
        }

        $oImages = new Images();
        $oImages->convert(
            $fileName,
            Config::path('images') . $path . $id . '.' . $ext,
            $ext
        );

        self::$imageExt = $ext;

        return true;
    }


    /** Удаление картинки, связанной с записью в БД
     * @param array|int $id Один или несколько ID (в массиве)
     * @return    bool
     */
    function imageDel($id)
    {
        if (is_array($id)) {
            $res = array();
            foreach ($id as $id1) {
                $res[] = $this->imageDel($id1);
            }
            return $res;
        }

        $path = $this->imagePath();
        if (!$path) {
            return false;
        }
        $id = intval($id);
        if (!$id) {
            return false;
        }

        $ext = $this->imageExt($id);
        if (!$ext) {
            return false;
        }
        $file = Config::path('images') . $path . $id . '.' . $ext;
        if (is_file($file)) {
            unlink($file);
        }

        $oImages = new Images();
        $oImages->cacheClean(true, Config::path('images') . $path . $id . '_*');

        return true;
    }


    /** Привязывает к выборке данных из БД расширения соответствующих файлов
     * @param array $data
     * @return    array
     */
    public function imageExtToData(array $data): array
    {
        $idFld = $this->idFld();

        if (isset($data[$idFld])) {
            // Если передана одна строка
            $data = $this->imageExtToData(array($data));
            return $data[0];
        }

        foreach ($data as $i=>$d) {

            //$d['_img_ext'] = isset($d[$idFld]) ? $this->imageExt($d[$idFld]) : false;
            //dp($d);

            if (isset($d[$idFld])) {
                //dp($this->imageExt($d[$idFld]));
                $data[$i]['_img_ext'] = $this->imageExt($d[$idFld]);
            } else {
                $data[$i]['_img_ext'] = false;
            }
        }

        return $data;
    }

    /**
     * Вернет определенное при загрузке расширение файла
     *
     * @return string
     */
    public function getExt(): string
    {
        return self::$imageExt;
    }
}
