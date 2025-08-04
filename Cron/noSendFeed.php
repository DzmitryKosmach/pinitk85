<?php

require_once dirname(dirname(__FILE__)) . "/Classes/Catalog/Yml/YandexFeed.php";

$feed = new YandexFeed();
$feed->setFilename('nosend.xml');
$feed->setInYMarket(0);
$feed->create();
