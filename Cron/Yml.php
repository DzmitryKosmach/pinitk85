<?php

/**
 * Запускать раз в час
 */

require_once(dirname(dirname(__FILE__)) . '/includes.php');

mtime('yml');
Catalog_Yml::make();
print mtime('yml');

?>
