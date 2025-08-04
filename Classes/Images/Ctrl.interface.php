<?php

// Интефейс для классво манипуляции изображениями

interface Images_Ctrl {

	// Возвращает размеры картинки
	function size(&$img);

	// Ресайз картинки. В этой функции ничего не вычисляется, т.к. все параметры сюда передаются при вызове
	function resize(
		&$img,
		$canvasW, $canvasH,
		$offsetX, $offsetY,
		$targetW, $targetH,
		$sourceW, $sourceH,
		$transparent = false
	);

	// Загружает изображение заданного формата из файла, возвращает переменную (или объект) с изображением
	function fromFile($file, $mime = false);

	// Сохраняет изображение в файл
	function toFile($img, $file, $mime);

	// Выводит изображение в браузер
	function output($img, $mime, $h = true);

	// Уничтожаем объект-картинку
	function destroy($img);
}

?>