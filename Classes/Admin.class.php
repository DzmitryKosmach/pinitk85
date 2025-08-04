<?php

/**
 * Класс, который наследуется всеми модулями админки и содержит основные общие для них методы и свойства
 *
 * @author    Seka
 */

abstract class Admin
{
    /**
     * Список существующих разделов админки
     */
    const CONTENT = 1;
    const CATALOG = 2;
    const ORDERS = 3;
    const CLIENTS = 4;
    const REVIEWS = 5;
    const SETTINGS = 6;
    const DEALERS = 7;

    /** Сюда в классе-наследнике нужно записать имя класса, связанного с таблицей, из которой выводятся данные в данном модуле
     * @var string
     */
    var $mainClass;

    /** Сюда в классе-наследнике нужно вписать требуемое для конкретного раздела админке право доступа
     * И затем его нужно проверить методом checkRights()
     * @see    Admin::checkRights();
     * @var int
     */
    var $rights = 0;

    /**
     * @var bool
     */
    static $admin = true;


    /**
     * Главный метод модуля
     * @abstract
     * @param array $pageInf
     */
    abstract static function main(array &$pageInf = []);


    function checkRights()
    {
        if (!$this->rights) {
            // Права не указаны, раздел доступен всем
            return;
        }

        if (!Administrators::checkRights($this->rights)) {
            if ($_SERVER['HTTP_REFERER']) {
                $back = '<br><a href="' . $_SERVER['HTTP_REFERER'] . '">Назад</a>';
            } else {
                $back = '';
            }
            exit('У вас нет прав для доступа к данному разделу панели управления!' . $back);
        }
    }


    /** Удаление записи
     * @param $iId
     */
    function delItem($iId)
    {
        exit('Method delItem() should be implemented in the descendant class of class Admin');
    }


    /**
     * Проверка и выполнение типичных get-операций
     */
    final function getOperations()
    {
        $_GET['act'] = $_GET['act'] ?? '';
        $_GET['msg'] = $_GET['msg'] ?? '';

        // Удаление строки таблицы
        if (trim($_GET['act']) == 'del') {
            $this->delItem($_GET['n']);
        }

        // Установка tinyint опций
        if (trim($_GET['act']) == 'setopt') {
            $this->switchOpt($_GET['itemid'], $_GET['opt'], trim($_GET['msg']));
        }

        // Сортировка записей
        if (trim($_GET['act']) == 'dragsort') {
            $this->dragSort($_GET['order'], $_GET['direct']);
        }
    }


    /** Переключение опций типа tinyint
     * @param int    $itemId
     * @param string $optName
     * @param string $nojsMsg
     */
    final function switchOpt($itemId = 0, $optName = '', $nojsMsg = '')
    {
        $itemId = intval($itemId);
        $optName = trim($optName);

        $o = new $this->mainClass;

        // Получаем прежнее значение опции
        $oldVal = $o->getCell($optName, '`id` = ' . $itemId);
        if ($oldVal === false) {
            print 0;
            exit;
        }

        // Сохраняем новое значение
        $newVal = $oldVal ? 0 : 1;
        $o->upd($itemId, array($optName => $newVal));

        if (abs($_GET['js'])) {
            print $newVal;
        } else {
            if (trim($nojsMsg) === '') {
                $nojsMsg = 'Опция <b>' . $optName . '</b> ' . ($newVal ? 'включена' : 'выключена');
            }
            Pages::flash($nojsMsg);
        }
        exit;
    }


    final function dragSort($order)
    {
        $order = trim($order);
        $dir = strtoupper(trim($_GET['direct']));
        if (!in_array($dir, array('ASC', 'DESC'))) {
            $dir = 'ASC';
        }

        // Новый порядок следования объектов
        $newIds = array();
        foreach (explode(',', $order) as $id) {
            if (intval($id)) {
                $newIds[] = intval($id);
            }
        }
        if (count($newIds) < 2) {
            exit('Requires at least two objects');
        }

        // Получаем записи с нужными ID и их ORDER'ы
        $o = new $this->mainClass();
        $oldIds2Order = $o->getHash('id, order', '`id` IN (' . implode(',', $newIds) . ')', 'order');

        // Проверяем, чтобы все ID из нового порядка были выбраны из БД (иначе удаляем ID из нового порядка)
        $newIds1 = array();
        foreach ($newIds as $id) {
            if (isset($oldIds2Order[$id])) {
                $newIds1[] = $id;
            }
        }
        $newIds = $newIds1;

        // Разворачиваем список, если элементы должны выводиться в обратном порядке
        if ($dir == 'DESC') {
            $newIds = array_reverse($newIds);
        }

        // Сортируем элементы
        $newN = 0;
        foreach ($oldIds2Order as $oldId => $ord) {
            $newId = $newIds[$newN];
            if ($newId != $oldId) {
                $o->upd($newId, array('order' => $ord));
            }
            $newN++;
        }

        exit;
    }
}
