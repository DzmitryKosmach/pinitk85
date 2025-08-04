<?php

require_once dirname(dirname(__FILE__)) . "/Classes/Catalog/Yml/YandexFeed.php";

$feed = new YandexFeed();
$feed->setFilename('market.xml');
$feed->setInYMarket(1);
$feed->create();
