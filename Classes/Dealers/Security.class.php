<?php

/**
 * Авторизация и проверка авторизации дилеров
 *
 * @author	Seka
 */

class Dealers_Security extends ExtDbList {
	/**
	 * @var Dealers
	 */
	protected $oDealers;

	/** Данные о текущем залогиненом юзере
	 * @var array|bool
	 */
	protected static $currentDealer = null;

	/**
	 * Ключи для $_SESSION, используемые при авторизации
	 */
	const SESSKEY_LOGIN = 'login';
	const SESSKEY_PASS = 'pass';


	/**
	 *
	 */
	function __construct(){
		$this->oDealers = new Dealers();
	}


	/** Проверяем, авторизован ли пользователь и получаем инфу о нём
	 * @static
	 * @return	array|bool	false или массив с данными юзера
	 */
	static function isAuthorized(){
		if(self::$currentDealer === null){
			if(!isset($_SESSION[self::SESSKEY_LOGIN]) || !isset($_SESSION[self::SESSKEY_PASS])){
				// Логина/пароля нет в сессии
				self::$currentDealer = false;
				return false;
			}

			// Ищем юзера
			$o = new self();
			self::$currentDealer = $o->oDealers->getRow(
				'*',
				'
					`login` = \'' . MySQL::mres($_SESSION[self::SESSKEY_LOGIN]) . '\' AND
					`pass` = \'' . MySQL::mres($_SESSION[self::SESSKEY_PASS]) . '\'
				'
			);
		}
		return self::$currentDealer;
	}


	/** Синоним метода isAuthorized()
	 * @static
	 * @see	Users_Security::isAuthorized()
	 * @return	array|bool	false или массив с данными юзера
	 */
	static function getCurrent(){
		return self::isAuthorized();
	}


	/**
	 * Результаты авторизации
	 */
	const LOGIN_OK = 1;
	const LOGIN_ERR_INCORRECT = 2;
	const LOGIN_ERR_BANNED = 3;

	/** Пробуем авторизоваться
	 * @param	string	$login
	 * @param	string	$pass
	 * @return	int
	 */
	function login($login, $pass){
		// Ищем юзера
		$info = $this->oDealers->getRow(
			'*',
			'
				`login` = \'' . MySQL::mres($login) . '\' AND
				`pass` = \'' . MySQL::mres($pass) . '\'
			'
		);
		if(!$info) return self::LOGIN_ERR_INCORRECT;	// Юзер не найден

		if($info['status'] == Dealers::STATUS_BANNED){
			// Статус - заблокирован
			return self::LOGIN_ERR_BANNED;
		}

		// Всё ок, логинмся
		self::$currentDealer = null;
		$_SESSION[self::SESSKEY_LOGIN] = $login;
		$_SESSION[self::SESSKEY_PASS] = $pass;

		return self::LOGIN_OK;
	}


	/**
	 * Выход из аккаунта
	 */
	function logout(){
		self::$currentDealer = false;
		unset($_SESSION[self::SESSKEY_LOGIN], $_SESSION[self::SESSKEY_PASS]);
	}


	/** Смена пароля в сессии, когда юзер сам меняет пароль
	 * @param	string	$newPass
	 */
	function updatePass($newPass){
		$_SESSION[self::SESSKEY_PASS] = $newPass;
	}
}

?>