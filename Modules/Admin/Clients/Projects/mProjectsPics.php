<?php

/** Админка: Фотографии/Схемы проекта
 *
 * @author	Seka
 */

class mProjectsPics extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CLIENTS;

	/**
	 * @var string
	 */
	var $mainClass = 'Clients_Projects_Pics';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CLIENTS;

	/**
	 * @var int
	 */
    static $projectId;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();
		$o->getOperations();

		// Получаем инфу о серии
		self::$projectId = intval($_GET['p']);
		$oProjects = new Clients_Projects();
        $projectInf = $oProjects->getRow('*', '`id` = ' . self::$projectId);
        if(!$projectInf){
            Pages::flash('Не найден проект для просмотра списка фотографий/схем.', true, Url::a('admin-clients-projects'));
			exit;
        }

        // Получаем картинки
		$oPics = new Clients_Projects_Pics();
		$pics = $oPics->imageExtToData($oPics->get(
			'*',
			'`project_id` = ' . self::$projectId,
			'order'
		));

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
			'projectInf'	=> $projectInf,
            'pics'			=> $pics
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->setInit();
        return $frm->run('mProjectsPics::save', 'mProjectsPics::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData){

		// Проверка файла картинки
		$imgCheck = Form::checkUploadedImage(
			0,
			'image',
			3200,
			2400,
			true
		);
		if($imgCheck !== true){
			return array($imgCheck);
		}

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData){
		$oPics = new Clients_Projects_Pics();

        // Создаем в БД запись о картинке
        $id = $oPics->add(array(
			'type'		=> $newData['type'],
			'alt'		=> $newData['alt'],
			'project_id'	=> self::$projectId
		));

		// Save image
		$oPics->imageSave(
			$id,
			$_FILES['image']['tmp_name']
		);

        Pages::flash('Фотография/Схема успешно сохранена.');
    }


    /** Удаление картинки
     * @param $iId
     */
    function delItem($iId){
		$oPics = new Clients_Projects_Pics();
		$oPics->del(intval($iId));

        Pages::flash('Фотография/Схема удалена.');
    }
}

?>