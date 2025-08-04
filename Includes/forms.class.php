<?php

/*
Инпут со всем параметрами (textarea поддерживает такие же параметры):
<input
	type="text"
	name="text"
	value=""
	pattern="[a-zA-Z0-9]"
	msg_pattern="Можно вводить только английские буквы и цифры"
	maxlength="200"
	msg_maxlen="Максимальная длина текста не должна превышать 200 символов"
	required
	msg_required="Поле обязательно для заполнения"
>

Капча вставляется в форму тегом:
<recaptcha>
*/

// ЗАМЕТКИ ПО РАБОТЕ С PHP QUERY
//
// Есть два типа элементов: DOM-элементы и PQ-элементы
// PQ-элементы:
// 1. Метод phpQuery::newDocument возвращает PQ-элемент.
// 2. К PQ можно применять методы attr(), find() и др.
// 3. Метод find() возвращает 1 или несколько PQ-элементов (типа как nodeList, только pqList).
// 4. Для pqList есть два метода: eq(N) и get(N) - они возвращают N-ный элемент в виде PQ или DOM соответственно.
// 5. PQ-элементы имеют метод __toString, т.е. их можно получить в виде строки (html-кода).
// 6. pqList можно прокручивать foreach'ем, при этом в итерациях будут получаться DOM-элементы.
// 7. Для превращения PQ в DOM используется метод get: $domElement = $pqElement->get(0).
//
// DOM-элементы:
// 1. DOM не превращается в строку.
// 2. У DOM можно узнать tagName, совсем как в стандартном JS: $domElement->tagName;
// 3. Для превращения DOM в PQ используется функция $pqElement = pq($domElement);

// * Удобно хранить все элементы в виде DOM, и при необходимости превращать их в PQ "на лету" функцицей pq()

// TODO: Вместо pq($DOM)->attr() лучше (быстрее работает) использовать $DOM->getAttribute() или $DOM->setAttribute()


/** КЛАСС для работы с HTML-формами
 * @author	Seka
 */


define('RECAPTCHA_PUBLIC_KEY', '6Lc_QdgSAAAAAFg_RZKIXSDQXRNL3Rl3u1IGOHw6');
define('RECAPTCHA_PRIVATE_KEY', '6Lc_QdgSAAAAAJJw0x2lD4QRenZ4fEps8aXJxQ0m');


class Form {
	const REGEXP_COLOR = '#[0-9a-f]{6}';

	const REGEXP_EMAIL = '[0-9a-z\.\-_а-яёЁ]+@[0-9a-z\.\-а-яёЁ]+\.[a-zа-яёЁ]+';

	const REGEXP_TIME = '[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?';

	const REGEXP_DATE = '[0-9]{4}-[01][0-9]-[0123][0-9]';

	/** Режим админа позволяет вводить html-теги и не превращает их в сущности
	 * @var bool
	 */
	var $adminMode = false;

	/** Имя CSS-класса, который будет присваиваться блоку для ошибок, если этот блок создаёт автоматически данным классом
	 * @var string
	 */
	var $errBlockClassName = 'errortext';

	/** Имя CSS-класса для подсветки полей с ошибками
	 * @var string
	 */
	var $errFldsClassname = 'form-field-error';

	/** DOM-объект со всем исходным кодом (страницы или её части)
	 * @var DOMElement
	 */
	var $htmlDOM;

	/** DOM-объект с исходным кодом формы
	 * @var DOMElement
	 */
	protected $formDOM;

	/** DOM-объект - блок для ошибок
	 * @var DOMElement
	 */
	protected $errBlockDOM;

	/** Массив объектов FormElement
	 * @var array
	 */
	protected $elementsOBJ = array();

	/** Исходные значения для полей формы
	 * @var array
	 */
	protected $initValues = array();

	/** Временное хранилище для блоков JS-кода (перед анализом формы они вырезаются, а в конце вставляются на места)
	 * @var array
	 */
	protected $JSblocks = array();



	/** Создаём объект формы
	 * @param string          $html
	 * @param string|bool     $formId		id html-элемента - формы
	 * @param string|bool     $errBlockId	id html-элемента - блока для отображения сообщений об ошибках
	 * @param bool			$setAction     автоматически заменить параметр action формы на текущий url
	 */
	function __construct($html, $formId = false, $errBlockId = false, $setAction = true){
		// Подключаем phpQuery
		include_once(Config::path('external') . '/phpQuery/phpQuery.php');

		// Подключаем реКапчу
		include_once(Config::path('external') . '/reCaptcha/recaptchalib.php');

		// Парсим код: находим форму и её элементы
		$html = $this->javaScriptRemove($html);
		$this->parse($html, $formId);

		// Устанавливаем свойства самой формы:
		if($setAction) pq($this->formDOM)->attr('action', Url::buildUrl(0, $_GET)); // Action на текущую страницу
		pq($this->formDOM)->attr('method', 'post'); // Метод POST - иначе никак =))
		pq($this->formDOM)->attr('enctype', 'multipart/form-data');	// Для закачки файлов

		// Находим блок для вывода сообщений об ошибках
		if($errBlockId){
			// Блок есть
			$errBlockPQ = pq($this->htmlDOM)->find('#' . $errBlockId);
			if(!$errBlockPQ->length){
				trigger_error('Form error: error block id &quot;' . $errBlockId . '&quot; not found.');
				exit;
			}
			$this->errBlockDOM = $errBlockPQ->get(0);

		} else{
			// Блока нет, надо создать
			pq($this->formDOM)->prepend('<div class="' . $this->errBlockClassName . '"></div>');
			$this->errBlockDOM = pq($this->htmlDOM)->find('div.' . $this->errBlockClassName)->get(0);
		}
		// Скрываем блок
		$s = pq($this->errBlockDOM)->attr('style');
		$s ? $s .= ';' : $s = '';
		pq($this->errBlockDOM)->attr('style', $s . 'display:none');
	}



