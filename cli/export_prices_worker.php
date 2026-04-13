<?php

if (php_sapi_name() !== 'cli') {
    exit(1);
}

$jobId = isset($argv[1]) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $argv[1]) : '';
if ($jobId === '') {
    exit(1);
}

define('EXPORT_ROOT', dirname(__DIR__));
$_GET['no_session'] = 1;
require EXPORT_ROOT . '/includes.php';

/**
 * @param string $jobId
 * @return string
 */
function exportJobFile($jobId)
{
    return EXPORT_ROOT . '/tmp/export-jobs/' . $jobId . '.json';
}

/**
 * @param string $jobId
 * @return string
 */
function exportPayloadFile($jobId)
{
    return EXPORT_ROOT . '/tmp/export-jobs/' . $jobId . '.payload.json';
}

/**
 * @param string $jobId
 * @param array $data
 */
function saveExportJob($jobId, array $data)
{
    @file_put_contents(exportJobFile($jobId), json_encode($data, JSON_UNESCAPED_UNICODE));
}

$payloadFile = exportPayloadFile($jobId);
if (!is_file($payloadFile)) {
    saveExportJob($jobId, array(
        'status' => 'error',
        'message' => 'Не найден payload задачи',
        'finished_at' => time()
    ));
    exit(1);
}

$payloadRaw = @file_get_contents($payloadFile);
$params = json_decode($payloadRaw, true);
if (!is_array($params)) {
    saveExportJob($jobId, array(
        'status' => 'error',
        'message' => 'Некорректный payload задачи',
        'finished_at' => time()
    ));
    exit(1);
}

try {
    $oExport = new Catalog_Prices_Export();
    $filePath = $oExport->exportToFile(
        isset($params['query']) ? $params['query'] : '',
        isset($params['optSeries']) && is_array($params['optSeries']) ? $params['optSeries'] : array(),
        isset($params['optItems']) && is_array($params['optItems']) ? $params['optItems'] : array(),
        !empty($params['seriesExtraFormula'])
    );

    saveExportJob($jobId, array(
        'status' => 'done',
        'message' => 'Экспорт завершен',
        'file' => '/xls/' . basename($filePath),
        'finished_at' => time()
    ));
    @unlink($payloadFile);
} catch (Throwable $e) {
    saveExportJob($jobId, array(
        'status' => 'error',
        'message' => $e->getMessage(),
        'finished_at' => time()
    ));
    exit(1);
}

