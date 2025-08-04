<?php

/** Админка: Добавление / Редактирование картинки в слайдер для главной страницы
 * @author	Seka
 */


class mSliderindexEdit extends Admin {
	/**
	 * @var int
	 */
	static $adminMenu = Admin::CONTENT;

	/**
	 * @var int
	 */
	var $rights = Administrators::R_PAGES;

	/**
	 * @var int
	 */
	static $pId;



	/**
	 * @static
	 * @param array $pageInf
	 * @return string
	 */
	static function main(&$pageInf = array()){
		$o = new self();
		$o->checkRights();

		$oSlider = new Slider_Index();

		if(self::$pId = intval($_GET['id'])){
			// Редактирование

			// Исходные данные
			$init = $oSlider->imageExtToData($oSlider->getRow(
				'*',
				'`id` = ' . self::$pId
			));
			if($init === false){
				Pages::flash('Запрошенная для редактирования картинка слайдера не найдена.', true, Url::a('admin-content-sliderindex'));
			}

		}else{
			// Добавление
			$init = array(
				'active'	=> 1
			);
		}

		// Собираем шаблон
		$tpl = Pages::tplFile($pageInf);
		$formHtml = pattExeP(fgc($tpl), array(
			'init'			=> $init
		));
		// Выводим форму
		$frm = new Form($formHtml);
		$frm->adminMode = true;
		$frm->setInit($init);
		return $frm->run('mSliderindexEdit::save', 'mSliderindexEdit::check');
	}






	/**
	 * @param $initData
	 * @return array
	 */
	static function check($initData){

		// Проверка файла картинки
		$imgCheck = Form::checkUploadedImage(
			self::$pId,
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
		$save = array(
			'alt'		=> $newData['alt'],
			'link'		=> $newData['link'],
			'active'	=> intval($newData['active']) ? 1 : 0
		);

		$oSlider = new Slider_Index();

		if(self::$pId){
			// Редактирование
			$oSlider->upd(self::$pId, $save);

			$msg = 'Изменения сохранены.';

		}else{
			// Добавление
			self::$pId = $oSlider->add($save);

			$msg = 'Картинка успешно добавлена.';
		}

		// Save image
		if($_FILES['image']['name']){
			$oSlider->imageSave(
				self::$pId,
				$_FILES['image']['tmp_name']
			);
		}


		Pages::flash($msg, false, Url::a('admin-content-sliderindex'));
	}
}

?>