	/** Устанавливаем исходные значения
	 * @param array $init
	 * @return null
	 */
	function setInit($init = array()){
		if(!is_array($init)) return;
		$this->initValues = array_merge($this->initValues, $init);
	}



	/** Выполнение формы: отображение с иходными/промежуточными данными, валидация, сохранение
	 * @todo надо бы как-то сделать, что перед валидацией проверялось: были ли отправлены данные из текущей формы, или из другой
	 * @param string|bool $funcSave
	 * @param string|bool $funcCheck
	 * @return string
	 */
	function run($funcSave = false, $funcCheck = false){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			// Данные из формы отправлены

			// Стандартная валидация
			$check1 = $this->validate();

			// Дополнительная валидация внешний функцией
			$check2 = true;
			if($check1 === true && $funcCheck){
				if(strpos($funcCheck, '::') !== false){
					// Нужно вызвать статический метод класса
					list($class, $method) = explode('::', $funcCheck);

                    $runClass = new $class;
                    $check2 = $runClass::$method($this->initValues);
                    /*
					$funcCheck = create_function(
						'$initData',
						'return ' . $class . '::' . $method . '($initData);'
					);*/
				} else {
                    $check2 = $funcCheck($this->initValues);
                }
			}

			if($check1 === true && $check2 === true){
				// Валидация прошла успешно

				// Убираем &nbsp; из пустых строк
				foreach($_POST as $n => $v) if(!is_array($v) && trim(str_replace('&nbsp;', '', $v)) === '') $_POST[$n] = '';

				// Вызываем функцию сохранения результатов
				if($funcSave){
					if(strpos($funcSave, '::') !== false){
						// Нужно вызвать статический метод класса
						list($class, $method) = explode('::', $funcSave);

                        $runClass = new $class;

						/*$funcSave = create_function(
							'$initData, $newData',
							'return ' . $class . '::' . $method . '($initData, $newData);'
						);*/
                        $saveResult = $runClass::$method($this->initValues, ($this->adminMode ? $_POST : hsch($_POST)));
					}
                    else {
                        // $funcSave()может вернуть массив вида array('elementId1' => 'html1', 'elementId2' => 'html2'...)
                        // В этом случае в html-элементы в коде формы с ID = elementId1 и elementId2
                        // будет вставлен html-код html1 и html2 соответственно
                        $saveResult = $funcSave($this->initValues, ($this->adminMode ? $_POST : hsch($_POST)));
                    }

					if(is_array($saveResult) && count($saveResult))
						foreach($saveResult as $elId => $elHtml){
							$elPQ = pq($this->htmlDOM)->find('#' . $elId);
							if(!$elPQ->length) continue;
							$elPQ->eq(0)->html($elHtml);
						}
				}

			} else{
				// При валидации формы обнаружены ошибки
				$errors = $check1 !== true ? $check1 : $check2;

				// Выводим сообщения об ошибках
				$msg = array();
				foreach($errors as $e){
					if(!is_array($e) || !isset($e['msg']) || !$e['msg']) continue;
					$msg[] = $e['msg'];
				}
				pq($this->errBlockDOM)->attr('style', str_replace('display:none', '', pq($this->errBlockDOM)->attr('style')));
				pq($this->errBlockDOM)->html(implode('<br>', $msg));


				// Подсвечиваем элементы с ошибками
				foreach($errors as $e){
					if(is_array($e) && (!isset($e['name']) || !$e['name'])) continue; // Поле для подстветки не задано
					if(!is_array($e)){
						// Некорректное сообщение об ошибке формы
						trigger_error('Incorrect form error \'' . (is_array($e) ? arrToStr($e) : $e) . '\'.', E_USER_WARNING);
						continue;
					}
					if(!isset($this->elementsOBJ[$e['name']])){
						// Элемент формы для подсветки не найден
						trigger_error('Form element with name \'' . $e['name'] . '\' not found.', E_USER_WARNING);
						continue;
					}
					$this->elementsOBJ[$e['name']]->errHighlight($this->errFldsClassname);
				}
			}

			// Форма отображается с текущими значениями из $_POST
			$this->setValues($this->adminMode ? $_POST : hsch($_POST));

		} else{
			// Форма отображается с исходными значениями
			$this->setValues($this->initValues);
		}

