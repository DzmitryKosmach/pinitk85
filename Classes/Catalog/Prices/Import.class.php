<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xls;

/**
 * Импорт прайсов
 *
 * @author    Seka
 */

class Catalog_Prices_Import extends Catalog_Prices
{

    const SESS_KEY = 'price-import';

    /**
     * Проверка файла, загруженного через форму
     * @param array $fileArr Что-то вроде $_FILES['file']
     * @return    bool|string
     */
    function checkFile(array $fileArr): bool|string
    {
        $fileName = trim($fileArr['name']);

        if ($fileName === '') {
            return 'Загрузите файл с прайсом!';
        }

        $e = explode('.', $fileName);
        $ext = mb_strtolower(trim(array_pop($e)));
        if ($ext !== 'xls' && $ext !== 'xlsx') {
            return 'Загрузите файл в формате Excel!';
        }

        $oReader = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileArr['tmp_name']);

        if (!$oReader) {
            return 'Загруженный файл не удаётся прочитать как Excel файл! Вероятно, файл был повреждён, либо ваша версия Excel не подходит для импорта прайсов.';
        }

        return true;
    }


    /**
     * Обработка файла и получение из него различий по сериям и товарам, кот. необходимо внести в БД
     * Полученные данные запоминаются в сессию
     * @param string $file Путь к файлу ($_FILES['file']['tmp_name'])
     */
    function loadData($file)
    {
        // Парсим файл
        list($series, $items) = $this->parse($file);

        // Определяем различия в данных из файала и в БД
        list($updSeries, $updItemsDiscount) = $this->diffSeries($series);
        $updItems = $this->diffItems($items);

        // Скидка, установленная для серии приоритетнее скидки товара
        foreach ($updItemsDiscount as $itemId => $d) {
            if (!isset($updItems[$itemId])) {
                $updItems[$itemId] = array();
            }
            $updItems[$itemId]['discount'] = $d;
        }

        /*print_array($updSeries);
        print_array($updItemsDiscount);
        print_array($updItems);
        exit;*/

        // Сохраняем различия файла и БД в сессию
        $_SESSION[self::SESS_KEY] = array($updSeries, $updItems);
    }


    /** Есть ли в сессии данные из файла для сохранения в БД
     * @return bool
     */
    function isDataLoaded()
    {
        return is_array($_SESSION[self::SESS_KEY]);
    }


    /**
     * Удаление данных для сохранения в БД из сесии
     */
    function clearDataLoaded()
    {
        unset($_SESSION[self::SESS_KEY]);
    }


    /** Возвращаем все данные для обновления (для предпросмотра изменений)
     * @return array
     */
    function getDataLoaded()
    {
        return $_SESSION[self::SESS_KEY];
    }


    /**
     * Сохраняем изменения в БД
     * @return bool
     */
    function saveData2DB()
    {
        if (!self::isDataLoaded()) {
            return false;
        }

        list($updSeries, $updItems) = $_SESSION[self::SESS_KEY];
        //print_array($updSeries); print_array($updItems); exit;
        self::clearDataLoaded();

        $oSeries = new Catalog_Series();
        $oSeriesOptions = new Catalog_Series_Options();
        $oItems = new Catalog_Items();
        $oItemsGroups = new Catalog_Items_Groups();
        $oItems2Materials = new Catalog_Items_2Materials();

        // Получаем названия групп товаров (по сериям)
        $item2series = array();
        $groupsNames = array();
        if (count($updItems)) {
            $item2series = $oItems->getHash(
                'id, series_id',
                '`id` IN (' . implode(',', array_keys($updItems)) . ')'
            );
            if (count($item2series)) {
                $series2cat = $oSeries->getHash(
                    'id, category_id',
                    '`id` IN (' . implode(',', array_unique($item2series)) . ')'
                );
                $groupsByCats = array();
                foreach (array_unique($series2cat) as $cId) {
                    $groupsByCats[$cId] = $oItemsGroups->getHash(
                        'id, name',
                        '`category_id` = ' . $cId,
                        'order'
                    );
                }
                foreach ($series2cat as $sId => $cId) {
                    $groupsNames[$sId] = $groupsByCats[$cId];
                }
            }
        }
        //print_array($groupsNames); exit;

        // Серии
        foreach ($updSeries as $sId => $upd) {
            if (isset($upd['options'])) {
                $oSeriesOptions->delCond('`series_id` = ' . $sId);
                foreach ($upd['options'] as $o) {
                    $n = trim($o['name']);
                    $v = trim($o['value']);
                    if ($n !== '' && $v !== '') {
                        $oSeriesOptions->add(array(
                            'series_id' => $sId,
                            'name' => $n,
                            'value' => $v
                        ));
                    }
                }
                unset($upd['options']);

                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_SERIES_CHANGE, $sId);
            }

            if (count($upd)) {
                // update
                $oSeries->upd($sId, $upd);

                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_SERIES_CHANGE, $sId);
            }
        }

        // Товары
        foreach ($updItems as $iId => $upd) {
            $sId = isset($item2series[$iId]) ? $item2series[$iId] : 0;

            if (isset($upd['group'])) {
                // Определяем ID группы товаров
                $gName = trim($upd['group']);
                $gId = 0;
                if (isset($groupsNames[$sId]) && in_array($gName, $groupsNames[$sId])) {
                    $gId = array_search($gName, $groupsNames[$sId]);
                }/*else{
					// Нужно создать группу товаров
					$gId = $oItemsGroups->add(array(
						'name'		=> $gName,
						'series_id'	=> $sId
					));
				}*/
                $upd['group_id'] = $gId;
                unset($upd['group']);

                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_CHANGE, $sId);
            }

            if (isset($upd['materials'])) {
                // Сохраняем материалы и цены по ним
                $oItems2Materials->delCond('`item_id` = ' . $iId);
                foreach ($upd['materials'] as $mId => $m) {
                    $oItems2Materials->add(array(
                        'material_id' => $mId,
                        'item_id' => $iId,
                        'currency' => $m['currency'],
                        'price' => $m['price']
                    ));
                }
                unset($upd['materials']);

                // Автозаметка
                $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_PRICE, $sId);
            }

            if (isset($upd['name']) || isset($upd['art'])) {
                // Генерим новый URL для товара
                $upd['url'] = Catalog_Items::generateUrl(
                    $iId,
                    isset($upd['name']) ? $upd['name'] : '',
                    isset($upd['art']) ? $upd['art'] : ''
                );
            }

            if (count($upd)) {
                // update
                $oItems->upd($iId, $upd);

                // Автозаметки
                if (isset($upd['price']) || isset($upd['currency'])) {
                    $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_PRICE, $sId);
                }
                if (isset($upd['extra_charge'])) {
                    $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_EXTRA, $sId);
                }
                if (isset($upd['discount'])) {
                    $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_DISCOUNT, $sId);
                }
                unset($upd['price'], $upd['currency'], $upd['extra_charge'], $upd['discount']);
                if (count($upd)) {
                    $oSeries->makeNotes(Catalog_Series::NOTE_ITEM_CHANGE, $sId);
                }
            }
        }

        return true;
    }


    /**
     * Парсим XLS-файл и получаем данные о сериях и товарах в нём
     *
     * @param string $file
     * @return    array    array($series, $items)    Массивы с данными из файла
     */
    protected function parse(string $file): array
    {
        $oPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);

        $sheetNames = $oPHPExcel->getSheetNames();
        if (isset($sheetNames[0])) {
            $oPHPExcel->setActiveSheetIndexByName($sheetNames[0]);
        }

        $aSheet = $oPHPExcel->getActiveSheet();
        $rowsCnt = $aSheet->getHighestRow();

        $highestRow = $aSheet->getHighestDataRow();
        $highestColumn = $aSheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Определяем, с какого столбца начинается информация о товарах
        $itemsFirstCol = false;
        for ($row = 1; $row <= $highestRow; ++$row) {
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $value = $aSheet->getCell([$col, $row])->getValue();
                if ($value === self::$fldsSeries[self::FLD_ITEMS_ID]) {
                    $itemsFirstCol = $col;
                }
            }
        }

        // Определяем назначение колонок
        $colsSeries = [];
        $colsItems = [];

        for ($col = 1; $col <= $highestColumnIndex; ++$col) {
            $value = $aSheet->getCell([$col, 1])->getValue();

            if ($itemsFirstCol === false || $col < $itemsFirstCol) {
                // Левая половина (с сериями)
                $fld = array_search($value, self::$fldsSeries);
                if ($fld !== false) {
                    $colsSeries[$fld] = $col;
                }
            } else {
                // Правая половина (с товарами)
                $fld = array_search($value, self::$fldsItems);
                if ($fld !== false) {
                    $colsItems[$fld] = $col;
                }
            }
        }

        // Названия поставщиков
        $oSuppliers = new Catalog_Suppliers();
        $suppNames = $oSuppliers->getHash('id, name', '', 'name');

        // Получаем серии и товары из таблицы XLS
        $series = [];
        $items = [];
        $matCols = [];

        for ($row = 1; $row <= $highestRow; ++$row) {
            // Ищем серию в строке
            $sId = intval($aSheet->getCell([$colsSeries[self::FLD_SERIES_ID], $row])->getValue());
            if ($sId) {
                // Собираем данные о серии
                $sInfo = [];

                foreach ($colsSeries as $fld => $col) {
                    if ($fld === self::FLD_SERIES_CATEGORY) {
                        // Категория не может изменяться через импорт прайса, пропускаем эту колонку
                        continue;
                    } elseif ($fld === self::FLD_SERIES_SUPPLIER) {
                        // Определяем ID поставщика
                        $val = $aSheet->getCell([$col, $row])->getValue();
                        $val = !is_null($val) ? trim($val) : '';
                        $suppId = array_search($val, $suppNames);
                        if ($suppId) {
                            $sInfo['supplier_id'] = $suppId;
                        }
                    } elseif ($fld === self::FLD_SERIES_EXTRA) {
                        // Наценка
                        $val = $aSheet->getCell([$col, $row])->getValue();
                        $val = !is_null($val) ? abs(trim($val)) : '';
                        $val = Catalog::percent2num($val, Catalog::PC_INCREASE);
                        $sInfo['extra_charge'] = $val;
                    } elseif ($fld === self::FLD_SERIES_DISCOUNT) {
                        // Скидка, если она есть
                        $val = $aSheet->getCell([$col, $row])->getValue();
                        $val = !is_null($val) ? abs(trim($val)) : '';
                        if ($val && $val < 99) {
                            $val = Catalog::percent2num($val, Catalog::PC_DECREASE);
                            $sInfo['discount'] = $val;
                        }
                    } else {
                        // Простые текстовые поля - берём данные из ячейки "как есть"
                        $val = $aSheet->getCell([$col, $row])->getValue();
                        $sInfo[$fld] = !$val ? '' : $val;
                    }
                }

                // Опции серии
                if ($aSheet->getCell('B' . ($row + 1))->getValue() === 'Параметр'
                    && $aSheet->getCell('C' . ($row + 1))->getValue() === 'Значение') {
                    $options = [];
                    $rowOffset = 2;
                    while (1) {
                        $name = $aSheet->getCell('B' . ($row + $rowOffset))->getValue();
                        $val = $aSheet->getCell('C' . ($row + $rowOffset))->getValue();

                        $name = is_null($name) ? '' : $name;
                        $val = is_null($val) ? '' : $val;

                        if ($name === '' && $val === '') {
                            break;
                        }

                        $options[] = array(
                            'name' => $name,
                            'value' => $val
                        );
                        $rowOffset++;
                    }
                    $sInfo['options'] = $options;
                }

                $series[$sId] = $sInfo;
            }

            // Ищем товар в строке
            if ($itemsFirstCol === false) {
                continue;
            }

            $iId = intval($aSheet->getCell([$colsItems[self::FLD_ITEMS_ID], $row])->getValue());

            if ($iId) {
                // Собираем данные о товаре
                $iInfo = array();
                foreach ($colsItems as $fld => $col) {
                    if ($fld === self::FLD_ITEMS_PRICES) {
                        // Наценка
                        $val = abs(trim($aSheet->getCell([$col, $row])->getCalculatedValue()));
                        $val = Catalog::percent2num($val, Catalog::PC_INCREASE);
                        $iInfo['extra_charge'] = $val;
                    } elseif ($fld === self::FLD_ITEMS_DISCOUNT) {
                        // Скидка
                        $val = abs(trim($aSheet->getCell([$col, $row])->getCalculatedValue()));
                        if ($val > 99) {
                            $val = 0;
                        }
                        $val = Catalog::percent2num($val, Catalog::PC_DECREASE);
                        $iInfo['discount'] = $val;
                    } else {
                        // Простые текстовые поля - берём данные из ячейки "как есть"
                        $val = $aSheet->getCell([$col, $row])->getValue();
                        $val = is_null($val) ? '' : trim($val);
                        $iInfo[$fld] = $val;
                    }
                }

                if (isset($colsItems[self::FLD_ITEMS_PRICES])) {
                    // Цены на товар
                    $colIn = max($colsItems) + 1;
                    $colOut = $colIn + 1;
                    $in = $aSheet->getCell([$colIn, $row])->getCalculatedValue();
                    $in = is_null($in) || !trim($in) ? 0 : $in;
                    $in = str_replace(',', '.', $in);
                    $in = is_numeric($in) ? abs($in) : 0;
                    $iInfo['price_in'] = $in;

                    $out = $aSheet->getCell([$colOut, $row])->getCalculatedValue();
                    $out = is_null($out) || !trim($out) ? 0 : $out;
                    $out = str_replace(',', '.', $out);
                    $out = is_numeric($out) ? abs($out) : 0;
                    $iInfo['price_out'] = $out;

                    $iInfo['currency'] = strpos(
                        $aSheet->getStyle([$colIn, $row])->getNumberFormat()->getFormatCode(),
                        '$'
                    ) !== false ? Catalog::USD : Catalog::RUB;

                    // Материалы товара и цены по ним
                    if (count($matCols)) {
                        $materials = array();
                        foreach ($matCols as $matId => $col) {
                            $price = $aSheet->getCell([$col, $row])->getCalculatedValue();
                            if ($price !== '' && !is_null($price)) {
                                // Цена указана (хотя там, возможно, 0)
                                $price = abs(str_replace(',', '.', $price));
                                $currency = strpos(
                                    $aSheet->getStyle([$col, $row])->getNumberFormat()->getFormatCode(),
                                    '$'
                                ) !== false ? Catalog::USD : Catalog::RUB;

                                // Для дальнейшего сравнения очень важно:
                                // 1) Ключи массива - это ID материалов
                                // 2) Значения - это price и currency именно в таком порядке
                                $materials[$matId] = [
                                    'price' => $price,
                                    'currency' => $currency
                                ];
                            }
                        }

                        $iInfo['materials'] = $materials;
                    }
                }

                $items[$iId] = $iInfo;
            } else {
                // Ищем шапку над товарами, чтобы определить названия материалов, цены для которых указаны для всех последующих товаров (до новой шапки)
                if ($aSheet->getCell([$colsItems[self::FLD_ITEMS_ID], $row])->getValue() === self::$fldsItems[self::FLD_ITEMS_ID]) {

                    $col = max($colsItems) + 3;
                    $matCols = [];

                    while (1) {
                        $matId = $aSheet->getCell([$col, $row])->getValue();
                        if ($matId === '' || is_null($matId)) {
                            break;
                        }

                        $e = explode('(', trim($matId, ')'));
                        $matId = intval(array_pop($e));
                        if ($matId) {
                            $matCols[$matId] = $col;
                        }
                        $col += 2;
                    }
                }
            }
        }

        return array($series, $items);
    }


    /** Находим разницу в данных по сериям из файла и в БД, определяем, что нужно изменить в базе
     * @param array $series Массив в данными по сериям из файла (из метода parse())
     * @return    array    array($updSeries, $updItemsDiscount)
     * $updSeries: массив вида ($seriesId => array(данные для замещения в БД))
     * $updItemsDiscount: массив вида ($itemId => новове_значение_скидки)
     * @see    Catalog_Price_Import::parse()
     */
    protected function diffSeries($series)
    {
        if (!count($series)) {
            return array(array(), array());
        }

        $updSeries = array();
        $updItemsDiscount = array();

        $oSeries = new Catalog_Series();
        $oItems = new Catalog_Items();
        $oSeriesOptions = new Catalog_Series_Options();

        foreach ($series as $sId => $sInfo) {
            $sId = intval($sId);

            // Получаем данные из БД
            $seriesOld = $oSeries->getRow(
                'name, title, h1, dscr, kwrd, extra_charge, supplier_id',
                '`id` = ' . $sId
            );
            if (!$seriesOld) {
                continue;
            }
            $seriesOld['extra_charge'] = abs($seriesOld['extra_charge']);

            // Немного преобразуем данные из файла
            unset($sInfo['id']);
            if (isset($sInfo['extra_charge'])) {
                $sInfo['extra_charge'] = abs($sInfo['extra_charge']);
            }
            if (isset($sInfo['options'])) {
                $optionsNew = $sInfo['options'];
                unset($sInfo['options']);
            } else {
                $optionsNew = false;
            }

            // Сравниваем данные из файла с данными из БД
            $update = array();
            foreach ($sInfo as $fld => $val) {
                if (isset($seriesOld[$fld]) && trim((string)$val) !== trim((string)$seriesOld[$fld])) {
                    $update[$fld] = $val;
                }
            }
            if (count($update)) {
                $updSeries[$sId] = $update;
            }

            // Сравниваем опции серии
            if ($optionsNew) {
                $optionsOld = $oSeriesOptions->get(
                    'name, value',
                    '`series_id` = ' . $sId,
                    'order'
                );
                foreach ($optionsOld as &$o) {
                    unset($o['id']);
                }
                unset($o);
                if (serialize($optionsOld) != serialize($optionsNew)) {
                    $updSeries[$sId]['options'] = $optionsNew;
                }
            }

            if (isset($sInfo['discount'])) {
                // Устанавливаем скидку на товары серии
                $itemsIds = $oItems->getCol(
                    'id',
                    '`series_id` = ' . $sId . ' AND `discount` != ' . $sInfo['discount']
                );
                foreach ($itemsIds as $itemId) {
                    $updItemsDiscount[$itemId] = $sInfo['discount'];
                }
            }
        }

        return array($updSeries, $updItemsDiscount);
    }


    /** Находим разницу в данных по товарам из файла и в БД, определяем, что нужно изменить в базе
     * @param array $items Массив в данными по товарам из файла (из метода parse())
     * @return    array    массив вида ($itemId => array(данные для замещения в БД))
     * @see    Catalog_Price_Import::parse()
     */
    protected function diffItems($items)
    {
        if (!count($items)) {
            return array();
        }

        $oSeries = new Catalog_Series();
        $oItems = new Catalog_Items();

        // Получаем способ расчёта вход.цены/выход.цены/наценки для каждой серии
        $seriesIds = $oItems->getCol(
            'series_id',
            '`id` IN (' . implode(',', array_map('intval', array_keys($items))) . ')',
            '',
            0,
            '',
            '`series_id`'
        );
        if (count($seriesIds)) {
            $extraFormulas = $oSeries->getHash(
                'id, import_extra_formula',
                '`id` IN (' . implode(',', $seriesIds) . ')'
            );
        } else {
            $extraFormulas = array();
        }

        // Названия групп товаров (по сериям)
        $groupsNames = array();
        if (count($seriesIds)) {
            $oItemsGroups = new Catalog_Items_Groups();
            $series2cat = $oSeries->getHash(
                'id, category_id',
                '`id` IN (' . implode(',', $seriesIds) . ')'
            );
            $groupsByCats = array();
            foreach (array_unique($series2cat) as $cId) {
                $groupsByCats[$cId] = $oItemsGroups->getHash(
                    'id, name',
                    '`category_id` = ' . $cId,
                    'order'
                );
            }
            foreach ($series2cat as $sId => $cId) {
                $groupsNames[$sId] = $groupsByCats[$cId];
            }
        }

        // Материалы для всех товаров
        $matsByItems = array();
        if (count($items) && isset($items[array_shift(array_keys($items))]['materials'])) {
            $oItems2Materials = new Catalog_Items_2Materials();
            $tmp = $oItems2Materials->get(
                '*',
                '`item_id` IN (' . implode(',', array_keys($items)) . ')'
            );
            foreach ($tmp as $m) {
                // Для дальнейшего сравнения очень важно:
                // 1) Ключи массива - это ID материалов
                // 2) Значения - это price и currency именно в таком порядке
                $matsByItems[$m['item_id']][$m['material_id']] = array(
                    'price' => round(abs($m['price']), Catalog::PRICES_DECIMAL),
                    'currency' => $m['currency']
                );
            }
        }

        $updItems = array();

        foreach ($items as $iId => $iInfo) {
            $iId = intval($iId);

            // Получаем данные из БД
            $itemOld = $oItems->getRow(
                'name, text, art, size, volume, weight, group_id, series_id, currency, price, extra_charge, discount',
                '`id` = ' . $iId
            );
            if (!$itemOld) {
                continue;
            }
            $seriesId = $itemOld['series_id'];
            unset($itemOld['series_id']);

            $itemOld['volume'] = abs($itemOld['volume']);
            $itemOld['weight'] = abs($itemOld['weight']);
            $itemOld['price'] = round(abs($itemOld['price']), Catalog::PRICES_DECIMAL);
            $itemOld['extra_charge'] = abs($itemOld['extra_charge']);
            $itemOld['discount'] = abs($itemOld['discount']);

            $itemOld['group'] = isset($groupsNames[$seriesId][$itemOld['group_id']]) ? $groupsNames[$seriesId][$itemOld['group_id']] : '';
            unset($itemOld['group_id']);

            // Немного преобразуем данные из файла
            unset($iInfo['id']);
            if (isset($iInfo['volume'])) {
                $iInfo['volume'] = abs($iInfo['volume']);
            }
            if (isset($iInfo['weight'])) {
                $iInfo['weight'] = abs($iInfo['weight']);
            }
            if (isset($iInfo['extra_charge'])) {
                $iInfo['extra_charge'] = abs($iInfo['extra_charge']);
            }
            if (isset($iInfo['discount'])) {
                $iInfo['discount'] = abs($iInfo['discount']);
            }
            if (isset($iInfo['price_in'])) {
                $auCourse = Catalog_Series::usdCourse($seriesId);

                $iInfo['price_in'] = round(abs($iInfo['price_in']), Catalog::PRICES_DECIMAL);
                $iInfo['price_out'] = round(abs($iInfo['price_out']), Catalog::PRICES_DECIMAL);

                if (isset($extraFormulas[$seriesId]) && $extraFormulas[$seriesId] === Catalog_Series::IMPORT_FORMULA_EXTRA && $iInfo['price_in']) {
                    // Наценка зависит от вход. и выход. цен
                    $iInfo['extra_charge'] = abs(
                        round($iInfo['price_out'] / $iInfo['price_in'], Catalog::MULTIPLERS_DECIMAL)
                    );
                }
                $iInfo['price'] = $iInfo['price_in'];
                unset($iInfo['price_in'], $iInfo['price_out']);
            }

            if (isset($iInfo['materials'])) {
                $materialsNew = $iInfo['materials'];
                unset($iInfo['materials']);
            } else {
                $materialsNew = false;
            }

            // Сравниваем данные из файла с данными из БД
            $update = array();
            foreach ($iInfo as $fld => $val) {
                if (isset($itemOld[$fld]) && trim((string)$val) !== trim((string)$itemOld[$fld])) {
                    /*print $iId . '<br>';
                    print '\'' . $val . '\'' . '<br>';
                    print '\'' . $itemOld[$fld] . '\'' . '<br><br>';*/
                    $update[$fld] = $val;
                }
            }

            // Сравниваем материалы и цены на них
            if ($materialsNew) {
                $materialsNew = (array)$materialsNew;
                foreach ($materialsNew as &$m) {
                    $m['price'] = round(abs($m['price']), Catalog::PRICES_DECIMAL);
                }
                unset($m);

                $materialOld = isset($matsByItems[$iId]) ? $matsByItems[$iId] : array();
                ksort($materialsNew);
                ksort($materialOld);

                if (serialize($materialOld) != serialize($materialsNew)) {
                    //print serialize($materialOld) . '<br>' . serialize($materialsNew) . '<br><br>'; exit;
                    $update['materials'] = $materialsNew;
                }
            }

            if (count($update)) {
                $updItems[$iId] = $update;
            }
        }
        return $updItems;
    }
}
