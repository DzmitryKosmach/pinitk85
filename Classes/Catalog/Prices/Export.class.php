<?php

/**
 * Экспорт прайсов
 *
 * @author    Seka
 * @author    HeoH
 */

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Форматы ячеек
const FORMAT_CURRENCY_RUR = '#,##0.00" р."';
const FORMAT_CURRENCY_M3 = '#,##0.00_-"м³"';
const FORMAT_CURRENCY_KG = '#,##0.00_-"кг"';

class Catalog_Prices_Export extends Catalog_Prices
{

    /**
     * @var PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $aSheet;

    /**
     * Обозначения стилей ячеек
     */
    const STYLE_BOLD = 'bold';
    const STYLE_H1 = 'h1';
    const STYLE_H2 = 'h2';
    const STYLE_H3 = 'h3';
    const STYLE_LINK = 'link';
    const STYLE_M3 = 'm3';
    const STYLE_KG = 'kg';
    const STYLE_USD = 'usd';
    const STYLE_RUR = 'rur';
    const STYLE_PERC = 'perc';
    const STYLE_GREY = 'grey';
    const STYLE_GREEN = 'green';
    const STYLE_BORDER_RIGHT = 'brdr-right';

    /**
     * Ширина ячеек с параметрами серий
     * @var array
     */
    protected static $colsSeriesWidth = array(
        self::FLD_SERIES_ID => 8,
        self::FLD_SERIES_CATEGORY => 25,
        self::FLD_SERIES_SUPPLIER => 25,
        self::FLD_SERIES_NAME => 20,
        self::FLD_SERIES_EXTRA => 15,
        self::FLD_SERIES_DISCOUNT => 15,
        self::FLD_SERIES_TITLE => 25,
        self::FLD_SERIES_HEADER => 25,
        self::FLD_SERIES_DSCR => 25,
        self::FLD_SERIES_KWRD => 25
    );

    /**
     * Ширина ячеек с параметрами товаров
     * @var array
     */
    protected static $colsItemsWidth = array(
        self::FLD_ITEMS_ID => 8,
        self::FLD_ITEMS_GROUP => 25,
        self::FLD_ITEMS_NAME => 25,
        self::FLD_ITEMS_ART => 15,
        self::FLD_ITEMS_SIZE => 12,
        self::FLD_ITEMS_VOLUME => 12,
        self::FLD_ITEMS_WEIGHT => 12,
        self::FLD_ITEMS_DESCRIPTION => 25,
        self::FLD_ITEMS_PRICES => 15,    // Наценка
        self::FLD_ITEMS_DISCOUNT => 15
    );


    /**
     * Основной метод (точка входа): генерит XLS-прайс и отправляет на скачивание в браузер
     *
     * @param string $query Запрос для выборки серий
     * @param array  $optSeries Параметры выгрузки серий
     * @param array  $optItems Параметры выгрузки товаров
     * @param bool   $seriesExtraFormula Связать наценку серии с выходной ценой товаров формулой
     */
    function export(string $query, array $optSeries, array $optItems, bool $seriesExtraFormula = false): void
    {
        // Создаём XLS-файл
        $filename = 'mebelioni-' . date('Y-m-d-H-i-s') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $this->aSheet = $spreadsheet->getActiveSheet();
        $this->aSheet->setTitle('Лист1');

        // Заполняем файл данными
        $this->fillSeries($query, $optSeries, $optItems, $seriesExtraFormula);

        $writer = new Xlsx($spreadsheet);
        $writer->save(_ROOT . '/xls/' . $filename);

        // Отдаем файл пользователю в браузер
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header("Location: " . "/xls/" . $filename);
    }