		$this->prepareToView();

		// Возвращаем HTML-код страницы
		return $this->javaScriptRestore((string)pq($this->htmlDOM));
	}



	/** Парсим код формы, находим все элементы формы, получаем их параметры и вставляем исходные значения
	 * @param $html
	 * @param $formId
	 */
	protected function parse($html, $formId){
		$this->htmlDOM = phpQuery::newDocument($html)->get(0);

		// Находим нужную нам форму
		if($formId === false){
			$formPQ = pq($this->htmlDOM)->find('form');
		} else{
			$formPQ = pq($this->htmlDOM)->find('form#' . $formId);
		}
		if(!$formPQ->length){
			trigger_error('Form error: &lt;form&gt; element not found.');
			exit;
		}
		$this->formDOM = $formPQ->get(0);

		// Находим в форме (а также вне формы, но связанные с ней) все элементы и прокручиваем их
		if($formId === false){
			$pqFindRequest = 'form (input, select, textarea, recaptcha, ckeditor)';
		}else{
			$pqFindRequest =
				'form#' . $formId . ' (input, select, textarea), ' .
				'input[form="' . $formId . '"], ' .
				'select[form="' . $formId . '"], ' .
				'textarea[form="' . $formId . '"]' .
				'recaptcha[form="' . $formId . '"]' .
				'ckeditor[form="' . $formId . '"]';
		}

		$this->elementsOBJ = array();
		$groupedElNames    = array();
		foreach(pq($this->htmlDOM)->find($pqFindRequest) as $elDOM){
			// Если элемент disabled, пропускаем его...
			// Или нихуя ???
			// Да, disabled'ные элементы всё равно парсим, т.к. они могут переключиться при помощи JS
			// if(pq($elDOM)->attr('disabled')) continue;

			if(FormElement::isGrouped($elDOM)){
				// Групповой элемент
				$elName = pq($elDOM)->attr('name');
                if (is_null($elName)) {
                    $elName = "";
                }

                if(in_array($elName, $groupedElNames)) continue; // Групповые элементы с таким именем уже обработаны
				$groupedElNames[] = $elName;

				// Находим все элементы для этой группы
				$elType  = pq($elDOM)->attr('type');
				$groupPQ = pq($this->htmlDOM)->find('input[name="' . $elName . '"][type="' . $elType . '"]');

				// Создаём объект элемента
				$this->elementsOBJ[str_replace('[]', '', $elName)] = new FormElement($groupPQ, $this);

			} else{
				// Одиночный элемент
				$elName = pq($elDOM)->attr('name');
                if (is_null($elName)) {
                    $elName = "";
                }

				// Создаём объект элемента
                if (!$elName) { $elName = ""; }
				$this->elementsOBJ[str_replace('[]', '', $elName)] = new FormElement($elDOM, $this);
			}
		}

		//print_array($this->elementsOBJ);
	}

	/** Заполняем элементы формы значениями
	 * @param array $values
	 */
	protected function setValues($values = array()){
		// Если режим админа выключен, значит входные данные уже преобразованы в сущности,
		// и для корректной вставки в поля формы их нужно декодировать
		if(!$this->adminMode) $values = dehsch($values);
		foreach($values as $name => $value)
			if(isset($this->elementsOBJ[$name])){
				$this->elementsOBJ[$name]->setValue($value);

			}elseif(is_array($value)){
				$subValues = array();
				foreach($value as $subName => $subValue){
					$subValues[$name . '[' . $subName . ']'] = $subValue;
				}
				$this->setValues($subValues);
			}
	}

	/**
	 * Вносим изменения в html-код элементов, чтобы его можно было корректно показать на странице
	 */
	protected function prepareToView(){
		foreach($this->elementsOBJ as $elementOBJ){
			$elementOBJ->prepareToView();
		}
	}

	/** Стандартная валидация данных, введённых в форму
	 * @return	array|bool	Массив ошибок или true
	 */
	protected function validate(){
		// Готовим POST-данные
		self::convertPost($postData, $_POST);
		//print_array($postData);

		$errors = array();

		if(strpos($_SERVER['REQUEST_URI'], '/cart/step') === 0 || strpos($_SERVER['REQUEST_URI'], '/admin/') === 0) {

		} else {
			$recaptcha = false;
			if(isset($_POST['g-recaptcha-response']) && $_POST['g-recaptcha-response'] != ''){

				$Response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6Ld8RvMUAAAAAG468SAP7gun9tCTjkNVrDHyz1cq&response=".$_POST["g-recaptcha-response"]);
				$Return = json_decode($Response);
				if($Return->success) $recaptcha = true;
			}

			if(APP_ENV === "prod" && !$recaptcha){
				$errors[] = array('name'=>'', 'msg'=>'Нет подтверждения что Вы не бот');
			}
		}

		foreach($this->elementsOBJ as $name => $elementOBJ){
			if(trim($name) == '') continue;
			$res = $elementOBJ->validate($postData);
			if($res !== true) $errors[] = array(
				'name' => $name,
				'msg'  => $res
			);
		}
		return count($errors) ? $errors : true;
	}

	/** Удаляет JS из кода и запоминает, что и откуда было удалено
	 * @param	string	$html
	 * @return	string
	 */
	protected function javaScriptRemove($html){
		$this->JSblocks = array();
		$htmlOrig       = $html;

		// Ищем скрипты
		$jsNum = 0;
		$html  = strtolower($htmlOrig);
		$start = strpos($html, '<script');
		$end   = false;
		if($start !== false) $end = strpos($html, 'script>', $start + strlen('>script'));
		while($start !== false && $end !== false){
			$JS                     = substr($htmlOrig, $start, $end - $start + strlen('script>'));
			$this->JSblocks[$jsNum] = $JS;

			$htmlOrig = str_replace($JS, '[[[javascript-block-' . $jsNum . ']]]', $htmlOrig);

			$jsNum++;
			$html  = strtolower($htmlOrig);
			$start = strpos($html, '<script');
			if($start !== false) $end = strpos($html, 'script>', $start + strlen('>script'));
		}

		return $htmlOrig;
	}

	/** Вставляет JS обратно в код
	 * @param	string	$html
	 * @return	string
	 */
	protected function javaScriptRestore($html){
		foreach($this->JSblocks as $jsNum => $JS)
			$html = str_replace('[[[javascript-block-' . $jsNum . ']]]', $JS, $html);

		$this->JSblocks = array();
		return $html;
	}

	/** Готовим POST-данные, чтобы извлечь из них значения элементов с названиями вида name[1] именно по такому ключу
	 * @param array $postData
	 * @param array $iterateData
	 * @param string $topKey
	 */
	static function convertPost(&$postData = array(), $iterateData = array(), $topKey = ''){
		foreach($iterateData as $k => $v){
			$key = $topKey ? $topKey . '[' . $k . ']' : $k;
			if(!is_array($v)){
				$postData[$key] = $v;
			}else{
				self::convertPost($postData, $v, $key);
			}
		}
	}

	/** Генерируем CKEditor для формы
	 * @static
	 * @param string $ck_name
	 * @param string $ck_width
	 * @param string $ck_height
	 * @param string $ck_toolbar
	 * @return string
	 */
	static function ckEditor($ck_name = '', $ck_width = '100%', $ck_height = '300', $ck_toolbar = 'Full'){
		$ck_basepath = Config::$pathsRel['external'] . '/ckEditor/';
		include_once(_ROOT . $ck_basepath . 'ckeditor.php');
		include_once(_ROOT . $ck_basepath . 'ckfinder/ckfinder.php');

		$CKEditor                    = new CKEditor();
		$CKEditor->basePath          = $ck_basepath;
		$CKEditor->config['width']   = $ck_width;
		$CKEditor->config['height']  = $ck_height;
		$CKEditor->config['toolbar'] = $ck_toolbar;

		CKFinder::SetupCKEditor($CKEditor, $ck_basepath . 'ckfinder/');

		ob_start();
		$CKEditor->editor($ck_name);
		$ck_str = ob_get_clean();

		if(strpos($ck_str, 'CKEDITOR.replace') === false)
			$ck_str = 'Для полноценной работы редактора текста рекомендуется использовать современный браузер (Opera 10.0+, FireFox 3.0+, Chrome)<br><b>Включён режим ручного ввода HTML</b><br>'
				. $ck_str;
		else{
			static $heightFixerIncluded;
			if($heightFixerIncluded == null){
				$heightFixerIncluded = true;
				$ck_str .= '<script type="text/javascript" src="' . $ck_basepath . 'height_fix.js"></script>';
			}

			$height_fix = '<script type="text/javascript">' . fgc(_ROOT . $ck_basepath . 'height_fix0.js') . '</script>';
			$height_fix = str_replace('%name%', $ck_name, $height_fix);
			$height_fix = str_replace('%h%', $ck_height, $height_fix);
			$ck_str .= $height_fix;
		}

		return $ck_str;
	}



	/** Получаем значение даты из формы в формате timestamp
	 * @static
	 * @param	string	$dateStr
	 * @param	bool      $endOfDay
	 * @return	int
	 */
	static function getDate($dateStr, $endOfDay = false){
		if(!trim($dateStr)) return 0;
		$dateStr .= ($endOfDay ? ' 23:59:59' : ' 00:00:00');
		return strtotime($dateStr);
	}



	/** Форматируем дату из timestamp для вставки в форму
	 * @static
	 * @param	int	$dateInt
	 * @return	string
	 */
	static function setDate($dateInt){
		return $dateInt ? date('Y-m-d', $dateInt) : '';
	}



	/** Получаем значение времени из формы в формате timestamp
	 * @static
	 * @param	string	$timeStr
	 * @return	int
	 */
	static function getTime($timeStr){
		if(!trim($timeStr)) return 0;

		$p = explode(':', $timeStr);
		if(count($p) != 2) return 0;

		return abs($p[0]) * 3600 + abs($p[1]) * 60;
	}



	/** Форматируем время из timestamp для вставки в форму
	 * @static
	 * @param	int	$timeInt
	 * @return	string
	 */
	static function setTime($timeInt){
		return $timeInt ? date('H:i', $timeInt) : '00:00';
	}



	/** Значения ошибок $_FILES['field']['error'], при которых файл хотел загрузиться, но не смог
	 * @var array
	 */
	static $filesUplodErr = array(
		UPLOAD_ERR_INI_SIZE  => 'Размер принятого файла превысил максимально допустимый размер',
		UPLOAD_ERR_FORM_SIZE => 'Размер принятого файла превысил максимально допустимый размер',
		UPLOAD_ERR_PARTIAL   => 'Загружаемый файл был получен только частично',
	);



	/** Проверка загруженного файла $_FILES['field'] на ошибки
	 * @static
	 * @param	array	$file
	 * @return	bool
	 */
	static function filesUplodErr($file){
		return isset(self::$filesUplodErr[$file['error']]) ? self::$filesUplodErr[$file['error']] : false;
	}











	/** Типовая проверка корректности загруженного изображения для записи БД в админке
	 * @static
	 * @param	int		$recId			ID записи в БД, или 0, если запись только создаётся
	 * @param	string	$fileFldName	Имя поля <input type="file"> в форме
	 * @param	int		$imgMaxW		Макс. допустимая ширина картинки
	 * @param	int		$imgMaxH		Макс. высота
	 * @param	bool	$imgRequired	Картинка обязательна для загрузки (если запись только создаётся - иначе необязательно)
	 * @return	array|bool		TRUE или массив array('name' => $fileFldName, 'msg' => 'Текст сообщения об ошибке')
	 */
	static function checkUploadedImage($recId, $fileFldName, $imgMaxW = 3200, $imgMaxH = 2400, $imgRequired = false){
		if(Form::filesUplodErr($_FILES[$fileFldName])){
			// Ошибка при загрузке файла
			return array(
				'name'	=> $fileFldName,
				'msg'	=> 'Ошибка при загрузке изображения.'
			);
		}

		if($imgRequired && !$recId && !$_FILES[$fileFldName]['name']){
			// Загрузка файла обязательна, но он не загружен
			return array(
				'name'	=> $fileFldName,
				'msg'	=> 'Файл изображения не задан.'
			);
		}

		if($_FILES[$fileFldName]['name']){
			// Проверяем тип файла и размер картинки
			$check = Images::checkImage($_FILES[$fileFldName]['tmp_name'], $imgMaxW, $imgMaxH);

			if($check == IMG_CHECK_ERR_TYPE){
				return array(
					'name'	=> $fileFldName,
					'msg'	=> 'Загрузите изображение правильного формата. Нужен файл JPG, GIF, или PNG.'
				);
			}
			if($check == IMG_CHECK_ERR_SIZE){
				return array(
					'name'	=> $fileFldName,
					'msg'	=> 'Слишком большое изображение. Максимум ' . $imgMaxW . 'x' . $imgMaxH . ' точек.'
				);
			}
		}

		return true;
	}








	/** Выводит список найденных элементов формы (их названия и типы)
	 * @param bool $showTypes
	 */
	function debugListElements($showTypes = false){
		foreach($this->elementsOBJ as $elName => $elObj){
			if($showTypes){
				print $elName . ': ' . $elObj->tag . (trim($elObj->type) ? '[' . $elObj->type . ']' : '') . '<br>';
			}else{
				print $elName . '<br>';
			}
		}
	}
}





