<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
require_once(dirname(__FILE__) . '/includes.php');
include(dirname(__FILE__) . '/Classes/Email/Email.class.php');

    $msg = new Email;

    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = "info@mebelioni.ru";

    if(!$phone) {
        echo json_encode( array( 'status' => 1 ) );
        return;
    }

	/* А здесь прописывается текст сообщения, \n - перенос строки */
	$mes = "Тема: Заявка!\n Телефон: $phone\n";

	/* А эта функция как раз занимается отправкой письма на указанный вами email */
	$sub = 'Заявка с сайта Мебелион'; //сабж

	$from = isset($email) && $email ? $email : 'zakaz@mebelioni.ru'; // от кого

	//$send = mail($address,$sub,$mes,"Content-type:text/plain; charset = utf-8\r\nFrom:$from");

    $status = $msg->send($address, $from, 'Мебелион' ,$sub, $mes, array());


	/*$send = mail($address, $sub, $mes,
		"From: $from
		Reply-To: $from
		Content-Type: text/plain; charset=windows-1251
		Content-Transfer-Encoding: 8bit"
	);*/

echo json_encode( array( 'status' => 1 ) );
