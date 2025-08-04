<?php

/**
 * Класс для управления базой словоформ
 *
 * @author	Seka
 */

class Wordforms extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'wordforms';

	/**
	 * Папка с файлами морфологии (относительно каталога с файлом с данным классом)
	 */
	const morfPath = '/morphology/';


	/** Приведение к простейшему виду ключевых слов и поисковых запросов
	 * @static
	 * @param	string	$keywords
	 * @param	bool	$uniqueWords	Удалять ли повторяющиеся слова
	 * @param	bool	$ignoreWordLen	Не дополнять короткие слова до нужной для полнотекстового поиска длины
	 * @return	string
	 */
	function formatKeywords($keywords, $uniqueWords = false, $ignoreWordLen = false){
		$keywords = trim($keywords);
		if($keywords === '') return '';

		// Определяем минимальную длину слова, воспринимаемого полнотекстовым поиском
		static $ftMinWordLen = 6;
		if(is_null($ftMinWordLen)) $ftMinWordLen = MySQL::getSystemVar('ft_min_word_len');

		$keywords = mb_strtolower($keywords);

		// Заменяем все виды пробелов на обычный пробел
		$keywords = preg_replace('/[[:space:]]/us', ' ', $keywords);

		// Удаляем из текста все символы, кроме букв и цифр
		$keywords = str_replace('ё', 'е', $keywords);
		$keywords = preg_replace('/[^a-z0-9а-я]/ius', ' ', $keywords);

		// Удаляем двойные пробелы
		$keywords = preg_replace('/ {2,}/us', ' ', $keywords);
		$keywords = trim($keywords);

		if($keywords === '') return '';

		// Разделяем строку на слова
		$words = explode(' ', $keywords);

		// Получаем базовые формы слов
		foreach($words as &$word){
			$word = trim($this->getBase($word));

			if(!$ignoreWordLen){
				$l = mb_strlen($word);
				if($l < $ftMinWordLen){
					// Дополняем короткие слова буквой Y (не так важно, что именно за буква, подошла бы любая)
					$word .= str_repeat('y', $ftMinWordLen - $l);
				}
			}
		}
		unset($word);

		if($uniqueWords){
			$words = array_unique($words);
		}

		return implode(' ', $words);
	}


	/** Возвращает исходную форму слова
	 * @param	string	$word
	 * @return	string
	 */
	function getBase($word = ''){
		$word = trim($word);
		$this->get('base', '`word` = \'' . MySQL::mres(mb_strtolower($word)) . '\'');
		return $this->len ? $this->nul['base'] : $word;
	}


	/** Данный метод создаёт базу словоформ из исходных файлов
	 * Предполагается вызвать его только 1 раз
	 * @return int	К-во получившихся словоформ
	 *
	 * $oWordforms = new Wordforms();
	 * $oWordforms->createBase();
	 */
	function createBase(){
		exit('Method Wordforms::createBase() should not be used');

		// Сканим всю папку с морфологией
		$files = glob(dirname(__FILE__) . self::morfPath . '*/*');

		// Перебираем файлы
		$morfology = array();
		foreach($files as $file){
			$words = fgc($file);	// Читаем файл
			$words = iconv('WINDOWS-1251', 'UTF-8', $words);	// Меняем кодировку
			$words = explode("\n", $words);		// Делим на строки

			// Обрезаем пробелы и исключаем пустые строки
			foreach($words as $n => &$line){
				$line = trim($line);
				if(!$line) unset($words[$n]);
			}
			unset($line);


			// Данные об окончаниях и начальной форме
			$info = array_shift($words);
			list($ends, $endBase) = explode(' ', $info);


			// Перебираем все слова из файла и генерим их всевозможные формы
			foreach($words as $word){
				if(strpos($word, '-') !== false) continue;	// Пропускаем слова с дефисами

				if($word == '#') $word = '';
				$base = self::makeMorf($word, $endBase); $base = trim($base[0]);
				$morf = self::makeMorf($word, $ends); $morf[] = $base;

				foreach($morf as $n => &$w){
					$w = trim($w);
					if(!$w) unset($morf[$n]);
				}
				unset($w);
				$morf = array_unique($morf);

				// Собираем всё в масисв вида ('слово' => 'исходная форма')
				foreach($morf as $w) if(!isset($morfology[$w])) $morfology[$w] = $base;
			}
		}


		$this->query('TRUNCATE TABLE ' . self::$tab);	// Чистим таблицу в базе

		// Записываем все слова и их исходные формы в базу
		foreach($morfology as $morf => $base){
			$this->add(array(
				'word'	=> $morf,
				'base'	=> $base
			));
		}

		return count($morfology);
	}


	/** Генерит все варианты написания слова
	 * @static
	 * @param	string	$word
	 * @param	string	$ends
	 * @return	array
	 */
	protected static function makeMorf($word, $ends){
		$m = array();
		if(preg_match('/^\{(.*)\}$/', $ends, $m)){
			// Группа окончаний в фигурных скобках
			return self::makeMorf($word, $m[1]);

		}else{

			if(strpos($ends ,'{') !== false){
				// Заменяем запятые во вложенных фиг.скобках на точки
				$ends = preg_replace(
					'/\{(.*?)\}/e',
					'
				"{" . str_replace(",", ".", "$1") . "}"
				',
					$ends
				);
			}

			$result = array();
			if(strpos($ends ,',') !== false){
				// Группа окончаний через запятую
				$ends = explode(',', $ends);
				foreach($ends as $end)
					$result = array_merge($result, self::makeMorf($word, $end));

				return $result;

			}else{
				// Одно окончание
				if(strpos($ends ,'{') !== false){
					// С подвариантами
					$word .= mb_substr($ends, 0, mb_strpos($ends ,'{'));
					$ends = mb_substr($ends, mb_strpos($ends ,'{'));
					$ends = str_replace('.', ',', $ends);
					return self::makeMorf($word, $ends);

				}else{
					// В конечной форме
					if($ends == '?') return array('');
					if($ends == '#') return array($word);
					return array($word . $ends);
				}
			}
		}
	}
}

?>
