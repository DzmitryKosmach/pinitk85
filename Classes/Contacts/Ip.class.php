<?php

/**
 * IP адреса отправивших запрос на обр. звонок или сообщения через обратную связь
 *
 * @author    Seka
 */

class Contacts_Ip extends ExtDbList
{

    /**
     * @var string
     */
    static string $tab = 'contacts_ip';

    /**
     *
     */
    const TYPE_FEEDBACK = 'feedback';
    const TYPE_CALLBACK = 'callback';

    /**
     * Мин. интервал между повторными отправками
     */
    const MIN_INTERVAL = 300;

    /**
     * Проверяем, можно ли юзеру с текущим IP отправить сообщение через обратную связь
     * При проверке время последней попытки обновляется
     * @param string $reason Одна из констант Contacts_Feedback::REASON_...
     * @return    bool
     */
    function checkFeedback($reason)
    {
        return $this->check(self::TYPE_FEEDBACK, $reason);
    }


    /**
     * Проверяем, можно ли юзеру с текущим IP отправить заявку на обратный звонок
     * При проверке время последней попытки обновляется
     * @return    bool
     */
    function checkCallback()
    {
        return $this->check(self::TYPE_CALLBACK);
    }


    /**
     * @param string $type
     * @param string $feedbackReason
     * @return bool
     */
    protected function check($type, $feedbackReason = Contacts_Feedback::REASON_OTHER)
    {
        // Результат проверки кешируется, чтобы можно было без проблем выполнить её несколько раз в рамках одной задачи
        static $cache = array();

        if ($type !== self::TYPE_FEEDBACK && $type !== self::TYPE_CALLBACK) {
            $type = self::TYPE_FEEDBACK;
        }
        if (!isset(Contacts_Feedback::$reasons[$feedbackReason])) {
            $feedbackReason = Contacts_Feedback::REASON_OTHER;
        }

        if (isset($cache[$type][$feedbackReason])) {
            return $cache[$type][$feedbackReason];
        }

        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        $log = $this->getRow(
            '*',
            '`ip` = ' . $ip . ' AND `type` = \'' . MySQL::mres($type) . '\' AND `feedback_reason` = \'' . MySQL::mres(
                $feedbackReason
            ) . '\''
        );

        if (!$log) {
            $this->add(array(
                'ip' => $ip,
                'time' => MySQL::dateTime(),
                'type' => $type,
                'feedback_reason' => $feedbackReason
            ));

            $cache[$type][$feedbackReason] = true;

            return true;

        } else {
            if (MySQL::fromDateTime($log['time']) < (time() - self::MIN_INTERVAL)) {
                $this->upd(
                    $log['id'],
                    array(
                        'time' => MySQL::dateTime()
                    )
                );

                $cache[$type][$feedbackReason] = true;

                return true;
            } else {

                $cache[$type][$feedbackReason] = false;

                return !(APP_ENV === "prod");
            }
        }
    }
}
