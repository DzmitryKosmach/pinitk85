<?php

/** Админка: добавление / редактирование материала
 * @author    Seka
 */


class mMaterialsEdit extends Admin
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
     * @var int
     */
    static $pId;

    /**
     * @var int
     */
    static $parentId;

    /**
     * @var int
     */
    static $matDeepLevel;

    static int $supplierId = 0;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();

        $oMaterials = new Catalog_Materials();

        self::$supplierId = isset($_GET['supplier']) ? (int)$_GET['supplier'] : 0;

        if (self::$pId = intval($_GET['id'])) {
            // Редактирование
            $init = $oMaterials->imageExtToData(
                $oMaterials->getRow(
                    '*',
                    '`id` = ' . self::$pId
                )
            );

            if ($init === false) {
                Pages::flash(
                    'Запрошенный для редактирования материал не найден.',
                    true,
                    Url::a('admin-catalog-materials')
                );
            }
            self::$parentId = $init['parent_id'];
        } else {
            // Добавление
            self::$parentId = intval($_GET['p']);
            $init = array(
                'has_sub' => 0
            );

            // Проверка что указанный родительский материал существует
            if (self::$parentId) {
                if (!$oMaterials->getCount('`id` = ' . self::$parentId)) {
                    Pages::flash(
                        'При добавлении подварианта материала не найден указанный родительский материал.',
                        true,
                        Url::a('admin-catalog-materials')
                    );
                }
            }
        }

        // Определяем уровень сложенности создаваемого/редактируемого материала
        self::$matDeepLevel = 1 + $oMaterials->getDeepLevel(self::$parentId);

        if (self::$parentId) {
            $parentMat = $oMaterials->getRow('*', '`id` = ' . self::$parentId);
        } else {
            $parentMat = false;
        }

        if (!$parentMat) {
            // Поставщики (только для верхнего уровня)
            $oSuppliers = new Catalog_Suppliers();
            $suppliers = $oSuppliers->getHash(
                'id, name',
                '',
                '`name` ASC'
            );
        } else {
            $suppliers = array();
        }

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'init' => $init,
            'parentMat' => $parentMat,
            'suppliers' => $suppliers,
            'matDeepLevel' => self::$matDeepLevel,
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        $frm->setInit($init);
        return $frm->run('mMaterialsEdit::save', 'mMaterialsEdit::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        // Проверка файла картинки
        $imgCheck = Form::checkUploadedImage(
            self::$pId,
            'image',
            3200,
            2400,
            false
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
        if (self::$matDeepLevel < Catalog_Materials::MAX_DEEP_LEVEL) {
            $hasSubcats = intval($newData['has_sub']) ? 1 : 0;
        } else {
            $hasSubcats = 0;
        }
        $save = array(
            'name' => $newData['name'],
            'parent_id' => self::$parentId,
            'has_sub' => $hasSubcats
        );
        if (!self::$parentId) {
            $save['supplier_id'] = intval($newData['supplier_id']);
        }

        $oMaterials = new Catalog_Materials();

        if (self::$pId) {
            // Редактирование
            $oMaterials->upd(self::$pId, $save);

            $msg = 'Изменения сохранены.';
        } else {
            // Добавление
            self::$pId = $oMaterials->add($save);

            $msg = 'Материал успешно добавлен.';
        }

        // Save image
        if ($_FILES['image']['name']) {
            $oMaterials->imageSave(
                self::$pId,
                $_FILES['image']['tmp_name']
            );
        } elseif (intval($newData['image-del'])) {
            $oMaterials->imageDel(self::$pId);
        }

        Pages::flash(
            $msg,
            false,
            Url::buildUrl(Url::a('admin-catalog-materials'), array(
                'p' => self::$parentId
            )) . (
            $save['supplier_id'] > 0 ? "?supplier_id=" . $save['supplier_id'] : ""
            )
        );
    }
}
