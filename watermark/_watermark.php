<?php

waterMark($_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI']);

function waterMark($original)
{

	$info_o = @getImageSize($original);
	if (!$info_o)
		return false;
	$info_w = @getImageSize('watermark.png');
	$info_w2 = @getImageSize('watermark2.png');
	$info_w3 = @getImageSize('watermark3.png');
	if (!$info_w)
		return false;	
    if (!$info_w2)
		return false; 
    if (!$info_w3)
		return false;

	header("Content-Type: ".$info_o['mime']);

	$original = @imageCreateFromString(file_get_contents($original));
	$watermark = @imagecreatefrompng("watermark.png");
	$watermark2 = @imagecreatefrompng("watermark2.png");
	$watermark3 = @imagecreatefrompng("watermark3.png");
	$out = imageCreateTrueColor($info_o[0],$info_o[1]);

	imageCopyMerge($out, $original, 0, 0, 0, 0, $info_o[0], $info_o[1], 100);
	// Водяной знак накладываем только на изображения больше 250 пикселей по вертикали и по горизонтали
	//товары
		if( ($info_o[0] > 1001 && $info_o[0] <= 4000) && ($info_o[1] > 1001 && $info_o[1] <= 3000) )
	{
		// Для изображений без альфа-канала
		// Последний параметр функции - степень непрозрачности водяного знака
		imageCopyMerge($out, $watermark3, ($info_o[0]-$info_w3[0])/2, ($info_o[1]-$info_w3[1])/2, 0, 0, $info_w3[0], $info_w3[1], 15);

		// Для изображений с альфа-каналом
		// В этом случае прозрачность регулируется альфа-каналом самого изображения
		// imageCopy($out, $watermark, ($info_o[0]-$info_w[0])/2, ($info_o[1]-$info_w[1])/2, 0, 0, $info_w[0], $info_w[1]);
	}
    //большие картинки
	if( ($info_o[0] > 400 && $info_o[0] <= 1500) && ($info_o[1] > 201 && $info_o[1] <= 1500) )
	{
		// Для изображений без альфа-канала
		// Последний параметр функции - степень непрозрачности водяного знака
		imageCopyMerge($out, $watermark2, ($info_o[0]-$info_w2[0])/2, ($info_o[1]-$info_w2[1])/2, 0, 0, $info_w2[0], $info_w2[1], 40);

		// Для изображений с альфа-каналом
		// В этом случае прозрачность регулируется альфа-каналом самого изображения
		// imageCopy($out, $watermark, ($info_o[0]-$info_w[0])/2, ($info_o[1]-$info_w[1])/2, 0, 0, $info_w[0], $info_w[1]);
	}
    //маленькие картинки
    if( ($info_o[0] > 100 && $info_o[0] <= 250) && ($info_o[1] > 100 && $info_o[1] <= 200) )
	{
		// Для изображений без альфа-канала
		// Последний параметр функции - степень непрозрачности водяного знака
		imageCopyMerge($out, $watermark, ($info_o[0]-$info_w[0])/2, ($info_o[1]-$info_w[1])/2, 0, 0, $info_w[0], $info_w[1], 15);

		// Для изображений с альфа-каналом
		// В этом случае прозрачность регулируется альфа-каналом самого изображения
		// imageCopy($out, $watermark, ($info_o[0]-$info_w[0])/2, ($info_o[1]-$info_w[1])/2, 0, 0, $info_w[0], $info_w[1]);
	}

	switch ($info_o[2])
	{
	case 1:
		imageGIF($out);
		break;
	case 2:
		imageJPEG($out);
		break;
	case 3:
		imagePNG($out);
		break;
	default:
		return false;
	}

	imageDestroy($out); 
	imageDestroy($original); 
	imageDestroy($watermark); 
	imageDestroy($watermark2); 
	imageDestroy($watermark3); 

	return true; 
} 

?>