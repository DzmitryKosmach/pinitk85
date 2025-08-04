<?php

// Набор основных методов обработки графики средствами библиотеки GD

class Images_CtrlGd implements Images_Ctrl {

	// Возвращает размеры картинки
	function size(&$img){
		return array(imagesx($img), imagesy($img));
	}

	// Ресайз картинки. В этой функции ничего не вычисляется, т.к. все параметры сюда передаются при вызове
	function resize(
		&$img,
		$canvasW, $canvasH,
		$offsetX, $offsetY,
		$targetW, $targetH,
		$sourceW, $sourceH,
		$transparent = false
	){
		// Создаём холст нужного размера
		$imgNew = imagecreatetruecolor($canvasW, $canvasH);

		// Определяем цвет фоновой заливки (обычно белый, но для прозрачности нужен нестандартный цвет)
		// И заливаем холст
		$white = $transparent ? imagecolorallocate($imgNew, 245, 247, 251) : imagecolorallocate($imgNew, 255, 255, 255);
		imagefilledrectangle($imgNew, 0, 0, $canvasW, $canvasH, $white);

		// Устанавливаем параметр прозрачности
		if($transparent) imagecolortransparent($imgNew, $white);

		// Копируем нужный кусок исходной картинки в нужное место на холсте
		imagecopyresampled($imgNew, $img, $offsetX, $offsetY, 0, 0, $targetW, $targetH, $sourceW, $sourceH);

		return $imgNew;
	}

	// Загружает изображение заданного формата из файла, возвращает переменную (или объект) с изображением
	function fromFile($file, $mime = false){
		if(!$mime){$mime = getimagesize($file); $mime = $mime['mime'];}
		$funcs = array(
			'image/jpeg'	=> 'imagecreatefromjpeg',
			'image/png'		=> 'imagecreatefrompng',
			'image/gif'		=> 'imagecreatefromgif'
		);
		return isset($funcs[$mime]) ? $funcs[$mime]($file) : false;
	}

	// Сохраняет изображение в файл
	function toFile($img, $file, $mime){
		$funcs = array(
			'image/jpeg'	=> 'imagejpeg',
			'image/png'		=> 'imagepng',
			'image/gif'		=> 'imagegif'
		);
		if(isset($funcs[$mime])) $funcs[$mime]($img, $file);
	}

	// Выводит изображение в браузер
	function output($img, $mime, $h = true){
		$funcs = array(
			'image/jpeg'	=> 'imagejpeg',
			'image/png'		=> 'imagepng',
			'image/gif'		=> 'imagegif'
		);
		if(isset($funcs[$mime])){
			if($h) header('Content-type: ' . $mime);
			$funcs[$mime]($img);
		}
	}

	// Уничтожаем объект-картинку
	function destroy($img){
		imagedestroy($img);
	}
}

?>