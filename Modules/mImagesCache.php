<?php

// Модуль для генерации ресайзнутых картинок и работы с их кешем

class mImagesCache
{

    /**
     * @var int
     */
    static $output = OUTPUT_FRAME;

    /**
     * Файл с ватермаркой
     * Обязательно должен быть квадратным
     * @var string
     */
    protected static $watermarkFile = '/watermark.png';

    /**
     * @var float
     */
    protected static $watermarkSize = 0.4;

    /**
     * @param array $pageInf
     * @return string
     */
    static function main($pageInf = array())
    {

        $oImages = new Images();

        // Определяем запрашиваемый файл
        $requestFile = $_SERVER['REQUEST_URI'];

        // патч под локаль
        //$requestFile = rtrim($requestFile, '/') . '.jpg';

        $requestFile = explode('/', $requestFile);
        $requestFile = array_reverse($requestFile);
        $requestFile[0] = array_pop(explode('-', $requestFile[0]));
        $requestFile = array_reverse($requestFile);
        $requestFile = implode('/', $requestFile);


        if (strpos($requestFile, Config::$pathsRel['images']) !== 0) {
            dd($requestFile, Config::$pathsRel['images']);
            return '';
        }

        $requestFile = _ROOT . $requestFile;

        // Получаем инфу о файле
        $fileInf = pathinfo($requestFile);

        // Определяем характеристики запроса (размер, ресайз-метод)
        $request = explode('_', mb_strtolower($fileInf['filename']));
        if (count($request) != 3 && count($request) != 4) {
            self::err(2);
        }
        $requestSize = explode('x', $request[1]);
        if (count($requestSize) != 2) {
            self::err(3);
        }

        $requestResizeMethod = intval($request[2]);
        if ($requestResizeMethod != IMG_RESIZE_ADD_MARGINS && $requestResizeMethod != IMG_RESIZE_CROP && $requestResizeMethod != IMG_RESIZE_AUTO) {
            $requestResizeMethod = IMG_RESIZE_CROP;
        }

        $requestExt = $origExt = mb_strtolower($fileInf['extension']);

        // Имя исходного файла
        $origFile = $fileInf['dirname'] . '/' . $request[0] . '.' . $origExt;
        if (!is_file($origFile)) {
            $origFileNotFound = true;
            foreach (Images::$extToMime as $origExt => $m) {
                $origFile = $fileInf['dirname'] . '/' . $request[0] . '.' . $origExt;
                if (is_file($origFile)) {
                    $origFileNotFound = false;
                    break;
                }

                $origFile = $fileInf['dirname'] . '/' . mb_strtoupper($request[0]) . '.' . $origExt;
                if (is_file($origFile)) {
                    $origFileNotFound = false;
                    break;
                }
            }
            if ($origFileNotFound) {
                self::err(4);
            }
        }


        // Ресайзим исходный файл
        $origImg = $oImages->fromFile($origFile);

        $newW = abs(intval($requestSize[0]));
        if ($newW > Config::$img['maxW']) {
            $newW = Config::$img['maxW'];
        }
        $newH = abs(intval($requestSize[1]));
        if ($newH > Config::$img['maxH']) {
            $newH = Config::$img['maxH'];
        }
        $requestImg = $oImages->resize(
            $origImg,
            $newW,
            $newH,
            $requestResizeMethod,
            in_array($origExt, array('png', 'gif')) && in_array($requestExt, array('png', 'gif'))
        );


        // Ватермарк
        // ВНИМАНИЕ! КОД НАПИСАН ПОД GD2 !!!!
        if (isset($request[3]) && $request[3] == 'wm') {
            list($imgW, $imgH) = $oImages->ctrl->size($requestImg);

            $wmImg = $oImages->fromFile(Config::path('images') . self::$watermarkFile);
            list($wmWOrig, $wmHOrig) = $oImages->ctrl->size($wmImg);

            $wmSize = min($imgW * self::$watermarkSize, $imgH * self::$watermarkSize);

            imagecopyresampled(
                $requestImg,
                $wmImg,
                $imgW - $wmSize,
                $imgH - $wmSize,
                0,
                0,
                $wmSize,
                $wmSize,
                $wmWOrig,
                $wmHOrig
            );
        }

        // сохраняем результат
        $mime = isset(Images::$extToMime[$requestExt]) ? Images::$extToMime[$requestExt] : 'image/jpeg';
        $oImages->toFile($requestImg, $requestFile, $mime);

        // выводим результат
        $oImages->output($requestImg, $mime);

        // Удаляем временные данные
        $oImages->destroy($origImg);
        $oImages->destroy($requestImg);

        exit;
    }

    // Функция для вывода ошибки
    function err($n)
    {
        header('HTTP/1.0 404', true, 404);
        print $_SERVER['REQUEST_URI'] . '<br>';
        exit('Error 404: File not found (' . $n . ')');
    }
}