    /**
     * Заполняем таблицу сериями (а также их характеристиками и товарами)
     *
     * @param string $query
     * @param array  $optSeries
     * @param array  $optItems false - товары не выгружаются
     * @param bool   $seriesExtraFormula
     * @return void
     */
    protected function fillSeries(
        string $query,
        array $optSeries,
        array $optItems,
        bool $seriesExtraFormula = false
    ): void {
        // Поставщики
        $oSuppliers = new Catalog_Suppliers();
        $suppNames = $oSuppliers->getHash('id, name', '', 'name');

        // Находим серии для экспорта
        $oSeries = new Catalog_Series();
        $series = $oSeries->get('*', $query, 'order');

        // Для каждой серии получаем все ID материалов и вычисляем их максимальное к-во среди всех серий
        $maxMaterialsCnt = 0;
        $oSeries2Materials = new Catalog_Series_2Materials();
        foreach ($series as &$s) {
            $s['materials'] = $oSeries2Materials->getCol(
                'material_id',
                '`series_id` = ' . $s['id']
            );
            $maxMaterialsCnt = max($maxMaterialsCnt, count($s['materials']));
        }
        unset($s);

        $oSeriesOptions = new Catalog_Series_Options();

        $row = 1;
        foreach ($series as $seriesInf) {
            $seriesLastRow = $row + 1;    // Строка, на которой заканчивается описание серии
            $col = 0;

            $seriesExtraCell = '';

            // Заполняем шапку серии и её основные параметры
            foreach (self::$fldsSeries as $fld => $fldName) {
                if (in_array($fld, $optSeries)) {
                    // Шапка
                    $cell = self::colN2C($col) . $row;
                    $this->aSheet->setCellValue(
                        $cell,
                        $fldName
                    );
                    $this->setCellStyle(
                        $cell,
                        self::STYLE_H1
                    );

                    // Значения
                    $cell = self::colN2C($col) . ($row + 1);
                    if (in_array($fld, array(
                        self::FLD_SERIES_ID,
                        self::FLD_SERIES_NAME,
                        self::FLD_SERIES_TITLE,
                        self::FLD_SERIES_HEADER,
                        self::FLD_SERIES_DSCR,
                        self::FLD_SERIES_KWRD
                    ))) {
                        // Простые поля: ID, название, тайтл и проч.
                        $this->aSheet->setCellValue(
                            $cell,
                            $seriesInf[$fld]
                        );
                    } elseif ($fld === self::FLD_SERIES_CATEGORY) {
                        // Категория
                        $this->aSheet->setCellValue(
                            $cell,
                            trim(Catalog_Categories::name($seriesInf['category_id']))
                        );
                    } elseif ($fld === self::FLD_SERIES_SUPPLIER) {
                        // Поставщик
                        $this->aSheet->setCellValue(
                            $cell,
                            !is_null($suppNames[$seriesInf['supplier_id']]) ? trim(
                                $suppNames[$seriesInf['supplier_id']]
                            ) : ''
                        );
                    } elseif ($fld === self::FLD_SERIES_EXTRA) {
                        // Наценка серии (на самом деле, наценка есть у каждого отдельного товара, а наценка серии - это способ задать массовую наценку)
                        $this->aSheet->setCellValue(
                            $cell,
                            Catalog::num2percent($seriesInf['extra_charge'], Catalog::PC_INCREASE)
                        );
                        $seriesExtraCell = $cell;

                        if ($seriesExtraFormula && $seriesInf['import_extra_formula'] !== Catalog_Series::IMPORT_FORMULA_EXTRA) {
                            // Наценка серии участвует в формулах для цен товаров
                            $this->setCellStyle($cell, self::STYLE_BOLD, self::STYLE_GREEN);
                        }
                    } elseif ($fld === self::FLD_SERIES_DISCOUNT) {
                        // Скидка серии: пусто, т.к. скидка - это свойство каждого отдельного товара
                        $this->aSheet->setCellValue(
                            $cell,
                            ''
                        );
                    }

                    $col++;
                }
            }

            if (in_array(self::FLD_SERIES_CHARACTERS, $optSeries)) {
                // Характеристики серии
                $options = $oSeriesOptions->get(
                    'name, value',
                    '`series_id` = ' . $seriesInf['id'],
                    'order'
                );

                $cRow = $seriesLastRow + 1;
                $this->aSheet->setCellValue('B' . $cRow, 'Параметр');
                $this->setCellStyle('B' . $cRow, self::STYLE_H1);
                $this->aSheet->setCellValue('C' . $cRow, 'Значение');
                $this->setCellStyle('C' . $cRow, self::STYLE_H1);

                foreach ($options as $o) {
                    $cRow++;
                    $this->aSheet->setCellValue('B' . $cRow, $o['name']);
                    $this->aSheet->setCellValue('C' . $cRow, $o['value']);
                }

                $seriesLastRow = $cRow;
            }

            // Товары
            if (count($optItems)) {
                $itemsCnt = $this->fillItems($col, $row, $seriesInf, $optItems, $seriesExtraFormula, $seriesExtraCell);
            } else {
                $itemsCnt = 0;
            }
            if ($row + 1 + $itemsCnt > $seriesLastRow) {
                $seriesLastRow = $row + 1 + $itemsCnt;
            }

            $row = $seriesLastRow + 2;
        }

        // Настраиваем ширину ячеек
        $this->setColsWidth($optSeries, $optItems, 2 + $maxMaterialsCnt * 2);
    }


