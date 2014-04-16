<?php

/*******************************************************************************
функции скопированы из /site/register.php (но изменены пути get_mail_settings и SendEmail!!)
используется в разделах сообщения, заказы, [счета]
*******************************************************************************/

function get_mail_settings($p_filemane='options.xml') {
	//$optxml = simplexml_load_file($p_filemane);

	//$optxml = simplexml_load_file(realpath('./../../../site/options.xml'));
	$optxml = simplexml_load_file(GetPath().'/site/options.xml');
		
	$email_params = array('mode' => (string)$optxml->SMTP['mode'], 'address' => (string)$optxml->SMTP['address'], 'port' => (int)$optxml->SMTP['port'], 'isAuthNeed' => (bool)$optxml->SMTP['isauthneed'], 'login' => (string)$optxml->SMTP['login'], 'password' => (string)$optxml->SMTP['password'], 'from_addr' => (string)$optxml->SMTP['from_addr'], 'from_name' => (string)$optxml->SMTP['from_name']);
	return $email_params;
}

function conv_rus_str($p_str) {
	$str = $p_str;
	if (GetDefaultEncoding() == 'utf8' || GetDefaultEncoding() == 'utf-8' || GetDefaultEncoding() == 'UTF8') {
		$str = iconv(GetDefaultEncoding(), 'cp1251', $p_str);
	}
	// TODO: это тоже лучше перевести в utf-8
	return '=?koi8-r?B?'.base64_encode(convert_cyr_string($str, "w","k")).'?=';   
}

//function SendEmail($p_smtp_params, $to, $from_mail, $from_name, $subject, $message) {
function SendEmail($p_smtp_params, $to, $subject, $message) {
	//require_once('email/class.phpmailer.php');
	//require_once(realpath('./../../../site/email/class.phpmailer.php'));
	require_once(realpath(GetPath().'/site/email/class.phpmailer.php')); //TODO: можно использовать файлы из sections/email/lib

	$mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch
	try {
		//if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		if ($p_smtp_params['mode'] == 'sockets') {
			$mail->IsSMTP(); // telling the class to use SMTP
			
			$mail->Host       = $p_smtp_params['address'];
			$mail->Port       = (int)$p_smtp_params['port'];
			$mail->SMTPAuth   = (bool)$p_smtp_params['isAuthNeed'];
			$mail->Username   = $p_smtp_params['login'];
			$mail->Password   = $p_smtp_params['password'];
		} else {
			$mail->IsSendmail(); // telling the class to use SendMail transport
		}	
		$mail->CharSet = GetDefaultEncoding();
		
		//$mail->SetFrom($from_mail, $from_name);
		//echo '[['.$p_smtp_params['from_mail'].']]';
		$mail->SetFrom($p_smtp_params['from_addr'], $p_smtp_params['from_name']);

		$mail->AddAddress($to, '');

		$mail->Subject = conv_rus_str($subject);
		$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
		$mail->MsgHTML($message);
		
		//print_r($p_smtp_params);
		
		$mail->Send();
	} catch (phpmailerException $e) {
		echo $e->errorMessage(); //Pretty error messages from PHPMailer
		return 1;
	} catch (Exception $e) {
		echo $e->getMessage(); //Boring error messages from anything else!
		return 1;
	}
	
	return 0;	
}

?>