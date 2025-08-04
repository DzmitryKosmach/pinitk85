<?php

require_once(dirname(__FILE__) . '/includes.php');

require_once _ROOT . '/Classes/CSRF.class.php';
$CSRF_TOKEN = new CSRF();
$CSRF_TOKEN->new();

// Определяем ID страницы
$oUrl = new Url();
$pageId = $oUrl->getPageId();

// Формируем страницу с соответствующим ID
$oPages = new Pages();
$oPages->make($pageId);