    /**
     * Вставляем в таблицу товары одной серии
     *
     * @param int    $startCol
     * @param int    $startRow
     * @param array  $seriesInf
     * @param array  $optItems
     * @param bool   $seriesExtraFormula
     * @param string $seriesExtraCell
     * @return    int        К-во товаров
     */
    protected function fillItems($startCol, $startRow, $seriesInf, $optItems, $seriesExtraFormula, $seriesExtraCell)
    {
        // Все возможные материалы
        $oMaterials = new Catalog_Materials();
        $matNames = $oMaterials->getHash('id, name', '', 'order');


        // Группы товаров
        static $groupsByCats;
        if (!$groupsByCats) {
            $groupsByCats = array();
        }
        if (!isset($groupsByCats[$seriesInf['category_id']])) {
            $oItemsGroups = new Catalog_Items_Groups();
            $groupsByCats[$seriesInf['category_id']] = $oItemsGroups->getHash(
                'id, name',
                '`category_id` = ' . $seriesInf['category_id'],
                'order'
            );
        }
        $groupsNames = $groupsByCats[$seriesInf['category_id']];

        // Получаем список товаров серии
        static $oItems;
        if (!$oItems) {
            $oItems = new Catalog_Items();
        }
        $items = $oItems->get(
            '*',
            '`series_id` = ' . intval($seriesInf['id']),
            'order'
        );

        // Наценка серии
        $seriesExtra = Catalog::num2percent($seriesInf['extra_charge'], Catalog::PC_INCREASE);

        // Шапка и основные параметры товаров
        $itemExtraCol = 0;

        $col = $startCol;
        foreach (self::$fldsItems as $fld => $fldName) {
            if (in_array($fld, $optItems)) {
                // Шапка
                $cell1 = self::colN2C($col) . $startRow;
                $cell2 = self::colN2C($col) . ($startRow + 1);
                $this->aSheet->setCellValue(
                    $cell1,
                    $fldName
                );
                $this->setCellStyle($cell1, self::STYLE_H1);
                $this->setCellStyle($cell2, self::STYLE_H1);
                $this->aSheet->mergeCells($cell1 . ':' . $cell2);

                // Значения
                $row = $startRow + 2;
                foreach ($items as $item) {
                    $cell = self::colN2C($col) . $row;

                    if (in_array($fld, array(
                        self::FLD_ITEMS_ID,
                        self::FLD_ITEMS_NAME,
                        self::FLD_ITEMS_SIZE
                    ))) {
                        $this->aSheet->setCellValue(
                            $cell,
                            $item[$fld]
                        );
                    } elseif ($fld === self::FLD_ITEMS_GROUP) {
                        $this->aSheet->setCellValue(
                            $cell,
                            isset($groupsNames[$item['group_id']]) ? $groupsNames[$item['group_id']] : ''
                        );
                    } elseif ($fld === self::FLD_ITEMS_ART) {
                        $this->aSheet->setCellValue($cell, $item['art']);
                        $this->aSheet->getCell($cell)->getHyperlink()->setUrl(
                            'https://' . _HOST . Catalog_Items::a($seriesInf, $item)
                        );
                        $this->setCellStyle($cell, self::STYLE_LINK);
                    } elseif ($fld === self::FLD_ITEMS_VOLUME) {
                        $this->aSheet->setCellValue($cell, $item['volume']);
                        $this->setCellStyle($cell, self::STYLE_M3);
                    } elseif ($fld === self::FLD_ITEMS_WEIGHT) {
                        $this->aSheet->setCellValue($cell, $item['weight']);
                        $this->setCellStyle($cell, self::STYLE_KG);
                    } elseif ($fld === self::FLD_ITEMS_DESCRIPTION) {
                        $this->aSheet->setCellValue($cell, $item['text']);
                        $this->aSheet->getStyle($cell)->getAlignment()->setWrapText(true);
                    } elseif ($fld === self::FLD_ITEMS_PRICES) {
                        // Наценка
                        $itemExtra = round(($item['extra_charge'] - 1) * 100, 3);
                        if ($seriesInf['import_extra_formula'] === Catalog_Series::IMPORT_FORMULA_EXTRA) {
                            // Наценка вычисляется по вх. и вых. цене
                            // Формула будет вписана, когда определим колонки с ценами
                            $this->setCellStyle($cell, self::STYLE_BOLD);
                        } elseif ($seriesExtraFormula && $seriesExtra == $itemExtra) {
                            // Наценка берётся из наценки серии
                            $this->aSheet->setCellValue(
                                $cell,
                                '=' . $seriesExtraCell
                            );
                            $this->setCellStyle($cell, self::STYLE_BOLD);
                        } else {
                            // Наценка товара
                            $this->aSheet->setCellValue($cell, $itemExtra);
                            $this->setCellStyle($cell, self::STYLE_BOLD, self::STYLE_GREEN);
                        }
                        $itemExtraCol = $col;
                    } elseif ($fld === self::FLD_ITEMS_DISCOUNT) {
                        // Скидка
                        $this->aSheet->setCellValue(
                            $cell,
                            Catalog::num2percent($item['discount'], Catalog::PC_DECREASE)
                        );
                    }

                    $row++;
                }

                $col++;
            }
        }

        // Цены на товар (основная и по материалам)
        if (in_array(self::FLD_ITEMS_PRICES, $optItems)) {
            // Шапка для основной цены
            $cell1 = self::colN2C($col) . $startRow;
            $cell2 = self::colN2C($col + 1) . $startRow;
            $this->aSheet->setCellValue($cell1, 'Цена');
            $this->setCellStyle($cell1, self::STYLE_H1);
            $this->setCellStyle($cell2, self::STYLE_H1);
            $this->aSheet->mergeCells($cell1 . ':' . $cell2);

            $cell = self::colN2C($col) . ($startRow + 1);
            $this->aSheet->setCellValue($cell, 'Вход');
            $this->setCellStyle($cell, self::STYLE_H2);

            $cell = self::colN2C($col + 1) . ($startRow + 1);
            $this->aSheet->setCellValue($cell, 'Выход');
            $this->setCellStyle($cell, self::STYLE_H2);


            // Шапка для цен по материалам
            $matCol = $col + 2;
            foreach ($seriesInf['materials'] as $mId) {
                if (!isset($matNames[$mId])) {
                    continue;
                }

                $cell1 = self::colN2C($matCol) . $startRow;
                $cell2 = self::colN2C($matCol + 1) . $startRow;
                $this->aSheet->setCellValue($cell1, $matNames[$mId] . ' (' . $mId . ')');
                $this->setCellStyle($cell1, self::STYLE_H3);
                $this->setCellStyle($cell2, self::STYLE_H3);
                $this->aSheet->getStyle("$cell1:$cell2")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('777777');
                $this->aSheet->mergeCells($cell1 . ':' . $cell2);


                $cell = self::colN2C($matCol) . ($startRow + 1);
                $this->aSheet->setCellValue($cell, 'Вход');
                $this->setCellStyle($cell, self::STYLE_H2);

                $cell = self::colN2C($matCol + 1) . ($startRow + 1);
                $this->aSheet->setCellValue($cell, 'Выход');
                $this->setCellStyle($cell, self::STYLE_H2);

                $matCol += 2;
            }

            $oItems2Materials = new Catalog_Items_2Materials();
            $row = $startRow + 2;
            foreach ($items as $item) {
                // Входная цена
                $price = abs($item['price']);
                //if($price == 0) $price = 100;

                if ($item['currency'] === Catalog::USD) {
                    $priceFormat = self::STYLE_USD;
                } else/*if($item['currency'] === Catalog::RUB)*/ {
                    $priceFormat = self::STYLE_RUR;
                }
                $cell1 = self::colN2C($col) . $row;
                $this->aSheet->setCellValue($cell1, $price);
                $this->setCellStyle($cell1, $priceFormat, self::STYLE_BOLD, self::STYLE_GREEN);

                $cell2 = self::colN2C($col + 1) . $row;
                $itemExtraCell = self::colN2C($itemExtraCol) . $row;

                if ($seriesInf['import_extra_formula'] === Catalog_Series::IMPORT_FORMULA_EXTRA) {
                    // Наценка зависит от вх/вых цен
                    $priceOut = abs($price * $item['extra_charge']);

                    $this->aSheet->setCellValue($cell2, $priceOut);
                    $this->aSheet->setCellValue($itemExtraCell, '=(' . $cell2 . '/' . $cell1 . '-1)*100');

                    $this->setCellStyle($cell2, $priceFormat, self::STYLE_BOLD, self::STYLE_GREEN);
                } else {
                    // Выходная цена зависит от наценки
                    $this->aSheet->setCellValue(
                        $cell2,
                        '=' . $cell1 . '*(' . $itemExtraCell . '/100+1)'
                    );
                    $this->setCellStyle($cell2, self::STYLE_BOLD);
                }
                $this->setCellStyle($cell2, $priceFormat, self::STYLE_BORDER_RIGHT);

                // Цены по материалам
                $materials = $oItems2Materials->getWhtKeys(
                    '*',
                    '`item_id` = ' . $item['id'],
                    '',
                    0,
                    '',
                    '',
                    'material_id'
                );

                $matCol = $col + 2;
                foreach ($seriesInf['materials'] as $mId) {
                    if (!isset($matNames[$mId])) {
                        continue;
                    }

                    $cell1 = self::colN2C($matCol) . $row;
                    $cell2 = self::colN2C($matCol + 1) . $row;

                    if (isset($materials[$mId])) {
                        $price = abs($materials[$mId]['price']);
                        if ($price != 0) {
                            // Для данного товара есть отдельная цена на этот материал
                            // Входная
                            if ($materials[$mId]['currency'] === Catalog::USD) {
                                $priceFormat = self::STYLE_USD;
                            } else/*if($materials[$mId]['currency'] === Catalog::RUB)*/ {
                                $priceFormat = self::STYLE_RUR;
                            }
                            $this->aSheet->setCellValue($cell1, $price);
                            $this->setCellStyle($cell1, $priceFormat, self::STYLE_BOLD, self::STYLE_GREEN);
                            $this->setCellStyle($cell2, self::STYLE_BOLD);
                        } else {
                            // Для товара нет отдельной цены на этот материал
                            $priceFormat = self::STYLE_RUR;

                            // Входная
                            $this->aSheet->setCellValue($cell1, '0');
                            $this->setCellStyle($cell1, $priceFormat, self::STYLE_GREY);
                            $this->setCellStyle($cell2, self::STYLE_GREY);
                        }

                        // Выходная цена
                        $this->aSheet->setCellValue(
                            $cell2,
                            '=' . $cell1 . '*(' . $itemExtraCell . '/100+1)'
                        );
                        $this->setCellStyle($cell2, $priceFormat, self::STYLE_BORDER_RIGHT);
                    } else {
                        // Материал не доступен с данным товаром
                        $this->setCellStyle($cell2, self::STYLE_BORDER_RIGHT);
                    }

                    $matCol += 2;
                }

                $row++;
            }
        }

        return count($items);
    }