/** КЛАСС для представления одного элемента формы
 * Анализирует стандартные html-свойства тегов формы.
 * Плюс, для указания текстов сообщений при ошибках валидации используется три нестандартных параметра:
 * msg_pattern="", msg_maxlen="", msg_required=""
 * После обработки эти параметры удаляются из исходного html-кода формы
 * @author	Seka
 */
class FormElement {

	const ATTR_PATTERN = 'pattern';

	const ATTR_REQUIRED = 'required';

	const ATTR_MAXLEN = 'maxlen';

	static $specialAttrs = array(
		self::ATTR_PATTERN,
		self::ATTR_REQUIRED,
		self::ATTR_MAXLEN
	);

	/** Сообщения по-умолчанию об ошибках валидации
	 * @var array
	 */
	static $defaulInvalidMsgs = array(
		self::ATTR_PATTERN  => 'Используйте требуемый формат',
		self::ATTR_REQUIRED => 'Поле обязательно для заполнения',
		self::ATTR_MAXLEN   => 'Превышено допустимое количество символов (%len%)',

		'color'             => 'Цвет указан в неправильном формате',
		'recaptcha'         => 'Неверно введена надпись с картинки'
	);


	/** Сообщение об ошибке при последней валидации реКапчи
	 * @var null
	 */
	static $recaptchaLastError = null;


