<?php

/** Админка: Письма клиентов
 *
 * @author	Seka
 */

class mLetters extends Admin {
	/**
	 * @var int
	 */
    static $adminMenu = Admin::CLIENTS;

	/**
	 * @var string
	 */
	var $mainClass = 'Clients_Letters';

	/**
	 * @var int
	 */
	var $rights = Administrators::R_CLIENTS;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();
		$o->getOperations();

        // Получаем картинки
		$oLetters = new Clients_Letters();
		$letters = $oLetters->imageExtToData($oLetters->get(
			'*',
			'',
			'`order` DESC'
		));

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
			'letters'	=> $letters
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->setInit();
        return $frm->run('mLetters::save', 'mLetters::check');
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
		$oLetters = new Clients_Letters();

        // Создаем в БД запись о картинке
        $id = $oLetters->add(array(
			'comment'	=> $newData['comment'],
		));

		// Save image
		$oLetters->imageSave(
			$id,
			$_FILES['image']['tmp_name']
		);

        Pages::flash('Письмо клиента сохранено.');
    }


    /** Удаление картинки
     * @param $iId
     */
    function delItem($iId){
		$oLetters = new Clients_Letters();
		$oLetters->del(intval($iId));

        Pages::flash('Письмо клиента удалено.');
    }
}

?>