<?php

/**
 * Класс для отправки Email
 *
 * @author    Seka
 */

class Email
{
    /** Функция прямой отправки письма
     * @static
     * @param array|string $toEmail Один адрес получателя, или неск. адр. ч-з зпт или тчк-с-зпт, или массив адресов
     * @param string       $fromEmail Email отправителя
     * @param string       $fromName Имя отправителя
     * @param string       $subject
     * @param string       $bodyHTML
     * @param array        $attaches Приаттаченные файлы. Каждый элемент - один файл.
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
     * @return    array    Массив результатов (true/false) отправки письма для каждого получателя
     */
    static function send(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $bodyHTML,
        array $attaches = array()
    ): array
    {
        $fromEmail = !trim($fromEmail) ? 'noreply@mebelioni.ru' : trim($fromEmail);

        // Шифруем имя отправителя
        $fromName = trim($fromName) ? '=?UTF-8?B?' . base64_encode(trim($fromName)) . '?=' : 'Mebelioni Client';

        // Шифруем тему письма
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: ' . $fromName . ' <' . $fromEmail . '>';

        // Код, который идет ниже, некорректен для PHP 8 b хостинга.
        // Чтобы быстро закрыть отправку не читаемых писем, код упрощен.
        // К тому же никто в скриптах не использует отправку файлов.

/*
        // Формируем заголовок письма
        $boundary = '=_' . md5(uniqid(time()));        // Генерим boundary
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n";

        // Шифруем тело письма
        $bodyHTML =
            'This is a MIME encoded message.' . "\n\n" .
            '--' . $boundary . "\n" .
            'Content-Type: text/html; charset=utf-8' . "\n" .
            'Content-Transfer-Encoding: base64' . "\n" .
            'Content-Disposition: inline' . "\n\n" .
            chunk_split(base64_encode($bodyHTML), 76, "\n") . "\n\n";

        // Прикрепляем аттачи к телу письма
        foreach ($attaches as &$attachment) {
            if (isset($attachment['file'])) {
                // Передано имя файла на сервере
                if (!is_file($attachment['file'])) {
                    continue;
                }
                $attachment['bin'] = fgc($attachment['file']);    // Получаем содержимое файла

                // Определяем тип файла
                $attachment['type'] = getimagesize($attachment['file']);
                $attachment['type'] = $attachment['type'] ? $attachment['type']['mime'] : 'application/octet-stream';

                // Если не передано имя файла
                if (!$attachment['name']) {
                    $attachment['name'] = basename($attachment['file']);
                }
            }

            if (!$attachment['type']) {
                $attachment['type'] = 'application/octet-stream';
            }    // Тип файла по умолчанию (если не был передан)

            // Шифруем имя файла
            $attachment['name'] = '=?UTF-8?B?' . base64_encode($attachment['name']) . '?=';

            // Прикрепляем файл
            $bodyHTML .=
                '--' . $boundary . "\n" .
                'Content-Type: ' . $attachment['type'] . '; name="' . $attachment['name'] . '"' . "\n" .
                'Content-Transfer-Encoding: base64' . "\n" .
                'Content-Disposition: attachment; filename="' . $attachment['name'] . '"' . "\n\n" .
                chunk_split(base64_encode($attachment['bin']), 76, "\n") . "\n\n";
        }

        $bodyHTML .= '--' . $boundary . '--' . "\n";    // Добавляем ещё один boundary в конце тела письма
*/
        // Приводим список получателей к простому массиву
        if (!is_array($toEmail)) {
            if (strpos($toEmail, ',') !== false) {
                $toEmail = explode(',', $toEmail);
            } elseif (strpos($toEmail, ';') !== false) {
                $toEmail = explode(';', $toEmail);
            } else {
                $toEmail = array($toEmail);
            }
        }
        foreach ($toEmail as &$e) {
            $e = trim($e);
        }
        unset($e);

        // Отправляем письмо
        $result = array();

        if (Config::$email == SEND_SMTP) {
            // Отправка через внешний SMTP-сервер
            $result = self::smpt('zakaz@mebelioni.ru', $fromEmail, $subject, $headers, $bodyHTML);
        } else {
            // Стандартная отправка функцией mail()
            foreach ($toEmail as $to) {
                $result[] = mail($to, $subject, $bodyHTML, $headers);
            }
        }

        return $result;
    }


    /** Отправка письма через внешний SMTP-сервер
     * @static
     * @param array  $toEmails Массив адресов получателей
     * @param string $fromEmail Email и имя отправителя (=?UTF-8?B?USERNAME?= <EMAILADDR>)
     * @param string $subject Кодированный сабж письма (=?UTF-8?B?SUBJECT?=)
     * @param string $headers Заголовки письма через \n
     * @param string $bodyHTML
     * @return    array    Массив результатов (true/false) отправки письма для каждого получателя
     */
    static protected function smpt($toEmails, $fromEmail, $subject, $headers, $bodyHTML)
    {
        include_once(Config::path('external') . '/SMTP/smtp.php');
        include_once(Config::path('external') . '/SMTP/sasl.php');

        static $oSmtp;
        if ($oSmtp === null) {
            $oSmtp = new smtp_class;
        }

        $oSmtp->host_name = Config::$smtp['host'];
        $oSmtp->host_port = Config::$smtp['port'];
        $oSmtp->ssl = Config::$smtp['useSsl'];
        $oSmtp->start_tls = Config::$smtp['useTls'];
        $oSmtp->localhost = Config::$smtp['localhost'];
        $oSmtp->timeout = Config::$smtp['timeout'];
        $oSmtp->user = Config::$smtp['login'];
        $oSmtp->password = Config::$smtp['pass'];

        //$oSmtp->debug = 1; $oSmtp->html_debug = 1;

        $headers = explode("\n", $headers);
        //array_unshift($headers, 'Date: ' . strftime('%a, %d %b %Y %H:%M:%S %Z'));
        array_unshift($headers, 'Subject: ' . $subject);
        array_unshift($headers, 'To: ');
        array_unshift($headers, 'From: ' . $fromEmail);

        $result = array();

        foreach ($toEmails as $toEmail) {
            // Вставляем нужный адрес отправителя в заголовки
            $headers[1] = 'To: ' . $toEmail;
            $result[] = $oSmtp->SendMessage(Config::$smtp['login'], array($toEmail), $headers, $bodyHTML);
        }

        return $result;
    }
}
