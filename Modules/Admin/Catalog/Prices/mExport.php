<?php

/** Админка: экспорт прайса
 * @author    Seka
 */

class mExport extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var int
     */
    static $output = OUTPUT_DEFAULT;

    /**
     * @var int
     */
    var $rights = Administrators::R_CATALOG;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        if (intval($_GET['calc'])) {
            // Запрос на подсчёт к-ва товаров по заданным параметрам
            return self::calc(
                intval($_GET['category_id']),
                intval($_GET['supplier_id']),
                intval($_GET['series_id']),
                intval($_GET['include_out_of_production'] ?? 0)
            );
        }

        if (!empty($_GET['job_status'])) {
            self::$output = OUTPUT_FRAME;
            $jobId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['job_status']);
            $job = self::readExportJob($jobId);
            if (!$job) {
                $job = array(
                    'status' => 'error',
                    'message' => 'Задача экспорта не найдена'
                );
            }
            return json_encode($job, JSON_UNESCAPED_UNICODE);
        }

        $exportJobId = '';
        if (!empty($_GET['job'])) {
            $exportJobId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['job']);
        }

        // Серии (по категориям и по поставщикам)
        $oSeries = new Catalog_Series();
        $series = $oSeries->get(
            'id, name, category_id, supplier_id',
            '',
            'order'
        );

        $seriesByCat = array();        // $seriesByCat[category_id][supplier_id][series_id] = series_name
        foreach ($series as $s) {
            $cat = $s['category_id'];
            $sup = $s['supplier_id'];
            if (!isset($seriesByCat[$cat])) {
                $seriesByCat[$cat] = array();
            }
            if (!isset($seriesByCat[$cat][$sup])) {
                $seriesByCat[$cat][$sup] = array();
            }
            $seriesByCat[$cat][$sup][$s['id']] = $s['name'];
        }

        // Конечные категории каталога
        $oCategories = new Catalog_Categories();
        $catsIds = $oCategories->getFinishIds(0);

        // Поставщики
        $oSuppliers = new Catalog_Suppliers();
        $suppliers = $oSuppliers->getHash(
            'id, name',
            '',
            'name'
        );


        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'seriesByCat' => $seriesByCat,
            'catsIds' => $catsIds,
            'suppliers' => $suppliers,
            'exportJobId' => $exportJobId,
            'exportStatusUrl' => Url::a('admin-catalog-export')
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;

        if (isset($_SESSION['export-options']) && is_array($_SESSION['export-options'])) {
            $init = $_SESSION['export-options'];
            $init['items'] = 1;
            $frm->setInit($init);
        }

        return $frm->run('mExport::export');
    }


    /**
     * @static
     * @param int $categoryId
     * @param int $supplierId
     * @param int $seriesId
     * @return    int
     */
    static function calc($categoryId, $supplierId, $seriesId, $includeOutOfProduction = 0)
    {
        self::$output = OUTPUT_FRAME;

        $cond = self::seriesSearchCond($categoryId, $supplierId, $seriesId, $includeOutOfProduction);
        $oSeries = new Catalog_Series();
        $seriesIds = $oSeries->getCol('id', $cond);
        $seriesCnt = count($seriesIds);

        if ($seriesCnt) {
            $oItems = new Catalog_Items();
            $itemsCnt = $oItems->getCount('`series_id` IN (' . implode(',', $seriesIds) . ')');
        } else {
            $itemsCnt = 0;
        }

        return json_encode(array(
            'series' => $seriesCnt,
            'items' => intval($itemsCnt)
        ));
    }

    /**
     * @param $initData
     * @param $newData
     */
    static function export($initData, $newData)
    {
        Catalog_Prices_Export::prepareRuntime();

        $_SESSION['export-options'] = array(
            'options' => isset($newData['options']) ? $newData['options'] : array(),
            'items' => intval($newData['items'] ?? 0) ? 1 : 0,
            'series-extra-formula' => intval($newData['series-extra-formula'] ?? 0) ? 1 : 0,
            'include_out_of_production' => intval($newData['include_out_of_production'] ?? 0) ? 1 : 0
        );

        $optSeries = (isset($newData['options']['series']) && is_array($newData['options']['series']))
            ? $newData['options']['series']
            : array();
        $optSeries[] = Catalog_Prices::FLD_SERIES_ID;
        $optSeries[] = Catalog_Prices::FLD_SERIES_CATEGORY;
        $optSeries[] = Catalog_Prices::FLD_SERIES_NAME;
        $seriesExtraFormula = !empty($newData['series-extra-formula']);

        if (!empty($newData['items'])) {
            $optItems = (isset($newData['options']['items']) && is_array($newData['options']['items']))
                ? $newData['options']['items']
                : array();
            $optItems[] = Catalog_Prices::FLD_ITEMS_ID;
            $optItems[] = Catalog_Prices::FLD_ITEMS_NAME;
            $optItems[] = Catalog_Prices::FLD_ITEMS_ART;
        } else {
            $optItems = array();
        }

        $q = self::seriesSearchCond(
            intval($newData['category_id'] ?? 0),
            intval($newData['supplier_id'] ?? 0),
            intval($newData['series_id'] ?? 0),
            intval($newData['include_out_of_production'] ?? 0)
        );

        $params = array(
            'query' => $q,
            'optSeries' => array_values($optSeries),
            'optItems' => array_values($optItems),
            'seriesExtraFormula' => $seriesExtraFormula
        );

        $jobId = bin2hex(random_bytes(8));
        self::ensureExportJobDir();
        self::writeExportJob($jobId, array(
            'status' => 'queued',
            'message' => 'Экспорт поставлен в очередь',
            'created_at' => time()
        ));
        file_put_contents(
            self::exportPayloadFile($jobId),
            json_encode($params, JSON_UNESCAPED_UNICODE)
        );

        if (!self::startExportWorker($jobId)) {
            Pages::flash('Не удалось запустить фоновый экспорт. Проверьте PHP CLI.', true);
            return;
        }

        header('Location: ' . Url::a('admin-catalog-export') . '?job=' . urlencode($jobId));
        exit;
    }

    /**
     * Условие выборки серий для подсчёта и экспорта.
     */
    private static function seriesSearchCond($categoryId, $supplierId, $seriesId, $includeOutOfProduction = 0): string
    {
        $categoryId = intval($categoryId);
        $supplierId = intval($supplierId);
        $seriesId = intval($seriesId);
        $q = array();

        if (!intval($includeOutOfProduction)) {
            $q[] = '`out_of_production` = 0';
        }

        if ($categoryId) {
            $oCategories = new Catalog_Categories();
            $ids = $oCategories->getFinishIds($categoryId);
            $ids[] = $categoryId;
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (count($ids) === 1) {
                $q[] = '`category_id` = ' . $ids[0];
            } elseif (count($ids)) {
                $q[] = '`category_id` IN (' . implode(',', $ids) . ')';
            }
        }
        if ($supplierId) {
            $q[] = '`supplier_id` = ' . $supplierId;
        }
        if ($seriesId) {
            $q[] = '`id` = ' . $seriesId;
        }

        return implode(' AND ', $q);
    }

    private static function exportJobDir(): string
    {
        return _ROOT . '/tmp/export-jobs';
    }

    private static function exportJobFile($jobId): string
    {
        return self::exportJobDir() . '/' . $jobId . '.json';
    }

    private static function exportPayloadFile($jobId): string
    {
        return self::exportJobDir() . '/' . $jobId . '.payload.json';
    }

    private static function ensureExportJobDir(): void
    {
        $dir = self::exportJobDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    private static function readExportJob($jobId)
    {
        $file = self::exportJobFile($jobId);
        if ($jobId === '' || !is_file($file)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private static function writeExportJob($jobId, array $data): void
    {
        file_put_contents(self::exportJobFile($jobId), json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private static function phpCliBinary(): string
    {
        $candidates = array();
        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            $dir = dirname(PHP_BINARY);
            $candidates[] = $dir . DIRECTORY_SEPARATOR . 'php';
            $candidates[] = $dir . DIRECTORY_SEPARATOR . 'php.exe';
            $candidates[] = PHP_BINARY;
        }
        $candidates[] = '/usr/bin/php';
        $candidates[] = '/usr/local/bin/php';
        $candidates[] = 'php';
        foreach ($candidates as $bin) {
            if ($bin === 'php') {
                return $bin;
            }
            if (!is_file($bin)) {
                continue;
            }
            $base = strtolower(basename($bin));
            if (strpos($base, 'php-fpm') !== false || strpos($base, 'php-cgi') !== false) {
                continue;
            }
            return $bin;
        }
        return 'php';
    }

    private static function startExportWorker($jobId): bool
    {
        $script = _ROOT . '/cli/export_prices_worker.php';
        if (!is_file($script)) {
            return false;
        }

        $php = self::phpCliBinary();
        $log = self::exportJobDir() . '/' . $jobId . '.log';
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobId);

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                pclose(popen('start /B "" ' . $cmd . ' > ' . escapeshellarg($log) . ' 2>&1', 'r'));
                return true;
            }
            $full = $cmd . ' > ' . escapeshellarg($log) . ' 2>&1 < /dev/null &';
            if (function_exists('exec')) {
                exec($full);
                return true;
            }
            if (function_exists('proc_open')) {
                $proc = proc_open($full, array(), $pipes);
                if (is_resource($proc)) {
                    proc_close($proc);
                    return true;
                }
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }
}
