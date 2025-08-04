<?php

/**
 * Опции системы
 *
 * @author	Seka
 */

class Options extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'options';



	/**
	 * Типы опций по способы их редактирования (строка, многострочный текст, HTML)
	 */
	const MODE_TEXT = 0;
	const MODE_BIGTEXT = 1;
	const MODE_HTML = 2;


    public function __construct()
    {
        self::setTable(self::$tab);
    }


	/** Возвращает значение опции по её имени или id
	 * @static
	 * @param	int|string	$o
	 * @return	bool|string
	 */
	static function name($o){
		/*static $oSelf;
		if($oSelf == null) */

		$oSelf = new self;

		if(is_string($o)){
			return $oSelf->getCell('value', '`name` = \'' . MySQL::mres($o) . '\'');
		}elseif(is_int($o)){
			return $oSelf->getCell('value', '`id` = \'' . MySQL::mres($o) . '\'');
		}else{
			error_log('Options::name() got wrong type argument (Integer or String expected)');
			return false;
		}
	}



	/** Устанавливает значение опции по её имени или id
	 * @static
	 * @param	int|string	$o
	 * @param	string		$val
	 * @return	bool
	 */
	static function set($o, $val){
		static $oSelf;
		if($oSelf == null) $oSelf = new self;

		if(is_string($o)){
			$oSelf->get('id', '`name` = \'' . MySQL::mres($o) . '\'');
		}elseif(is_int($o)){
			$oSelf->get('id', '`id` = \'' . MySQL::mres($o) . '\'');
		}

		if($oSelf->len){
			// Обновляем значение опции
			$oSelf->upd($oSelf->nul['id'], array('value' => $val));
		}else{
			// Создаём опцию
			$oSelf->add(array(
				'name'		=> $o,
				'value'	=> $val
			));
		}
		return true;
	}
}