    /**
     * Настраиваем ширину ячеек
     *
     * @param array $optSeries
     * @param array $optItems
     * @param int   $priceColCnt
     */
    protected function setColsWidth(array $optSeries, array $optItems, int $priceColCnt = 2): void
    {
        $col = 0;
        foreach (self::$fldsSeries as $fld => $fldName) {
            if (in_array($fld, $optSeries)) {
                $this->aSheet->getColumnDimension(self::colN2C($col))->setWidth(self::$colsSeriesWidth[$fld]);
                $col++;
            }
        }
        foreach (self::$fldsItems as $fld => $fldName) {
            if (in_array($fld, $optItems)) {
                $this->aSheet->getColumnDimension(self::colN2C($col))->setWidth(self::$colsItemsWidth[$fld]);
                $col++;
            }
        }
        for ($c = $col; $c < $col + $priceColCnt; $c++) {
            $this->aSheet->getColumnDimension(self::colN2C($c))->setWidth(12);
        }
    }


    /**
     * Устанавливает стиль для ячейки таблицы
     * Можно передать несколько стилей сразу (начиная со второго аргумента)
     *
     * @param string $cell Название ячейки. Например, B5
     * @param string $style Одна из констант self::STYLE...
     * param    string    $style2
     * param    string    $style3
     * param    string    $style4
     * ...
     * @return void
     */
    protected function setCellStyle(string $cell, string $style): void
    {
        $styles = [];

        $styles[self::STYLE_BOLD] = array(
            'font' => array(
                'bold' => true
            )
        );

        $styles[self::STYLE_H1] = array(
            'font' => array(
                'size' => '13',
                'color' => array(
                    'rgb' => '003366'
                ),
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'bottom' => array(
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => array(
                        'rgb' => 'c0c0c0'
                    )
                )
            )
        );

        $styles[self::STYLE_H2] = array(
            'font' => array(
                'size' => '11',
                'color' => array(
                    'rgb' => '003366'
                ),
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'bottom' => array(
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '0066cc'
                    )
                )
            )
        );

