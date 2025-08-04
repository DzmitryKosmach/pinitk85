<?php

/**
 * Дилеры
 *
 * @author	Seka
 */

class Dealers extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'dealers';

	/**
	 * Статусы пользователей
	 */
	const STATUS_OK = 'ok';
	const STATUS_BANNED = 'banned';

	/**
	 * Результаты проверки возможности создания аккаунта
	 */
	const CHECK_OK = 0;
	const CHECK_ERR_LOGIN = 1;
	const CHECK_ERR_EMAIL = 2;


	/** Проверка возможности создания аккаунта
	 * @param	array	$data
	 * @param	int		$userId		При изменении данных юзера здесь указывается его ID, чтобы исключить его из проверки
	 * @return	int
	 */
	function newAccCheck($data, $userId = 0){
		// Проверяем свободность логина
		if(!$this->loginAvlCheck($data['login'], $userId)){
			// Логин занят
			return self::CHECK_ERR_LOGIN;
		}

		// Проверяем свободность емэйла
		if(!$this->emailAvlCheck($data['email'], $userId)){
			// Емэйл занят
			return self::CHECK_ERR_EMAIL;
		}

		// Всё ок
		return self::CHECK_OK;
	}


	/** Проверка доступности (свободности) Логина
	 * @param	string	$login
	 * @param	int		$userId		При изменении данных юзера здесь указывается его ID, чтобы исключить его из проверки
	 * @return	bool
	 */
	function loginAvlCheck($login, $userId = 0){
		return
			$this->getCount(
				($userId ? '`id` != ' . $userId . ' AND ' : '') . '`login` = \'' . MySQL::mres($login) . '\''
			) ? false : true;
	}


	/** Проверка доступности (свободности) Email
	 * @param	string	$email
	 * @param	int		$userId		При изменении данных юзера здесь указывается его ID, чтобы исключить его из проверки
	 * @return	bool
	 */
	function emailAvlCheck($email, $userId = 0){
		return
			$this->getCount(
				($userId ? '`id` != ' . $userId . ' AND ' : '') . '(`email` = \'' . MySQL::mres($email) . '\')'
			) ? false : true;
	}


	/** Восстановление пароля (отправка письма)
	 * @param	int	$userId
	 */
	function restorePass($userId){
		$info = $this->getRow('*', '`id` = ' . intval($userId));
		if(!$info) return;

		// Отправка письма с логином/паролем
		Email_Tpl::send(
			'restorepass',
			$info['email'],
			array(
				'login'	=> $info['login'],
				'pass'	=> $info['pass']
			)
		);
	}


	/**
	 * @see	DbList::delCond()
	 * @param string $cond
	 * @return bool
	 */
	function delCond($cond = ''){
		$ids = $this->getCol('id', $cond);

		$result = parent::delCond($cond);

		if(count($ids)){
			$oOffers = new Dealers_Offers();
			$oOffers->delCond('`dealer_id` IN (' . implode(',', $ids) . ')');

			$oExtra = new Dealers_Extra();
			$oExtra->delCond('`dealer_id` IN (' . implode(',', $ids) . ')');
		}

		return $result;
	}
}

?>
