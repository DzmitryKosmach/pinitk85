<?php

/**
 * Шаблоны Email
 *
 * @author    Seka
 */

class Email_Tpl extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'email_tpl';

    /**
     * Сюда запоминаются полученные в методе label() из БД шаблоны, чтобы не делать это многократно
     * @var array
     */
    static $tplTmp = array();

    /** Возвращает параметры шаблона по его метке
     * @static
     * @param string $l
     * @return    array|bool
     */
    static function label($l)
    {
        if (!isset($tplTmp[$l])) {
            static $oSelf;
            if ($oSelf == null) {
                $oSelf = new self;
            }
            $tplTmp[$l] = $oSelf->getRow('subj, from, from_name, body', '`label` = \'' . MySQL::mres($l) . '\'');
        }
        return $tplTmp[$l];
    }


    /** Формируем готовое письмо на основе шаблона и данных для него
     * @static
     * @param string $label
     * @param array  $data
     * @return    array|bool
     */
    protected static function make($label, $data = array())
    {
        $tpl = self::label($label);
        if (!$tpl) {
            return false;
        }

        $tpl['subj'] = str_replace(array('[[', ']]'), array('<?=', '?>'), $tpl['subj']);
        $tpl['body'] = str_replace(array('[[', ']]'), array('<?=', '?>'), $tpl['body']);
        $tpl['subj'] = pattExeP($tpl['subj'], $data);
        $tpl['body'] = pattExeP($tpl['body'], $data);

        return $tpl;
    }


    /** Отправка письма из шаблона
     * @static
     * @param string $label
     * @param string $to
     * @param array  $data
     * @param array  $attaches Приаттаченные файлы. Каждый элемент - один файл.
     * Файл описывается массивом следующего вида:
     * array(
     *        'name'    => 'Имя файла, желательно латиницей',
     *        'file'    => 'Путь к файлу на сервере'
     * )
     * Либо вот так:
     * array(
     *        'name'    => 'Имя файла, желательно латиницей',
     *        'type'    => 'MIME-тип файла',
     *        'bin'    => 'Содержимое файла'
     * )
     * @return    array|bool
     */
    static function send($label, $to, $data = array(), $attaches = array())
    {
        $mail = self::make($label, $data);

        if (!$mail) {
            return false;
        }

        return Email::send(
            $to,
            $mail['from'] ? $mail['from'] : 'zakaz@mebelioni.ru',
            isset($mail['from_name']) && trim($mail['from_name']) ? $mail['from_name'] : 'Обратная связь',
            $mail['subj'],
            $mail['body'],
            $attaches
        );
    }


    /** Записывает письмо в очередь рассылки
     * @static
     * @param string $label
     * @param string $to
     * @param array  $data
     * @return bool|int
     */
    static function toQueue($label, $to, $data = array())
    {
        $mail = self::make($label, $data);

        if (!$mail) {
            return false;
        }

        $mail['to'] = $to;

        static $oQueue;
        if (!$oQueue) {
            $oQueue = new Email_Queue();
        }

        return $oQueue->add($mail);
    }
}