        $styles[self::STYLE_H3] = array(
            'font' => array(
                'size' => '11',
                'color' => array(
                    'rgb' => 'FFFFFF'
                ),
                'bold' => true
            ),
            'fill' => array(
                'type' => Fill::FILL_SOLID,
                'startcolor' => array(
                    'rgb' => 'c0c0c0'
                )
            ),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '0066cc'
                    ]
                ]
            ]
        );

        $styles[self::STYLE_LINK] = array(
            'font' => array(
                'color' => array(
                    'rgb' => '0000FF'
                ),
                'underline' => Font::UNDERLINE_SINGLE
            ),
        );

        $styles[self::STYLE_M3] = array(
            'numberFormat' => array(
                'formatCode' => FORMAT_CURRENCY_M3
            )
        );

        $styles[self::STYLE_KG] = array(
            'numberFormat' => array(
                'formatCode' => FORMAT_CURRENCY_KG
            )
        );

        $styles[self::STYLE_USD] = array(
            'numberFormat' => array(
                'formatCode' => NumberFormat::FORMAT_CURRENCY_USD
            )
        );

        $styles[self::STYLE_RUR] = array(
            'numberFormat' => array(
                'formatCode' => FORMAT_CURRENCY_RUR
            )
        );

        $styles[self::STYLE_PERC] = array(
            'numberFormat' => array(
                'formatCode' => NumberFormat::FORMAT_PERCENTAGE
            )
        );

        $styles[self::STYLE_GREY] = array(
            'font' => array(
                'color' => array(
                    'rgb' => '777777'
                )
            )
        );

        $styles[self::STYLE_GREEN] = array(
            'font' => array(
                'color' => array(
                    'rgb' => '009900'
                )
            )
        );

        $styles[self::STYLE_BORDER_RIGHT] = array(
            'borders' => array(
                'right' => array(
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '000000'
                    )
                )
            )
        );

        if (isset($styles[$style])) {
            $this->aSheet->getStyle($cell)->applyFromArray($styles[$style]);

            if (func_num_args() > 2) {
                $args = func_get_args();

                for ($n = 2; $n < func_num_args(); $n++) {
                    $style = $args[$n];
                    if (isset($styles[$style])) {
                        $this->aSheet->getStyle($cell)->applyFromArray($styles[$style]);
                    }
                }
            }
        }
    }
}
