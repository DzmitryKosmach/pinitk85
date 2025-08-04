<?php

// Класс для работы с графикой

// Ошибки при проверке типа изображения
define('IMG_CHECK_ERR_TYPE', 1);
define('IMG_CHECK_ERR_SIZE', 2);

// Методы ресайза
define('IMG_RESIZE_CROP', 0);
define('IMG_RESIZE_ADD_MARGINS', 1);
define('IMG_RESIZE_AUTO', 2);

class Images
{
    static $extToMime = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    );

    // Объект для манипуляций изображениями
    var $ctrl;


    // В конструкторе создаётся объект $ctrl класса Images_CtrlGd или Images_CtrlImagick для манипуляции с изображениями
    // Класс выбирается в соответствии с конфигом сайта
    function __construct()
    {
        if (Config::$img['lib'] == 'gd') {
            $this->ctrl = new Images_CtrlGd();
        } elseif (Config::$img['lib'] == 'imagick') {
            $this->ctrl = new Images_CtrlImagick();
        } else {
            trigger_error('Config of images library can be "gd" or "imagick"', E_USER_WARNING);
            exit;
        }
    }


    /**
     * Проверяет, является ли файл картинкой (любого или заданного типа) и вписывается ли она в указанные размеры
     * $requiredFormat: расширение требуемого формата (или массив расширений)
     *
     * @param string $fileName
     * @param int    $maxW
     * @param int    $maxH
     * @param array  $requiredFormat
     * @return int|mixed|string
     */
    static function checkImage(string $fileName, int $maxW, int $maxH, array $requiredFormat = [])
    {
        // Определяем перечень требуемых форматов
        if (!is_array($requiredFormat)) {
            $requiredFormat = array($requiredFormat);
        }
        if (!count($requiredFormat)) {
            $requiredFormat = array_keys(self::$extToMime);
        }

        // Определяем расширение файла (причём, не по имени файла, а по его содержимому)
        $imgInfo = getimagesize($fileName);
        $fileExt = array_search($imgInfo['mime'], self::$extToMime);

        if (!in_array($fileExt, $requiredFormat)) {
            // Файл неправильного типа
            return IMG_CHECK_ERR_TYPE;
        } else {
            // Файл правильного типа
            // Проверяем размер изображения
            if ($imgInfo[0] > $maxW || $imgInfo[1] > $maxH) {
                // Размер превышает допустимый
                return IMG_CHECK_ERR_SIZE;
            }
        }

        // В случае успешной проверки возвращается mime-тип изображения
        return $imgInfo['mime'];
    }

    // Масштабирует картинку;
    // $canvasW и $canvasH - размеры, под которые производится масштабирование.
    // Один из них или оба могут быть не указаны: тогда они будут вычисляться автоматически из имеющихся данных
    // $resizeMethod == IMG_RESIZE_CROP: обрезание краёв;
    // $resizeMethod == IMG_RESIZE_ADD_MARGINS: добавление белых полей;
    // $resizeMethod == IMG_RESIZE_AUTO: - картинка уменьшается в заданный размер без обрезания и без добавл. полей
    // $transparent - требуется ли сохранить свойства прозрачности при масштабировании
    function resize(&$img, $canvasW, $canvasH, $resizeMethod = IMG_RESIZE_CROP, $transparent = false)
    {
        $s = $this->ctrl->size($img);
        $sourceW = $s[0];
        $sourceH = $s[1];
        $canvasW = intval($canvasW);
        $canvasH = intval($canvasH);

        if ($canvasW && $canvasH) {
            // ЗАДАНЫ ШИРИНА И ВЫСОТА ХОЛСТА
            if (IMG_RESIZE_CROP == $resizeMethod) {
                // ОБРЕЗАНИЕ
                if ($canvasW < $sourceW && $canvasH < $sourceH) {
                    // Холст меньше оригинала
                    if ($canvasW / $sourceW < $canvasH / $sourceH) {
                        // Обрез по-бокам
                        $targetW = round($canvasH * $sourceW / $sourceH);
                        $targetH = $canvasH;
                    } else {
                        // Обрез сверху и снизу
                        $targetW = $canvasW;
                        $targetH = round($canvasW * $sourceH / $sourceW);
                    }
                } else {
                    // Холст больше оригинала (по крайней мере по одной стороне), обрезание не требуется
                    $targetW = $sourceW;
                    $targetH = $sourceH;
                }
            } else/*if(IMG_RESIZE_ADD_MARGINS == $resizeMethod || IMG_RESIZE_AUTO == $resizeMethod)*/ {
                // ПОЛЯ
                if ($canvasW < $sourceW && $canvasH < $sourceH) {
                    // Холст меньше оригинала
                    if ($canvasW / $sourceW < $canvasH / $sourceH) {
                        // Поля сверху и снизу
                        $targetW = $canvasW;
                        $targetH = round($canvasW * $sourceH / $sourceW);
                    } else {
                        // Поля по-бокам
                        $targetW = round($canvasH * $sourceW / $sourceH);
                        $targetH = $canvasH;
                    }
                } elseif ($canvasW < $sourceW) {
                    // Холст меньше оригинала только по ширине
                    // Поля будут сверху и внизу
                    $targetW = $canvasW;
                    $targetH = round($canvasW * $sourceH / $sourceW);
                } elseif ($canvasH < $sourceH) {
                    // Холст меньше оригинала только по высоте
                    // Поля будут по-бокам
                    $targetW = round($canvasH * $sourceW / $sourceH);
                    $targetH = $canvasH;
                } else {
                    // Холст больше оригинала, поля будут со всех сторон
                    $targetW = $sourceW;
                    $targetH = $sourceH;
                }
            }
        } elseif ($canvasW) {
            // ЗАДАНА ТОЛЬКО ШИРИНА ХОЛСТА
            if (IMG_RESIZE_CROP == $resizeMethod || ((IMG_RESIZE_ADD_MARGINS == $resizeMethod || IMG_RESIZE_AUTO == $resizeMethod) && $canvasW > $sourceW)) {
                // ОБРЕЗАНИЕ или (ПОЛЯ + ХОЛСТ_БОЛЬШЕ_ИСХОДНИКА)
                $targetW = $sourceW;
                $targetH = $canvasH = $sourceH;
            } else {
                // ПОЛЯ + ХОЛСТ_МЕНЬШЕ_ИСХОДНИКА
                $targetW = $canvasW;
                $targetH = $canvasH = round($canvasW * $sourceH / $sourceW);
            }
        } elseif ($canvasH) {
            // ЗАДАНА ТОЛЬКО ВЫСОТА ХОЛСТА
            if (!IMG_RESIZE_CROP == $resizeMethod || ((IMG_RESIZE_ADD_MARGINS == $resizeMethod || IMG_RESIZE_AUTO == $resizeMethod) && $canvasH > $sourceH)) {
                // ОБРЕЗАНИЕ или (ПОЛЯ + ХОЛСТ_БОЛЬШЕ_ИСХОДНИКА)
                $targetW = $canvasW = $sourceW;
                $targetH = $sourceH;
            } else {
                // ПОЛЯ + ХОЛСТ_МЕНЬШЕ_ИСХОДНИКА
                $targetW = $canvasW = round($canvasH * $sourceW / $sourceH);
                $targetH = $canvasH;
            }
        } else {
            // НЕ ЗАДАНА НИ ШИРИНА, НИ ВЫСОТА ХОЛСТА, МАСШТАБИРОВАНИЕ НЕ ПОТРЕБУЕТСЯ
            $targetW = $canvasW = $sourceW;
            $targetH = $canvasH = $sourceH;
        }

        if (IMG_RESIZE_AUTO == $resizeMethod) {
            $offsetX = $offsetY = 0;
            $canvasW = $targetW;
            $canvasH = $targetH;
        } else {
            $offsetX = round(($canvasW - $targetW) / 2);
            $offsetY = round(($canvasH - $targetH) / 2);
        }

        return $this->ctrl->resize(
            $img,
            $canvasW,
            $canvasH,
            $offsetX,
            $offsetY,
            $targetW,
            $targetH,
            $sourceW,
            $sourceH,
            $transparent
        );
    }


    // Конвертирует изображение из файла в заданный формат и сохраняет его в другой файл
    function convert($fSource, $fDest, $newFormat = 'jpg')
    {
        $newMime = self::$extToMime[$newFormat];
        $oldMime = getimagesize($fSource);
        $oldMime = $oldMime['mime'];

        if ($oldMime != $newMime) {
            $img = $this->ctrl->fromFile($fSource, $oldMime);
            $this->ctrl->toFile($img, $fDest, $newMime);
            $this->ctrl->destroy($img);
        } else {
            copy($fSource, $fDest);
        }
    }

    // Очистка устаревших копий картинок
    // $absolut - безусловное удаление всего кеша
    function cacheClean($absolut = false, $filePattern = false)
    {
        // TODO: вместо полного пути $filePattern лучше принимать только его часть, относительно папки для картинок (заданной в конфиге)
        if ($filePattern === false) {
            $filePattern = Config::path('images') . '/*/*x*.{gif,jpg,jpeg,png,GIF,JPG,JPEG,PNG}';
        }

        $files = glob($filePattern);
        foreach ($files as $file) {
            if ($absolut || !filesize($file)) {
                unlink($file);
            } else {
                $f1 = pathinfo($file);
                $path = $f1['dirname'];
                $ext = $f1['extension'];
                $f0 = explode('_', $f1['filename']);
                $f0 = $path . '/' . $f0[0] . '.';
                $file0 = $f0 . $ext;

                $no_file = false;
                if (!is_file($file0)) {
                    $no_file = true;
                    foreach (self::$extToMime as $ext => $m) {
                        $file0 = $f0 . $ext;
                        if (is_file($file0)) {
                            $no_file = false;
                            break;
                        }
                    }
                }

                if ($no_file || filemtime($file0) > filemtime($file)) {
                    unlink($file);
                }
            }
        }
    }


    // Загружает изображение заданного формата из файла, возвращает переменную (или объект) с изображением
    function fromFile($file, $mime = false)
    {
        return $this->ctrl->fromFile($file, $mime);
    }

    // Сохраняет изображение в файл
    function toFile($img, $file, $mime)
    {
        $this->ctrl->toFile($img, $file, $mime);
    }

    // Выводит изображение в браузер
    function output($img, $mime, $h = true)
    {
        $this->ctrl->output($img, $mime, $h);
    }

    // Уничтожаем объект-картинку
    function destroy($img)
    {
        $this->ctrl->destroy($img);
    }
}
