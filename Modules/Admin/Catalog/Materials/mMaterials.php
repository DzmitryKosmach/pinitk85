<?php

/** Админка: Материалы
 * @author    Seka
 */

class mMaterials extends Admin
{

    /**
     * @var int
     */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Materials';

    /**
     * @var int
     */
    var $rights = Administrators::R_CATALOG;

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
        $o->getOperations();

        $oMaterials = new Catalog_Materials();

        // Находим текущий материал-родитель, если он задан
        $parentId = intval($_GET['p']);
        $parentMat = false;
        if ($parentId) {
            $parentMat = $oMaterials->getRow('*', '`id` = ' . $parentId);

            if (!intval($parentMat['has_sub'])) {
                Pages::flash(
                    'Запрошенный материал не может содержать подматериалы.',
                    true,
                    Url::a('admin-catalog-materials')
                );
                exit;
            }

            $matsDeepLevel = 1 + $oMaterials->getDeepLevel($parentId);
        } else {
            $matsDeepLevel = 1;
        }

        $search = array(
            '`parent_id` = ' . $parentId
        );

        $_GET['supplier_id'] = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
        self::$supplierId = $_GET['supplier_id'];

        // Поиск по поставщику (только для мат. верхнего уровня)
        if ($parentId == 0 && trim($_GET['supplier_id']) !== '') {
            $search[] = '`supplier_id` = ' . intval($_GET['supplier_id']);
        }

        // Получаем материалы
        $materials = $oMaterials->imageExtToData(
            $oMaterials->get(
                '*',
                implode(' AND ', $search),
                'order'
            )
        );

        // Получаем для каждого материала к-во подматериалов и к-во серий, где он используется
        $mIds = array();
        foreach ($materials as $m) {
            $mIds[] = $m['id'];
        }
        if (count($mIds)) {
            if ($matsDeepLevel < Catalog_Materials::MAX_DEEP_LEVEL) {
                // Подматериалы (не для последнего уровня)
                $subCnt = $oMaterials->getHash(
                    'parent_id, COUNT(*)',
                    '`parent_id` IN (' . implode(',', $mIds) . ')',
                    '',
                    0,
                    '',
                    'parent_id'
                );
            }
            if (!$parentMat) {
                // Серии (только для верхнего уровня)
                $oSeries2Materials = new Catalog_Series_2Materials();
                $seriesCnt = $oSeries2Materials->getHash(
                    'material_id, COUNT(*)',
                    '`material_id` IN (' . implode(',', $mIds) . ') AND `cs`.out_of_production = 0',
                    '`' . Catalog_Series_2Materials::$tab . '`.id',
                    0,
                    'JOIN `' . Catalog_Series::$tab . '` AS `cs` ON (`cs`.id = `' . Catalog_Series_2Materials::$tab . '`.series_id)',
                    'material_id'
                );
            }
            foreach ($materials as &$m) {
                if ($matsDeepLevel < Catalog_Materials::MAX_DEEP_LEVEL) {
                    $m['sub_cnt'] = isset($subCnt[$m['id']]) ? intval($subCnt[$m['id']]) : 0;
                }
                if (!$parentMat) {
                    $m['series_cnt'] = isset($seriesCnt[$m['id']]) ? intval($seriesCnt[$m['id']]) : 0;
                }
            }
            unset($m);
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

        // Выводим шаблон
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'parentMat' => $parentMat,
            'matsDeepLevel' => $matsDeepLevel,
            'materials' => $materials,
            'suppliers' => $suppliers,
            'supplier_id' => self::$supplierId,
        ));
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId)
    {
        $_GET['supplier_id'] = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

        $oMaterials = new Catalog_Materials();
        $oMaterials->del(intval($iId));
        Pages::flash('Материал успешно удалён.',
            false,
            Url::buildUrl(Url::a('admin-catalog-materials'), []) .
            ($_GET['supplier_id'] > 0 ? '?supplier_id=' . $_GET['supplier_id'] : '')
        );
    }
}
