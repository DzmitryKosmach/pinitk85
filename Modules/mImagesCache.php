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
        $isWebpRequest = ($requestExt === 'webp');

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
            in_array($origExt, array('png', 'gif')) && in_array($requestExt, array('png', 'gif', 'webp'))
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
        if ($isWebpRequest) {
            // WEBP: сохраняем напрямую, не трогая общий стек форматов Images::$extToMime
            if (Config::$img['lib'] === 'gd' && function_exists('imagewebp')) {
                @imagewebp($requestImg, $requestFile, 85);
            } elseif (Config::$img['lib'] === 'imagick' && class_exists('\\Imagick')) {
                try {
                    /** @var \Imagick $requestImg */
                    $imgWebp = clone $requestImg;
                    $imgWebp->setImageFormat('webp');
                    if (method_exists($imgWebp, 'setImageCompressionQuality')) {
                        $imgWebp->setImageCompressionQuality(85);
                    }
                    $imgWebp->writeImage($requestFile);
                    $imgWebp->destroy();
                } catch (\Throwable $e) {
                    self::err(5);
                }
            } else {
                self::err(6);
            }
        } else {
            $mime = isset(Images::$extToMime[$requestExt]) ? Images::$extToMime[$requestExt] : 'image/jpeg';
            $oImages->toFile($requestImg, $requestFile, $mime);
        }

        // Для jpeg-кропа дополнительно формируем webp-кроп с тем же именем и размерами.
        if (
            ($requestExt === 'jpg' || $requestExt === 'jpeg') &&
            (
                (Config::$img['lib'] === 'gd' && function_exists('imagewebp')) ||
                (Config::$img['lib'] === 'imagick' && class_exists('Imagick'))
            )
        ) {
            $webpFile = $fileInf['dirname'] . '/' . $fileInf['filename'] . '.webp';
            if (Config::$img['lib'] === 'gd') {
                @imagewebp($requestImg, $webpFile, 85);
            } else {
                try {
                    /** @var Imagick $requestImg */
                    $imgWebp = clone $requestImg;
                    $imgWebp->setImageFormat('webp');
                    if (method_exists($imgWebp, 'setImageCompressionQuality')) {
                        $imgWebp->setImageCompressionQuality(85);
                    }
                    $imgWebp->writeImage($webpFile);
                    $imgWebp->destroy();
                } catch (\Throwable $e) {
                    // игнорируем — webp не критичен для ответа
                }
            }
        }

        // выводим результат
        if ($isWebpRequest) {
            header('Content-type: image/webp');
            if (Config::$img['lib'] === 'gd' && function_exists('imagewebp')) {
                imagewebp($requestImg);
            } else {
                /** @var \Imagick $requestImg */
                echo $requestImg;
            }
        } else {
            $oImages->output($requestImg, $mime);
        }

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