	// Основные свойства элемента

	/** Объект формы
	 * @var Form
	 */
	var $formEl;

	/** Объект с элементом формы: DOMElement - для одиночных элементов, phpQueryObject - для групповых
	 * @var DOMElement|phpQueryObject
	 */
	var $element;

	/** Элемент является групповым
	 * @var bool
	 */
	var $grouped = false;

	/** Тег html-элемента
	 * @var string
	 */
	var $tag = '';

	/** Имя html-элемента
	 * @var string
	 */
	var $name = '';

	/** Параметр type html-элемента
	 * @var string
	 */
	var $type = '';

	/** Сообщения об ошибках валидации (беруться из html-атрибутов, либо по умолчанию)
	 * @see FormElement::$defaulInvalidMsgs
	 * @var array
	 */
	var $errMsg = array(
		self::ATTR_PATTERN  => '',
		self::ATTR_REQUIRED => '',
		self::ATTR_MAXLEN   => ''
	);


	// Параметры, на основе которых происходит валидация ввода (только для текстовых полей и select'ов):

	/** Шаблон PCRE
	 * @var string|bool
	 */
	protected $pattern = false;

	/** Обязательно для ввода
	 * @var bool
	 */
	protected $required = false;

	/** Ограничение макс длинны
	 * @var int|bool
	 */
	protected $maxlen = false;

