<?php

/** Админка: Названия характеристик серий в категории
 *
 * @author    Seka
 */

class mOptions extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::CATALOG;

    /**
     * @var string
     */
    var $mainClass = 'Catalog_Categories_Opts4Series';

    /**
     * @var int
     */
    var $rights = Administrators::R_CATALOG;

    /**
     * @var    int
     */
    static $catId;


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

        // Данные категории
        $categoryId = intval($_GET['c']);
        $oCategories = new Catalog_Categories();
        $categoryInf = $oCategories->getRow('*', '`id` = ' . $categoryId);
        if (!$categoryInf) {
            Pages::flash('Запрошенная категория не найдена.', true, Url::a('admin-catalog-categories'));
            exit;
        }
        if (intval($categoryInf['has_subcats'])) {
            Pages::flash(
                'Категория с подкатегориями не может содержать серии.',
                true,
                Url::a('admin-catalog-categories')
            );
            exit;
        }

        //
        $oOpts4Series = new Catalog_Categories_Opts4Series();
        $options = $oOpts4Series->get(
            '*',
            '`category_id` = ' . $categoryId,
            '`order` ASC, `id` ASC'
        );

        // Выводим страницу
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array(
            'categoryInf' => $categoryInf,
            'options' => $options
        ));
    }


    function dragSortSave($order)
    {
        $categoryId = intval($_REQUEST['c'] ?? $_GET['c'] ?? 0);
        $this->dragSortSaveScoped($order, '`category_id` = ' . $categoryId);
    }


    /** Удаление
     * @param $iId
     */
    function delItem($iId)
    {
        $oOpts4Series = new Catalog_Categories_Opts4Series();
        $oOpts4Series->del(intval($iId));

        Pages::flash(
            'Название характеристики удалено. Соответствующие характеристики серий в данной категории также удалены.'
        );
    }
}
