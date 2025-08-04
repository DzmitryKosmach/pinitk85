<?php

/** Админка: Фотографии серий
 *
 * @author    Seka
 */

class mPhotos extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Series_Photos';

    /**
     * @var int
     */
    var $rights = Administrators::R_CATALOG;

    /**
     * @var int
     */
    static $seriesId;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();
        $o->getOperations();

        // Получаем инфу о серии
        self::$seriesId = intval($_GET['s']);
        $oSeries = new Catalog_Series();
        $seriesInf = $oSeries->getRow('*', '`id` = ' . self::$seriesId);
        if (!$seriesInf) {
            Pages::flash('Не найдена серия для просмотра списка фотографий.', true, self::retURL());
            exit;
        }

        // Получаем фотографии
        $oPhotos = new Catalog_Series_Photos();
        $photos = $oPhotos->imageExtToData(
            $oPhotos->get(
                '*',
                '`series_id` = ' . self::$seriesId,
                'order'
            )
        );

        // Инициализируем класс Images.class.php, т.к. в шаблоне нужны значения define переменных
        $im = new Images();

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'seriesInf' => $seriesInf,
            'photos' => $photos
        ));

        // Выводим форму
        $frm = new Form($formHtml);
        $frm->setInit();
        return $frm->run('mPhotos::save', false);
    }


    /** Получаем URL для возврата к списку серий
     * @static
     * @return string
     */
    static function retURL()
    {
        if (isset($_GET['ret']) && trim($_GET['ret'])) {
            return $_GET['ret'];
        } else {
            return Url::a('admin-catalog-series');
        }
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        // Проверка файла картинки
        $imgCheck = Form::checkUploadedImage(
            0,
            'image',
            3200,
            2400,
            true
        );
        if ($imgCheck !== true) {
            return array($imgCheck);
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        if (!isset($_POST['uploadedImages']) || !trim($_POST['uploadedImages'])) {
            Pages::flash( 'Список фотографий пуст.', true);
            return;
        }

        $oPhotos = new Catalog_Series_Photos();

        $files = explode(",", $_POST['uploadedImages']);

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            // Создаем в БД запись о картинке
            $id = $oPhotos->add(array(
                'series_id' => self::$seriesId,
                'alt' => $newData['alt'],
                'rm' => intval($newData['rm'])
            ));

            // Костыль из-за кривой работы функций
            if (is_null($id) || $id==0) {
                $res = $oPhotos->query(
                    'select id from catalog_series_photos
                where series_id=' . self::$seriesId .
                    ' order by id desc limit 1');

                if (is_array($res) && isset($res[0]['id'])) {
                    $id = $res[0]['id'];
                } else {
                    Pages::flash('Фотография ' . basename($file). ' НЕ сохранена.');
                    return;
                }
            }

            // Save image
            $isUploaded = $oPhotos->imageSave(
                $id,
                _ROOT . $file
            );

            if (!$isUploaded) {

                $res = $oPhotos->query(
                    'delete from catalog_series_photos
                where id=' . $id);

                Pages::flash( 'Фотография ' . basename($file). ' не может быть обработана.' . $id, true);
                return;
            }

            // Создание файлов-превью
            self::makePreviewImages($id, $oPhotos->getExt());

        }

        //dd($id, $_GET, $_POST);

        Pages::flash( count($files) == 1 ? 'Фотография успешно сохранена.' : 'Фотографии успешно сохранены.');
    }


    /**
     * Удаление картинки
     * @param $iId
     */
    function delItem($iId)
    {
        $oPhotos = new Catalog_Series_Photos();
        $oPhotos->del(intval($iId));

        Pages::flash('Фотография удалена.');
    }

    private static function makePreviewImages(int $id, string $ext): void
    {
        $size = [
            [80, 60],
            [160, 120],
            [90, 90],
        ];

        foreach ($size as $item) {
            self::makeImages($id, $item[0], $item[1], $ext);
        }
    }

    private static function makeImages(int $id, int $width, int $height, string $ext): void
    {
        // Код выдернут из mImagesCache.php
        $oImages = new Images();
        $origFile = Config::path('images') . '/Series/' . $id . '.' . $ext;
        $newName = Config::path('images') . '/Series/' . $id . '_' . $width . 'x' . $height . '_0.' . $ext;
        $origImg = $oImages->fromFile($origFile);

        $newW = $width;
        if ($newW > Config::$img['maxW']) {
            $newW = Config::$img['maxW'];
        }
        $newH = $height;
        if ($newH > Config::$img['maxH']) {
            $newH = Config::$img['maxH'];
        }
        $requestImg = $oImages->resize(
            $origImg,
            $newW,
            $newH,
            IMG_RESIZE_CROP,
            false
        );

        $mime = isset(Images::$extToMime[$ext]) ? Images::$extToMime[$ext] : 'image/jpeg';
        $oImages->toFile($requestImg, $newName, $mime);
    }
}
