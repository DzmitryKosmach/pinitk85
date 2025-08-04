<?php

/**
 * Обратная связь: Обычные сообщения, плюс заявки (на дизайн проект, на подбор мебели, на подписку о снижении цены)
 *
 * @author	Seka
 */

class Contacts_Feedback {

	/**
	 * Темы для разговора
	 */
	const REASON_OTHER = 'other';
	const REASON_DESIGN = 'design';			// Заявка на дизайн проект
	const REASON_SELECTION = 'selection';	// Заявка на подбор мебели
	const REASON_LOWPRICE = 'lowprice';		// Заявка на уведомление о снижении цены
	const REASON_CHEAPER = 'cheaper';		// Сообщение по акции "Нашли дешевле?"

	/**
	 * @var array
	 */
	static $reasons = array(
		self::REASON_OTHER		=> 'Общие вопросы',
		self::REASON_DESIGN		=> 'Нужен дизайн-проект',
		self::REASON_SELECTION	=> 'Нужен выбор мебели',
		self::REASON_LOWPRICE	=> 'Нужна скидка',
		self::REASON_CHEAPER	=> 'Нашли дешевле'
	);

	/**
	 * @var array
	 */
	static $popupTitles = array(
		self::REASON_OTHER		=> 'Написать нам',
		self::REASON_DESIGN		=> 'Заказать дизайн-проект',
		self::REASON_SELECTION	=> 'Заказать выбор мебели',
		self::REASON_LOWPRICE	=> 'Хочу скидку!',
		self::REASON_CHEAPER	=> 'Нашли дешевле?'
	);

	/**
	 * @var array
	 */
	static $popupTitlesIndex = array(
		self::REASON_OTHER		=> 'Написать нам',
		self::REASON_DESIGN		=> 'Заказать дизайн-проект',
		self::REASON_SELECTION	=> 'Заказать выбор мебели',
		self::REASON_LOWPRICE	=> 'Заказать скидку',
		self::REASON_CHEAPER	=> 'Нашли дешевле?'
	);


	const SESS_KEY = 'feedback-data';

	/** Отправка сообщения администратору
	 * @static
	 * @param	string	$name
	 * @param	string	$email
	 * @param	string	$phone
	 * @param	string	$text
	 * @param	string	$reason
	 * @return bool
	 */
	static function send($name, $email, $phone, $text, $reason){
		$name = trim($name);
		$email = trim($email);
		$phone = trim($phone);
		$text = trim($text);

		if(!isset(self::$reasons[$reason])){
			$reason = self::REASON_OTHER;
		}

		self::setLastData(array(
			'name'	=> $name,
			'email'	=> $email,
			'phone'	=> $phone
		));

		$oIp = new Contacts_Ip();
		if(!$oIp->checkFeedback($reason)){
			return false;
		}

		Email_Tpl::send(
			'feedback',
			Options::name('admin-feedback-email'),
			array(
				'name'		=> $name,
				'email'		=> $email,
				'phone'		=> $phone,
				'text'		=> nl2br($text),
				'reason'	=> self::$reasons[$reason],
				'fromUrl'	=> $_SERVER['HTTP_REFERER']
			)
		);

		return true;
	}


	/**
	 * @static
	 * @param	array	$data
	 */
	static function setLastData($data){
		$_SESSION[self::SESS_KEY] = $data;
	}


	/**
	 * @static
	 * @return array
	 * array('name' => ..., 'email' => ..., 'phone' => ...) или пустой массив
	 */
	static function getLastData(){
		if(isset($_SESSION[self::SESS_KEY]) && is_array($_SESSION[self::SESS_KEY])){
			return $_SESSION[self::SESS_KEY];
		}else{
			return array();
		}
	}
}

?>