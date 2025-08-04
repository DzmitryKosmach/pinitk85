<?php

// Набор основных методов обработки графики средствами библиотеки Imagick

class Images_CtrlImagick implements Images_Ctrl {

	// Возвращает размеры картинки
	function size(&$img){
		return array($img->getImageWidth(), $img->getImageHeight());
	}

	// Ресайз картинки. В этой функции ничего не вычисляется, т.к. все параметры сюда передаются при вызове
	function resize(
		&$img,
		$canvasW, $canvasH,
		$offsetX, $offsetY,
		$targetW, $targetH,
		$sourceW, $sourceH,
		$transparent = false)
	{
		// Создаём объект для холста
		$imgNew = new Imagick();

		// Определяем цвет фона и создаём холст заданных размеров с этим цветом
		$clr = new ImagickPixel($transparent ? 'transparent' : '#FFF');
		$imgNew->newImage($canvasW, $canvasH, $clr);

		// Масштабируем картинку
		$img->scaleImage($targetW, $targetH);
		if($transparent) $imgNew->paintTransparentImage($clr, 0.0, 0.0);	// Задаём прозрачность
		$imgNew->compositeImage($img, imagick::COMPOSITE_DEFAULT, $offsetX, $offsetY);

		// Задаём качество и повышаем резкость
		if(!$transparent) $imgNew->sharpenImage(2, 1);
		$imgNew->setImageCompressionQuality(95);

		return $imgNew;
	}

	// Загружает изображение заданного формата из файла, возвращает переменную (или объект) с изображением
	function fromFile($file, $mime = false){
		if(!$mime){$mime = getimagesize($file); $mime = $mime['mime'];}
		if(in_array($mime, Images::$extToMime)){
			$img = new imagick($file);
			$img->setImageFormat(array_search($mime, Images::$extToMime));
			return $img;
		}else{
			return false;
		}
	}

	// Сохраняет изображение в файл
	function toFile($img, $file, $mime){
		if(in_array($mime, Images::$extToMime)){
			$img->setImageFormat(array_search($mime, Images::$extToMime));
			$img->writeImage($file);
		}
	}

	// Выводит изображение в браузер
	function output($img, $mime, $h = true){
		if(in_array($mime, Images::$extToMime)){
			if($h) header('Content-type: ' . $mime);
			$img->setImageFormat(array_search($mime, Images::$extToMime));
			echo $img;
		}
	}

	// Уничтожаем объект-картинку
	function destroy($img){
		$img->destroy();
	}
}

?>