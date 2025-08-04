<?php

// Серверная часть для JS-скрипта UploadImages

class mUploadImages
{

    static $UI = 0;    // Номер UI-элемента в блоке клиента
    static $path;    // Каталог для хранения временных файлов

    // Максимальный допустимый размер картинки (по умолчанию значения берутся из конфига)
    static $imgMaxW;
    static $imgMaxH;

    static $output = OUTPUT_FRAME;

    static private $preview_file = "";

    static function main($pageInf = array())
    {
        self::$path = Config::path('temp') . '/';

        // Удаляем старые временные файлы
        $oldFiles = array_merge(
            glob(self::$path . '*.uitemp'),
            glob(self::$path . '*.jpg'),
            glob(self::$path . '*.jpeg'),
            glob(self::$path . '*.gif'),
            glob(self::$path . '*.png')
        );

        foreach ($oldFiles as $file) {
            if (filemtime($file) < time() - 3600) {
                unlink($file);
            }
        }

        // Получаем номер UI-элемента
        self::$UI = intval($_GET['UInum']);

        if ($_GET['type'] == 'drop' && trim($_GET['src'])) {
            // Загрузка файла с удалённого сервера
            self::getRemote();
        }

        // Проверяем успешность загрузки
        $check = self::check();

        if ($check !== true) {
            // Ошибка
            return self::answer('0|' . self::$UI . '|' . $check . '|');
        }

        // Если всё ок, сохраняем временный файл и выводим ссылку на него
        list($fname, $link, $preview) = self::save();

        self::$preview_file = $preview;

        return self::answer('1|' . self::$UI . '|' . $fname . '|' . $link);
    }


    // Вывод результата
    static function answer($answer)
    {
        if ($_GET['type'] == 'iframe') {
            return '
				<script>
				window.parent.UploadImages.uploadResult(\'' . str_replace('\'', '\\\'', $answer) . '\');
				</script>';
        } elseif ($_GET['type'] == 'json') {
            $e  =explode("|", $answer);
            return json_encode([
                'success' => $e[0],
                'ui' => $e[1],
                'message' => $e[2],
                'link' => $e[3],
                'preview' => self::$preview_file
            ]);
        } else {
            return $answer;
        }
    }


    // Проверка загруженного файла
    static function check()
    {
        if ($_FILES['image']['error'] != UPLOAD_ERR_OK) {
            // Ошибка при загрузке файла
            if (trim($_GET['src']) && $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
                return 'Ошибка: не удалось загрузить файл по адресу ' . trim($_GET['src']);
            } else {
                if ($_FILES['image']['error'] == UPLOAD_ERR_INI_SIZE || $_FILES['image']['error'] == UPLOAD_ERR_FORM_SIZE) {
                    return 'Ошибка: размер файла превышает допустимый размер';
                }
                if ($_FILES['image']['error'] == UPLOAD_ERR_PARTIAL) {
                    return 'Ошибка: загружаемый файл получен только частично';
                }
                if ($_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
                    return 'Ошибка: не удалось загрузить файл';
                }
                return 'Неизвестная ошибка ' . $_FILES['image']['error'];
            }
        }

        // Проверяем, заданы ли макс. размеры картинки
        if (self::$imgMaxW == null || self::$imgMaxH == null) {
            self::$imgMaxW = Config::$img['maxW'];
            self::$imgMaxH = Config::$img['maxH'];
        }

        if (!isset($_FILES['image']) || !isset($_FILES['image']['tmp_name'])) {
            return 'Нет файлов в списке загрузок';
        }

        // Проверяем тип файла и размер картинки
        $check = Images::checkImage($_FILES['image']['tmp_name'], self::$imgMaxW, self::$imgMaxH);

        if ($check == IMG_CHECK_ERR_TYPE) {
            return 'Загрузите изображение правильного формата. Нужен файл JPG, GIF, или PNG.';
        }
        if ($check == IMG_CHECK_ERR_SIZE) {
            return 'Слишком большая картинка. Максимум ' . self::$imgMaxW . 'x' . self::$imgMaxH . ' точек.';
        }

        return true;
    }


    // Сохранение временной картинки
    static function save()
    {
        $e = explode('.', $_FILES['image']['name']);
        $ext = end($e);

        $xname = strtolower(md5(rand(1, 999999999999)));
        $fname = self::$path . $xname . '.' . $ext;
        $fname80 = self::$path . $xname . '_80x60_0.' . $ext;

        file_put_contents(self::$path . 'upload.log', $fname, FILE_APPEND);

        // Копируем файл в нужное место
        copy($_FILES['image']['tmp_name'], $fname);
        copy($_FILES['image']['tmp_name'], $fname80);
        unlink($_FILES['image']['tmp_name']);

        $oImages = new Images();
        $origImg = $oImages->fromFile($fname80);
        $requestImg = $oImages->resize(
            $origImg,
            80,
            60,
            IMG_RESIZE_CROP,
            false
        );

        $mime = Images::$extToMime[$ext] ?? 'image/jpeg';
        $oImages->toFile($requestImg, $fname80, $mime);

        // Возвращаем ссылку на файл для вывода его в браузере
        $link = str_replace(_ROOT, '', $fname);
        $linkPreview = str_replace(_ROOT, '', $fname80);
        return array($fname, $link, $linkPreview);
    }


    // Загрузка файла с удалённого сервера
    static function getRemote()
    {
        $remoteUrl = trim($_GET['src']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $remoteUrl);
        $bin = curl_exec($ch);

        if (!curl_error($ch) && $bin) {
            $fTmp = self::$path . strtolower(md5(rand(1, 999999999999))) . '.uitemp';
            file_put_contents($fTmp, $bin);

            $_FILES['image']['name'] = end(explode('/', array_shift(explode('?', $remoteUrl))));
            $_FILES['image']['size'] = filesize($fTmp);
            $_FILES['image']['tmp_name'] = $fTmp;

            $imgInfo = getimagesize($fTmp);
            $_FILES['image']['type'] = $imgInfo['mime'];
            $_FILES['image']['error'] = UPLOAD_ERR_OK;
        } else {
            $_FILES['image']['error'] = UPLOAD_ERR_NO_FILE;
        }
    }
}
