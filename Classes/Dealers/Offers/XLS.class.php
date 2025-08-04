<?php

/**
 * Генерация XLS-файла с коммерческим предложением
 *
 * @author	Seka
 */

class Dealers_Offers_XLS {

	/**
	 * Шаблон XLS-файла
	 */
	const TEMPLATE = '/Uploads/OffersXLS/template.xls';

	/**
	 * Строка, с которой начинается таблица товаров
	 */
	const ROW_START = 12;

	/**
	 * Столбцы для таблицы товаров
	 */
	const COL_NUM = 'B';
	const COL_SERIES = 'C';
	const COL_NAME = 'D';
	const COL_MATERIAL = 'E';
	const COL_ART = 'F';
	const COL_SIZE = 'G';
	const COL_PIC = 'H';
	const COL_PRICE = 'I';
	const COL_COUNT = 'J';
	const COL_TOTAL = 'K';

	/**
	 * Столбцы для сводной информации
	 */
	const COL_INFO_NAME = 'H';
	const COL_INFO_NAME_BRDR = 'I';
	const COL_INFO_NAME_MERGE = 'J';	// Столбец COL_INFO_NAME будет смержен до этого столбца
	const COL_INFO_VALUE = 'K';

	/**
	 * @var int
	 */
	protected static $rowCurrent = 0;

