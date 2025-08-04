<?php

/**
 * Очередь рассылки Email
 *
 * @author	Seka
 */

class Email_Queue extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'email_queue';




	/** Отправляем заданное к-во писем из очереди
	 * @param	int	$limit
	 * @return	array
	 */
	function send($limit = 5){
		$result = array();

		$mails = $this->get('*', '', '`id` ASC', intval($limit));
		foreach($mails as $mail){
			$result[] = Email::send(
				$mail['to'],
				$mail['from'],
				$mail['from_name'],
				$mail['subj'],
				$mail['body']
			);

			$this->del($mail['id']);
		}

		return $result;
	}

}

?>
