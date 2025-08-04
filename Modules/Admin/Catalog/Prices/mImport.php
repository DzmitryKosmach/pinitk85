<?php

/** Админка: Импорт прайса
 * @author    Seka
 */

class mImport extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var int
     */
    var $rights = Administrators::R_CATALOG;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $oImport = new Catalog_Prices_Import();

        if (intval($_GET['execute']) && $oImport->isDataLoaded()) {
            // Выполняем импорт
            self::import();
            exit;
        } elseif (intval($_GET['preview']) && $oImport->isDataLoaded()) {

            // Отображаем превью изменений, ожидаем подтверждения
            list($updSeries, $updItems) = $oImport->getDataLoaded();
            //print_array($updSeries); print_array($updItems);

            $tpl = Pages::tplFile($pageInf, 'preview');
            return pattExeP(fgc($tpl), array(
                'updSeries' => $updSeries,
                'updItems' => $updItems
            ));
        } else {
            // Форма загрузки файла
            $oImport->clearDataLoaded();

            $tpl = Pages::tplFile($pageInf);
            $formHtml = pattExeP(fgc($tpl), array());

            // Выводим форму
            $frm = new Form($formHtml);
            $frm->adminMode = true;
            return $frm->run('mImport::parse', 'mImport::check');
        }
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        //return true;

        // Проверяем формат файла
        $oImport = new Catalog_Prices_Import();
        $check = $oImport->checkFile($_FILES['file']);

        if ($check !== true) {
            return array(
                array(
                    'name' => 'file',
                    'msg' => $check
                )
            );
        }

        return true;
    }


    /** Парсим загруженный файл
     * @static
     * @param $initData
     * @param $newData
     */
    static function parse($initData, $newData)
    {
        $oImport = new Catalog_Prices_Import();
        $oImport->loadData($_FILES['file']['tmp_name']);

        header('Location: ' . Url::a('admin-catalog-import') . '?preview=1');
        exit;
    }


    /** Выплняем импорт прайса
     * @static
     */
    static function import()
    {
        $oImport = new Catalog_Prices_Import();
        if ($oImport->saveData2DB()) {
            Pages::flash('Прайс успешно импортирован.', false, Url::a('admin-catalog-import'));
        } else {
            Pages::flash(
                'При импорте прайса произошла ошибка. Данные не были сохранены в БД.',
                true,
                Url::a('admin-catalog-import')
            );
        }
        exit;
    }
}
