<?php

/**
 * Длительность сессий администраторов
 *
 * @author    Seka
 */

class Administrators_Stat extends ExtDbList
{

    /**
     * @var string
     */
    static string $tab = 'administrators_stat';

    /**
     * Макс. период неактивности админа в течение одной сессии (20 минут)
     */
    const TIMEOUT = 1200;


    /**
     * @param int $adminId
     */
    function log($adminId)
    {
        $adminId = intval($adminId);

        $time = time();

        // Проверяем, попадает ли запись в последнюю сессию активности админа
        $logId = $this->getCell(
            'id',
            '`administrator_id` = ' . intval($adminId) . ' AND `time_end` > \'' . MySQL::dateTime(
                $time - self::TIMEOUT
            ) . '\''
        );
        if ($logId) {
            // Попадает - изменяем время окончания сессии на текущее
            $this->upd(
                $logId,
                array(
                    'time_end' => MySQL::dateTime($time)
                )
            );
        } else {
            // Не попадает - открываем новую сессию
            $this->add(array(
                'administrator_id' => $adminId,
                'ip' => ip2long($_SERVER['REMOTE_ADDR']),
                'time_start' => MySQL::dateTime($time),
                'time_end' => MySQL::dateTime($time)
            ));
        }
    }


    /** Рассчитываем длтельность ссесси и возвращает ответ в виде hh:mm:ss
     * @param int $timeStartTs timestamp начала сессии
     * @param int $timeEndTs timestamp конца
     * @return    string
     */
    static function sessionDuration($timeStartTs, $timeEndTs)
    {
        $duration = $timeEndTs - $timeStartTs;

        $h = floor($duration / 3600);
        $duration -= $h * 3600;
        $m = floor($duration / 60);
        $duration -= $m * 60;
        if ($m < 10) {
            $m = '0' . $m;
        }
        $s = $duration;
        if ($s < 10) {
            $s = '0' . $s;
        }

        return $h . ':' . $m . ':' . $s;
    }

    static function sessionDurationTotal(?int $duration)
    {
        if (is_null($duration)) {
            return '00:00:00';
        }

        $h = floor($duration / 3600);
        $duration -= $h * 3600;
        $m = floor($duration / 60);
        $duration -= $m * 60;
        if ($m < 10) {
            $m = '0' . $m;
        }
        $s = $duration;
        if ($s < 10) {
            $s = '0' . $s;
        }

        return $h . ':' . $m . ':' . $s;
    }
}