	/**
	 * @var array
	 */
	protected static $brdrThin = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array(
					'rgb' => '000000'
				)
			)
		)
	);

	/**
	 * @var array
	 */
	protected static $brdrThick = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
				'color' => array(
					'rgb' => '000000'
				)
			)
		)
	);

	/**
	 * @var array
	 */
	protected static $brdrThickLeft = array(
		'borders' => array(
			'left' => array(
				'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
				'color' => array(
					'rgb' => '000000'
				)
			)
		)
	);

	/**
	 * @var array
	 */
	protected static $brdrThickRight = array(
		'borders' => array(
			'right' => array(
				'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
				'color' => array(
					'rgb' => '000000'
				)
			)
		)
	);

	/**
	 * @var array
	 */
	protected static $bgrGrey = array(
		'fill'	=> array(
			'type'       => PHPExcel_Style_Fill::FILL_SOLID,
			'startcolor' => array(
				'rgb' => 'c0c0c0'
			)
		)
	);

	/**
	 * @var array
	 */
	protected static $aVertCenter = array(
		'alignment'	=> array(
			'vertical'	=> PHPExcel_Style_Alignment::VERTICAL_CENTER
		)
	);

	/**
	 * @var array
	 */
	protected static $aHorLeft = array(
		'alignment'	=> array(
			'horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT
		)
	);

	/**
	 * @var array
	 */
	protected static $aHorCenter = array(
		'alignment'	=> array(
			'horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER
		)
	);

	/**
	 * @var array
	 */
	protected static $formatNumber = array(
		'numberformat'	=> array(
			'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER
		)
	);

	/**
	 * @var array
	 */
	protected static $formatPrice = array(
		'numberformat'	=> array(
			'code' => PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_RUR_SIMPLE1
		)
	);

	/**
	 * @var array
	 */
	protected static $smallFont = array(
		'font'	=> array(
			'size'	=> '7'
		)
	);


	/**
	 * @static
	 * @param	array	$offer
	 * @param	string	$fileName
	 */
	static function make($offer, $fileName){
		include_once(Config::path('external') . '/PHPExcel/PHPExcel.php');
		include_once(Config::path('external') . '/PHPExcel/PHPExcel/Writer/Excel5.php');

		// Открываем шаблон
		$oReader = PHPExcel_IOFactory::createReaderForFile(_ROOT . self::TEMPLATE);
		$oReader->setReadDataOnly(false);
		$oExcel = $oReader->load(_ROOT . self::TEMPLATE);
		$aSheet = $oExcel->getActiveSheet();

		// Заполняем таблицу товарами
		$offerItems = unserialize($offer['cart']); if(!is_array($offerItems)) $offerItems = array();
		self::insertItems($aSheet, $offerItems);

		// Сводные данные
		$offerOptions = unserialize($offer['options']); if(!is_array($offerOptions)) $offerOptions = array();
		self::insertInfo(
			$aSheet,
			$offerOptions,
			$offer['price_out'] + $offer['price_options']
		);

		// Фотографии
		$offerPhotos = unserialize($offer['photos']); if(!is_array($offerPhotos)) $offerPhotos = array();
		self::insertPhotos($aSheet, $offerPhotos);

		// Сохраняем файл
		if(is_file($fileName)) unlink($fileName);
		$oWriter = new PHPExcel_Writer_Excel5($oExcel);
		$oWriter->save($fileName);
	}




	/** Заполняем таблицу товарами
	 * @static
	 * @param	PHPExcel_Worksheet	$aSheet
	 * @param	array				$offerItems
	 */
	protected static function insertItems(&$aSheet, $offerItems = array()){
		$oMaterials = new Catalog_Materials();

		self::$rowCurrent = self::ROW_START;

		$num = 1;
		foreach($offerItems as $item){
			$aSheet->getRowDimension(self::$rowCurrent)->setRowHeight(84);

			$aSheet->setCellValue(self::COL_NUM . self::$rowCurrent, $num);
			$aSheet->getStyle(self::COL_NUM . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$aHorCenter)
				->applyFromArray(self::$brdrThin);

			$aSheet->setCellValue(self::COL_SERIES . self::$rowCurrent, $item['series']['name']);
			$aSheet->getStyle(self::COL_SERIES . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$aHorLeft)
				->applyFromArray(self::$brdrThin);

			$aSheet->setCellValue(self::COL_NAME . self::$rowCurrent, $item['item']['name']);
			$aSheet->getStyle(self::COL_NAME . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$aHorLeft)
				->applyFromArray(self::$brdrThin);

			$aSheet->setCellValue(self::COL_ART . self::$rowCurrent, $item['item']['art']);
			$aSheet->getStyle(self::COL_ART . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$aHorLeft)
				->applyFromArray(self::$brdrThin);

			$aSheet->setCellValue(self::COL_SIZE . self::$rowCurrent, $item['item']['size']);
			$aSheet->getStyle(self::COL_SIZE . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$aHorLeft)
				->applyFromArray(self::$brdrThin);

			$aSheet->setCellValue(self::COL_PRICE . self::$rowCurrent, $item['price']);
			$aSheet->getStyle(self::COL_PRICE . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$formatPrice)
				->applyFromArray(self::$brdrThin);

			$aSheet->setCellValue(self::COL_COUNT . self::$rowCurrent, $item['amount']);
			$aSheet->getStyle(self::COL_COUNT . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$formatNumber)
				->applyFromArray(self::$aHorCenter)
				->applyFromArray(self::$brdrThin);

			$aSheet->setCellValue(self::COL_TOTAL . self::$rowCurrent, $item['price'] * $item['amount']);
			$aSheet->getStyle(self::COL_TOTAL . self::$rowCurrent)
				->applyFromArray(self::$aVertCenter)
				->applyFromArray(self::$formatPrice)
				->applyFromArray(self::$brdrThin);

			// Материал
			$matId = intval($item['material']['id']);
			$mat = $oMaterials->imageExtToData($oMaterials->getRow('*', '`id` = ' . $matId));
			if($mat){
				$matName = Catalog_Materials::getFullName($matId);
				if(count($matName) > 1){
					$aSheet->getStyle(self::COL_MATERIAL . self::$rowCurrent)
						->applyFromArray(self::$smallFont)
						->getAlignment()->setWrapText(true);	// Многострочный текст
				}
				$matName = implode("\n", $matName);

				if($mat['_img_ext']){
					$matImg = Config::pathRel('images') . Catalog_Materials::$imagePath . $matId . '.' . $mat['_img_ext'];
				}else{
					$matImg = false;
				}
			}else{
				$matName = '';
				$matImg = false;
			}
			$aSheet->setCellValue(self::COL_MATERIAL . self::$rowCurrent, $matName);
			$aSheet->getStyle(self::COL_MATERIAL . self::$rowCurrent)
				->applyFromArray(self::$aHorCenter)
				->applyFromArray(self::$brdrThin);

			$matImg = self::smallImage($matImg, '_100x100_0');

			if($matImg && is_file(_ROOT . $matImg)){
				$gdImage = self::imageGdObj(_ROOT . $matImg, $mime);

				$objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
				//$objDrawing->setName('Sample image'); $objDrawing->setDescription('Sample image');
				$objDrawing->setImageResource($gdImage);
				$objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
				$objDrawing->setMimeType($mime);
				$objDrawing->setWidthAndHeight(72, 65);
				$objDrawing->setCoordinates(self::COL_MATERIAL . self::$rowCurrent);
				$objDrawing->setOffsetX(29);
				$objDrawing->setOffsetY(5);
				$objDrawing->setWorksheet($aSheet);
			}

			// Картинка товара
			$itemImg = Config::pathRel('images') . Catalog_Items::$imagePath . $item['item']['id'] . '.' . $item['item']['_img_ext'];
			$itemImg = self::smallImage($itemImg, '_140x140_1');
			if(is_file(_ROOT . $itemImg)){
				$gdImage = self::imageGdObj(_ROOT . $itemImg, $mime);

				$objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
				//$objDrawing->setName('Sample image'); $objDrawing->setDescription('Sample image');
				$objDrawing->setImageResource($gdImage);
				$objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_DEFAULT);
				$objDrawing->setMimeType($mime);
				$objDrawing->setWidthAndHeight(101, 92);
				$objDrawing->setCoordinates(self::COL_PIC . self::$rowCurrent);
				$objDrawing->setOffsetX(27);
				$objDrawing->setOffsetY(5);
				$objDrawing->setWorksheet($aSheet);
			}
			$aSheet->getStyle(self::COL_PIC . self::$rowCurrent)
				->applyFromArray(self::$aHorCenter)
				->applyFromArray(self::$brdrThin);

			$num++;
			self::$rowCurrent++;
		}
		//exit;
	}




	/** Вставляем в таблицу сводные данные
	 * @static
	 * @param	PHPExcel_Worksheet	$aSheet
	 * @param	array	$offerOptions
	 * @param	number	$offerTotalPrice
	 */
	protected static function insertInfo(&$aSheet, $offerOptions, $offerTotalPrice){
		// Доставка
		$aSheet->mergeCells(self::COL_INFO_NAME . self::$rowCurrent . ':' . self::COL_INFO_NAME_MERGE . self::$rowCurrent);
		$aSheet->setCellValue(self::COL_INFO_NAME . self::$rowCurrent, 'Доставка: ' . $offerOptions[Orders_Options::DELIVERY]['info']);
		$aSheet->getStyle(self::COL_INFO_NAME . self::$rowCurrent)
			->applyFromArray(self::$aHorLeft)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickLeft);
		$aSheet->getStyle(self::COL_INFO_NAME_BRDR . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);
		$aSheet->getStyle(self::COL_INFO_NAME_MERGE . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);

		$aSheet->setCellValue(self::COL_INFO_VALUE . self::$rowCurrent, $offerOptions[Orders_Options::DELIVERY]['price']);
		$aSheet->getStyle(self::COL_INFO_VALUE . self::$rowCurrent)
			->applyFromArray(self::$formatPrice)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickRight);

		self::$rowCurrent++;


		// Разгрузка
		$aSheet->mergeCells(self::COL_INFO_NAME . self::$rowCurrent . ':' . self::COL_INFO_NAME_MERGE . self::$rowCurrent);
		$aSheet->setCellValue(self::COL_INFO_NAME . self::$rowCurrent, 'Разгрузка: ' . $offerOptions[Orders_Options::UNLOADING]['info']);
		$aSheet->getStyle(self::COL_INFO_NAME . self::$rowCurrent)
			->applyFromArray(self::$aHorLeft)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickLeft);
		$aSheet->getStyle(self::COL_INFO_NAME_BRDR . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);
		$aSheet->getStyle(self::COL_INFO_NAME_MERGE . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);

		$aSheet->setCellValue(self::COL_INFO_VALUE . self::$rowCurrent, $offerOptions[Orders_Options::UNLOADING]['price']);
		$aSheet->getStyle(self::COL_INFO_VALUE . self::$rowCurrent)
			->applyFromArray(self::$formatPrice)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickRight);

		self::$rowCurrent++;


		// Сборка
		$aSheet->mergeCells(self::COL_INFO_NAME . self::$rowCurrent . ':' . self::COL_INFO_NAME_MERGE . self::$rowCurrent);
		$aSheet->setCellValue(self::COL_INFO_NAME . self::$rowCurrent, 'Сборка: ' . $offerOptions[Orders_Options::ASSEMBLY]['info']);
		$aSheet->getStyle(self::COL_INFO_NAME . self::$rowCurrent)
			->applyFromArray(self::$aHorLeft)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickLeft);
		$aSheet->getStyle(self::COL_INFO_NAME_BRDR . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);
		$aSheet->getStyle(self::COL_INFO_NAME_MERGE . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);

		$aSheet->setCellValue(self::COL_INFO_VALUE . self::$rowCurrent, $offerOptions[Orders_Options::ASSEMBLY]['price']);
		$aSheet->getStyle(self::COL_INFO_VALUE . self::$rowCurrent)
			->applyFromArray(self::$formatPrice)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickRight);

		self::$rowCurrent++;


		// Вывоз мусора
		$aSheet->mergeCells(self::COL_INFO_NAME . self::$rowCurrent . ':' . self::COL_INFO_NAME_MERGE . self::$rowCurrent);
		$aSheet->setCellValue(self::COL_INFO_NAME . self::$rowCurrent, 'Вывоз мусора: ' . $offerOptions[Orders_Options::GARBAGE]['info']);
		$aSheet->getStyle(self::COL_INFO_NAME . self::$rowCurrent)
			->applyFromArray(self::$aHorLeft)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickLeft);
		$aSheet->getStyle(self::COL_INFO_NAME_BRDR . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);
		$aSheet->getStyle(self::COL_INFO_NAME_MERGE . self::$rowCurrent)
			->applyFromArray(self::$brdrThin);

		$aSheet->setCellValue(self::COL_INFO_VALUE . self::$rowCurrent, $offerOptions[Orders_Options::GARBAGE]['price']);
		$aSheet->getStyle(self::COL_INFO_VALUE . self::$rowCurrent)
			->applyFromArray(self::$formatPrice)
			->applyFromArray(self::$brdrThin)
			->applyFromArray(self::$brdrThickRight);

		self::$rowCurrent++;


		// ИТОГО
		$aSheet->mergeCells(self::COL_INFO_NAME . self::$rowCurrent . ':' . self::COL_INFO_NAME_MERGE . self::$rowCurrent);
		$aSheet->setCellValue(self::COL_INFO_NAME . self::$rowCurrent, 'Итого');
		$aSheet->getStyle(self::COL_INFO_NAME . self::$rowCurrent)
			->applyFromArray(self::$aHorCenter)
			->applyFromArray(self::$brdrThick)
			->applyFromArray(self::$bgrGrey);
		$aSheet->getStyle(self::COL_INFO_NAME_BRDR . self::$rowCurrent)
			->applyFromArray(self::$brdrThick)
			->applyFromArray(self::$bgrGrey);
		$aSheet->getStyle(self::COL_INFO_NAME_MERGE . self::$rowCurrent)
			->applyFromArray(self::$brdrThick)
			->applyFromArray(self::$bgrGrey);

		$aSheet->setCellValue(self::COL_INFO_VALUE . self::$rowCurrent, $offerTotalPrice);
		$aSheet->getStyle(self::COL_INFO_VALUE . self::$rowCurrent)
			->applyFromArray(self::$formatPrice)
			->applyFromArray(self::$brdrThick)
			->applyFromArray(self::$bgrGrey);

		self::$rowCurrent++;
	}





	/**
	 * @static
	 * @param	PHPExcel_Worksheet	$aSheet
	 * @param	array	$offerPhotos
	 */
	protected static function insertPhotos($aSheet, $offerPhotos){
		if(!count($offerPhotos)) return;
		$offerPhotos = array_map('intval', $offerPhotos);

		$oPhotos = new Catalog_Series_Photos();
		$photos = $oPhotos->imageExtToData($oPhotos->get('*', '`id` IN (' . implode(',', $offerPhotos) . ')'));

		$num = 0;
		foreach($photos as $p){
			$photo = Config::pathRel('images') . Catalog_Series_Photos::$imagePath . $p['id'] . '.' . $p['_img_ext'];
			$photo = self::smallImage($photo, '_360x270_' . $p['rm']);
			if(is_file(_ROOT . $photo)){
				$gdImage = self::imageGdObj(_ROOT . $photo, $mime);

				$objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
				//$objDrawing->setName('Sample image'); $objDrawing->setDescription('Sample image');
				$objDrawing->setImageResource($gdImage);
				$objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
				$objDrawing->setMimeType($mime);

				$objDrawing->setWorksheet($aSheet);


				if($num % 4 == 0){
					$col = 'A';
					$x = 5;
				}elseif($num % 4 == 1){
					$col = 'D';
					$x = 125;
				}elseif($num % 4 == 2){
					$col = 'E';
					$x = 50;
				}else/*if($num % 4 == 3)*/{
					$col = 'H';
					$x = 32;
				}

				$row = self::$rowCurrent + 11 * floor($num / 4);
				$objDrawing->setCoordinates($col . $row);
				$objDrawing->setOffsetX($x);
				$objDrawing->setOffsetY(10);
				$objDrawing->setWidthAndHeight(263, 180);

				$num++;
			}
		}
	}





	/**
	 * @static
	 * @param	string	$filename
	 * @param	string	$size
	 * @return	bool|string
	 */
	protected static function smallImage($filename, $size){
		if(!is_file(_ROOT . $filename)) return false;

		$filename = str_replace('.jpg', $size . '.jpg', $filename);
		$filename = str_replace('.jpeg', $size . '.jpeg', $filename);
		$filename = str_replace('.png', $size . '.png', $filename);
		$filename = str_replace('.gif', $size . '.gif', $filename);
		if(is_file(_ROOT . $filename)) return $filename;

		$fileurl = 'http://' . $_SERVER['SERVER_NAME'] . $filename;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $fileurl);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_exec($curl);
		curl_close($curl);

		return $filename;
	}




	protected static function imageGdObj($filename, &$mime = ''){
		static $funcs;
		if(!$funcs){
			$funcs = array(
				'image/jpeg'	=> 'imagecreatefromjpeg',
				'image/png'		=> 'imagecreatefrompng',
				'image/gif'		=> 'imagecreatefromgif'
			);
		}

		$mime = getimagesize($filename);
		$mime = $mime['mime'];
		return $funcs[$mime]($filename);
	}
}

?>