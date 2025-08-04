<?
if($_POST['phones'] !== '' ){

	  $phone   = htmlentities(strip_tags($_POST['phones']));
	  $subject = htmlentities(strip_tags($_POST['subject']));
	  $names   = htmlentities(strip_tags($_POST['names']));
	  $emails = htmlentities(strip_tags($_POST['emails']));
	  $texts = htmlentities(strip_tags($_POST['texts']));
	  
	  if($phone == '+70000000000') {
	  $post_body = '';
	  } else {
		  $post_body = '<p>Телефон: '.$phone.'</p><br />';
	  }
	  if($names != '') {$post_body .= '<p>Имя: '.$names.'</p><br />';}
	  if($emails != '') {$post_body .= '<p>Email: '.$emails.'</p><br />';}
	  if($texts != '') {$post_body .= '<p>Сообщение: '.$texts.'</p><br />';}
	  
	                 mb_internal_encoding("UTF-8");
	                 date_default_timezone_set('Europe/Moscow');
					   //$to = "<zakaz@mebelioni.ru>, ";
                       //$to .= "<vas_ap@mail.ru>";
$to = "<zakaz@mebelioni.ru>";

					   $msg = '<div style="width: 90%; margin: 20px auto; border: 0px solid #006199; font-family: \'Arial\', sans-serif;">
		               <div style="background-color: #f2f2f2; 
					   text-align: center; 
					   padding: 20px;"
					   display:flex;>
			              <div style=""><img src="https://mebelioni.ru/Skins/img/user/logo.png" /></div>
						  <div>'.$subject.' - Mebelioni.ru</div>
		               </div>
		               <div style="padding: 10px;">'.$post_body.'</div>';
			
			$headers  = 'MIME-Version: 1.0' . "\r\n";
	        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	        $headers .= 'From: Mebelioni.ru <host@mebelioni.ru>' . "\r\n";
	        mail($to, $subject, $msg, $headers);
			
			if($subject == 'Форма со страницы контактов') {
            $successtext = 'Ваша заявка принята. Мы свяжемся с Вами в ближайшее время';
			}
			
			if($subject == 'Новая заявка на обратный звонок') {
            $successtext = 'Ваша заявка принята. Мы свяжемся с Вами в ближайшее время';
			}
			
			if($subject == 'Обратная связь') {
            $successtext = 'Ваша заявка принята. Мы свяжемся с Вами в ближайшее время';
			}
			
			
			if($subject == 'Заказ обратного звонка') {
            $successtext = 'Ваша заявка принята. Мы свяжемся с Вами в ближайшее время!';
			}
			
			if($subject == 'Отзывы') {
            $successtext = 'Ваша отзыв принят! Спасибо!';
			}
			
			

echo json_encode(array('result'=>'success', 'successMsg'=>$successtext));



} else { echo json_encode(array('result'=>'error','records'=>'Необходимо ввести корректный номер телефона!'));}

