<?php

/** Админка: Подсказка по способам масштабирования картинок
 * @author    Seka
 */

class mImageResizeHelp extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::SETTINGS;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array()){
        $tpl = Pages::tplFile($pageInf);
        return pattExeP(fgc($tpl), array());
    }
}

?>