	/** Возможен ли выбор нескольких значений (для множественных элементов и списков)
	 * @var bool
	 */
	protected $multiple = false;

	/** Предопределённые рег.выражения для некоторых типов негрупповых <input>'ов
	 * @todo Заполнить этот массив для типов color, email и т.п.
	 * @var array
	 */
	protected $inputDefaultRegexp = array(
		'color' => Form::REGEXP_COLOR,
		'email' => Form::REGEXP_EMAIL,
		'time'  => Form::REGEXP_TIME,
		'date'  => Form::REGEXP_DATE
	);



	/** Для создания объекта одиночного элемента необходимо передать объект класса DOMElement
	 * Для создания объекта группового элемента - phpQueryObject
	 * @param	DOMElement|phpQueryObject	$element
	 * @param	Form						$formEl
	 */
	function __construct($element, &$formEl){
		$this->formEl = $formEl;
		$this->element = $element;

		if(get_class($this->element) == 'DOMElement'){
			// Одиночный элемент
			$this->makeSingle();

		}elseif(get_class($this->element) == 'phpQueryObject'){
			// Групповой элемент
			$this->makeGrouped();

		}else{
			trigger_error('FormElement error: argument for __construct() method should be an object of class "DOMElement" or "phpQueryObject".');
			exit;
		}
	}



