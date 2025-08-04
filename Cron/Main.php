<?php

/**
 * Запускать раз в 55 минут
 */

require_once(dirname(dirname(__FILE__)) . '/includes.php');

// Отправляем письма из очереди рассылки
/*$oEmailQueue = new Email_Queue();
$oEmailQueue->send(55);*/

// Очистка устаревших логов поисковых запросов
$oSearchHistory = new Catalog_Search_History();
$oSearchHistory->clear();

?>