	/**
	 * Создаём объект одиночного элемента
	 */
	protected function makeSingle(){
		$this->grouped = false;
		$this->tag     = $this->element->tagName;
		$this->name    = pq($this->element)->attr('name');

		if($this->tag == 'input'){
			// Тег <input>
			$this->type = pq($this->element)->attr('type'); // Тип input'а
			if(!$this->type) $this->type = 'text';

			$this->maxlen   = intval(pq($this->element)->attr('maxlength')); // Макс длина
			$this->required = !is_null(pq($this->element)->attr(self::ATTR_REQUIRED)); // Обязательный/необязаельный

			// Ищем предопределённый regexp
			if(isset($this->inputDefaultRegexp[$this->type])){
				// Для данного type есть стандартный regexp
				$this->pattern = '/(^$)|(^' . $this->inputDefaultRegexp[$this->type] . '$)/isu';
			} else{
				// Получаем regexp из кода элемента
				$this->pattern = pq($this->element)->attr(self::ATTR_PATTERN);
				if($this->pattern){
					$this->pattern = '/(^$)|(^' . $this->pattern . '$)/isu'; // TODO: надо этот момент прописать как-то гибче
				}
			}

		}elseif($this->tag == 'textarea'){
			// Тег <textarea>
			$this->maxlen   = intval(pq($this->element)->attr('maxlength')); // Макс длина
			$this->required = !is_null(pq($this->element)->attr(self::ATTR_REQUIRED)); // Обязательный/необязаельный

			// Получаем regexp из кода элемента
			$this->pattern = pq($this->element)->attr(self::ATTR_PATTERN);
			if($this->pattern){
				$this->pattern = '/(^$)|(^' . $this->pattern . '$)/isu'; // TODO: надо этот момент прописать как-то гибче
			}

		}elseif($this->tag == 'ckeditor'){
			// Тег <ckeditor>
			$this->maxlen   = intval(pq($this->element)->attr('maxlength')); // Макс длина
			$this->required = !is_null(pq($this->element)->attr(self::ATTR_REQUIRED)); // Обязательный/необязаельный

			// Получаем regexp из кода элемента
			$this->pattern = pq($this->element)->attr(self::ATTR_PATTERN);
			if($this->pattern){
				$this->pattern = '/(^$)|(^' . $this->pattern . '$)/isu'; // TODO: надо этот момент прописать как-то гибче
			}

			// Превращаем тег <ckeditor> в <textarea>
			$this->makeCKEditor();

		}elseif($this->tag == 'select'){
			// Тег <select>
			$this->required = !is_null(pq($this->element)->attr(self::ATTR_REQUIRED)); // Обязательный/необязаельный

			// Получаем regexp из кода элемента
			$this->pattern = pq($this->element)->attr(self::ATTR_PATTERN);
			if($this->pattern){
				$this->pattern = '/(^$)|(^' . $this->pattern . '$)/isu'; // TODO: надо этот момент прописать как-то гибче
			}

		}elseif($this->tag == 'recaptcha'){
			// Для реКапчи не нужны никакие доп. праметры, она всегда работает однозначно

		}else{
			trigger_error('FormElement error: method makeSingle() called with incorrect element &lt;' . $this->tag . '&gt;');
			exit;
		}


		// Определяем multiple
		$this->isMultiple();

		// Находим тексты для сообщений об ошибках валидации (в параметрах msg_pattern,  msg_required и  msg_maxlen)
		foreach(self::$specialAttrs as $a){
			if(pq($this->element)->attr('msg_' . $a)){
				// Текст ошибки есть в коде элемента
				$this->errMsg[$a] = pq($this->element)->attr('msg_' . $a);

			}elseif(isset(self::$defaulInvalidMsgs[$this->type])){
				// Для данного типа элементов предусмотрен стандартный текст ошибки
				$this->errMsg[$a] = self::$defaulInvalidMsgs[$this->type];

			}else{
				// Берём общий текст ошибки
				$this->errMsg[$a] = self::$defaulInvalidMsgs[$a];
			}
		}
		$this->errMsg[self::ATTR_MAXLEN] = str_replace('%len%', intval($this->maxlen), $this->errMsg[self::ATTR_MAXLEN]);
	}



	/**
	 * Создаём объект группового элемента
	 */
	protected function makeGrouped(){
		$this->grouped = true;

		// Из первого элемента группы вытигиваем общие данные
		$firstDOM   = $this->element->get(0);
		$this->tag  = $firstDOM->tagName;
		$this->name = pq($firstDOM)->attr('name');
		$this->type = pq($firstDOM)->attr('type');

		// Определяем multiple
		$this->isMultiple();
	}



	/**
	 * Определяем, есть ли в имени элемента [] - в этом случае его значение задаётся и возвращается в виде массива
	 */
	protected function isMultiple(){
		if($this->name && strpos($this->name, '[]')){
			$this->name     = str_replace('[]', '', $this->name);
			$this->multiple = true;
		} else {
            $this->multiple = false;
        }
	}



	/** Устанавливаем значение для элемента
	 * @param	array|string	$value
	 */
	function setValue($value){
		if($this->grouped){
			// Групповой элемент
			if(!is_array($value)) $value = array($value); // Значение должно быть массивом

			// Если тип элемента "radio", то массив значений должен содержать только 1 элемент
			if($this->type == 'radio' && count($value) > 1) $value = array(array_shift($value));
			foreach($this->element as $elDOM){
				$elVal = pq($elDOM)->attr('value');
				in_array($elVal, $value) ? pq($elDOM)->attr('checked', true) : pq($elDOM)->removeAttr('checked');
			}

		} else{
			// Одиночный элемент
			if($this->tag == 'input'){
				// Обычный элемент с единственным значением
				$t = gettype($value);
				if(!in_array($t, array('boolean', 'integer', 'double', 'string'))){
					trigger_error('Value inserted in the INPUT "' . $this->name . '" must be an instance of boolean, integer, double or string, ' . $t . ' given.', E_USER_WARNING);
					exit;
				}

				pq($this->element)->attr('value', $value);

			} elseif($this->tag == 'textarea' || $this->tag == 'ckeditor'){
				// <textarea>
				$t = gettype($value);
				if(!in_array($t, array('boolean', 'integer', 'double', 'string'))){
					trigger_error('Value inserted in the TEXTAREA "' . $this->name . '" must be an instance of boolean, integer, double or string, ' . $t . ' given.', E_USER_WARNING);
					exit;
				}
				pq($this->element)->html(hsch($value));

			} elseif($this->tag == 'select'){
				if(!is_array($value)) $value = array($value); // Значение должно быть массивом

				// Если множественный выбор запрещён, то массив значений должен содержать только 1 элемент
				if(!$this->multiple && count($value) > 1) $value = array(array_shift($value));

				foreach(pq($this->element)->find('option') as $optDOM){
					$optVal = pq($optDOM)->attr('value');
					in_array($optVal, $value) ? pq($optDOM)->attr('selected', true) : pq($optDOM)->removeAttr('selected');
				}
			}
		}
	}






	/**
	 * Превращаем тег <ckeditor> в <textarea>
	 */
	function makeCKEditor(){
		if($this->tag == 'ckeditor'){
			// Нужно заменить элемент <ckeditor> на код CKEditor'а
			$name = pq($this->element)->attr('name');
			$width = trim(pq($this->element)->attr('width')); if(!$width) $width = '100%';
			$height = trim(pq($this->element)->attr('height')); if(!$height) $height = '300';
			$toolbar = trim(pq($this->element)->attr('toolbar')); if(!$toolbar) $toolbar = 'Full';

			pq($this->element)->replaceWith(
				Form::ckEditor($name, $width, $height, $toolbar)
			);

			// Заново получаем DOMElement
			$this->element = pq($this->formEl->htmlDOM)->find('[name="' . $name . '"]')->get(0);
		}
	}





	/**
	 * Вносим изменения в html-код элемента, чтобы его можно было корректно показать на странице
	 */
	function prepareToView(){
		// Убираем нестандартные аттрибуты тегов
		foreach(self::$specialAttrs as $a){
			pq($this->element)->removeAttr('msg_' . $a);
		}

		if($this->tag == 'recaptcha'){
			// Нужно заменить элемент <recaptcha> на код реКапчи
			pq($this->element)->replaceWith(
				recaptcha_get_html(
					RECAPTCHA_PUBLIC_KEY,
					self::$recaptchaLastError
				)
			);
		}
	}








	/** Валидация введённых в элемент данных
	 * @param	array	$postData
	 * @return bool
	 */
	function validate($postData){
		// Групповые элементы не проверяются и считаются валидными всегда
		if($this->grouped) return true;

		// Получаем введённое значение
		$value = $postData[$this->name];

		if($this->multiple){
			// Если значение - массив, то мы можем проверить только его длину

			if($this->required && !count($value)){
				// Поле обязательно для заполнения
				return $this->errMsg[self::ATTR_REQUIRED];
			}

		} else{
			// Если значение - строка, проверяем её по-полной

			if($this->type == 'file'){
				// TODO: Сделать полноценную валидацию загруженных файлов

				//* Хак для получения файлов, предварительно загруженных через JS-скрипт UploadImages */
				if(isset($postData[$this->name . '-preloaded']) && trim($postData[$this->name . '-preloaded'])){
					$tmp = $postData[$this->name . '-preloaded'];

					if(is_file($tmp)){
						$_FILES[$this->name]['tmp_name'] = $tmp;
						$_FILES[$this->name]['name']     = $fname = end(explode('/', $tmp));
						$_FILES[$this->name]['size']     = $fsize = filesize($tmp);

						$imgInfo                      = getimagesize($tmp);
						$_FILES[$this->name]['type']  = $imgInfo['mime'];
						$_FILES[$this->name]['error'] = UPLOAD_ERR_OK;

					} elseif($tmp == 'delete'){
						$_FILES[$this->name]['delete'] = true;
					}
				}
				unset($postData[$this->name . '-preloaded']);
				/* Конец хака */

				return true;
			}

			$value = !is_null($value) ? trim($value) : '';

			if($this->required && $value === ''){
				// Поле обязательно для заполнения, но не заполнено
				return $this->errMsg[self::ATTR_REQUIRED];
			}

			if($this->pattern && !preg_match($this->pattern, $value)){
				// Поле не соответствует шаблону
				return $this->errMsg[self::ATTR_PATTERN];
			}

			if($this->maxlen && mb_strlen($value) > $this->maxlen){
				// Поле превышает макс. длину
				return $this->errMsg[self::ATTR_MAXLEN];
			}

			if($this->tag == 'recaptcha'){
				$reCaptchaResult = recaptcha_check_answer(
					RECAPTCHA_PRIVATE_KEY,
					$_SERVER['REMOTE_ADDR'],
					$postData['recaptcha_challenge_field'],
					$postData['recaptcha_response_field']
				);

				if(!$reCaptchaResult->is_valid){
					self::$recaptchaLastError = $reCaptchaResult->error;
					return self::$defaulInvalidMsgs[$this->tag];
				}
			}
		}

		return true;
	}



	/** Подсвечиваем элемент с ошибкой
	 * @param	string	$className
	 */
	function errHighlight($className){
		if($this->grouped){
			// Групповой элемент
			foreach($this->element as $elDOM) pq($elDOM)->addClass($className);
		} else{
			// Одиночный элемент
			pq($this->element)->addClass($className);
		}
	}



	/** Проверка, является ли элемент групповым (т.е. type== 'checkbox' или 'radio')
	 * @static
	 * @param	DOMElement	$elDOM
	 * @return	 bool
	 */
	static function isGrouped($elDOM){
		return $elDOM->tagName == 'input' && in_array(pq($elDOM)->attr('type'), array('checkbox', 'radio'));
	}
}
