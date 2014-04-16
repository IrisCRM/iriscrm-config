<?php
//********************************************************************
// Функции для отправки писем
//********************************************************************

function conv_rus_str($p_str) {
	$str_in_win1251 = GetDefaultEncoding() != 'cp1251' ?
	                  iconv(GetDefaultEncoding(), 'cp1251', $p_str) :
	                  $p_str;
	$str_in_koi8_r = convert_cyr_string($str_in_win1251, "w","k");
	return '=?koi8-r?B?'.base64_encode($str_in_koi8_r).'?=';
}

function SendEmail($p_con, $p_emailid, $p_emailaccountid, $p_smtp_params, $to, $from_mail, $from_name, $subject, $message, $attachments_arr, $p_phpmailer) {
	ini_set('display_errors', 'on');
	try {
		if ($p_phpmailer == null) {
			// создадим объект phpmailer и подключимся к серверу
			$mail = create_phpmailer($p_smtp_params);
		} else {
			$mail = $p_phpmailer;
		}
		
		// очистим поле кому и вложения (необходимо, если множественная отправка)
		$mail->ClearAddresses();
		$mail->ClearAttachments();

		//$message = pack("CCC",0xef,0xbb,0xbf).UtfEncode($message);	// miv 10.09.2010: теперь письма отсылаются в utf-8 с BOM
		$message = UtfEncode($message);	// miv 26.11.2010: убраны символы BOM
		
		$mail->SetFrom($from_mail, $from_name);

		//$mail->AddAddress($to, ''); // miv 09.09.2009: при отправке письма нескольким получателям возникает ошибка
		$to_arr = explode(',', iris_str_replace(" ", "", $to));
		foreach ($to_arr as $elem) {
			if ($elem != '')
				$mail->AddAddress($elem, '');
		}	

		$mail->Subject = conv_rus_str($subject);
		//$mail->AltBody = strip_tags(str_replace('<br>', chr(10), $message));
		$altbody = strip_tags(iris_str_replace(array('<br>', '&nbsp;'), array(chr(10), ' '), $message));
		$altbody = htmlspecialchars_decode($altbody, ENT_QUOTES); // miv 26.11.2010: переводим специальные символы

		$mail->AltBody = $altbody;

		$mail->MsgHTML($message);
		
		if (isset($attachments_arr)) {
			foreach ($attachments_arr as $attachment) {
				$buf = explode('.', $attachment['file_name']);
				$file_type = $mail->_mime_types($buf[count($buf)]);
				$mail->AddAttachment($attachment['file_path'], conv_rus_str($attachment['file_name']), 'base64', $file_type);
			}
		}
		
		fb_debug('sending email...');
		$mail->Send();
		fb_debug('sending ok');
	} catch (phpmailerException $e) {
		return $e->errorMessage(); //Pretty error messages from PHPMailer
	} catch (Exception $e) {
		return $e->getMessage(); //Boring error messages from anything else!
	}
	
	// если письмо отправлено успешно, то проставим дату отправки и вставим права доступа для пользователей на это письмо
	$CONN = $p_con; //db_connect(); // используется функция из common.php
	
	// проставим дату отправки
	$update_sql = "update iris_email set messagedate=_iris_current_datetime[] where id='".$p_emailid."'";
	$update_sql = PerformMacroSubstitution($update_sql);
	fb_debug($update_sql, 'update sended email date');
	$cmd=$CONN->prepare($update_sql);
	$cmd->execute();
	fb_debug($cmd->errorInfo(), 'update sended email date status', 'info');		

	
	// проставим права доступа для пользователей на это письмо
	AddAccessInformation($CONN, $p_emailid, '', $p_emailaccountid, 'outbox');
	
	return '';	
}


function send_email_message($p_email_id, $p_send_mode, $p_phpmailer, $p_emailaccountid) {
	$con = db_connect();
	//$p_phpmailer->ClearAddresses();return;
	// получение письма по его id
	$email_sql = "select e_from, e_to, subject, body, emailaccountid, code from iris_email T1 left join iris_emailtype T2 on T1.emailtypeid=T2.id where T1.id='".$p_email_id."'";
	$email_info = current($con->query($email_sql)->fetchAll());
	
	// проверка того, что письмо еще не отправлено
	if ($email_info['code'] != $p_send_mode) {
		return '{"status": "-", "message": "Разрешено отправлять только исходящие письма"}';
	}

	// если не указана учетная запись, то вернем ошибку
	if (strlen($email_info['emailaccountid']) < 36) {
		return '{"status": "-", "message": "Невозможно отправить письмо, так как у него не задан обратный адрес (emailaccountid is null)"}';
	}
	
	// формируем массив с вложениями с элементами вида (file_name => имя, file_path => путь)
	$files_res = $con->query("select file_filename, file_file from iris_file where emailid='".$p_email_id."' or id in (select fileid from iris_email_file where emailid='".$p_email_id."')")->fetchAll();
	$i = 0;
	$attachments_arr = null;
	foreach ($files_res as $file) {
		$attachments_arr[$i]['file_name'] = $file['file_filename'];
		$attachments_arr[$i]['file_path'] = getpath().'/files/'.$file['file_file'];
		$i++;
	}
	
	// считывание параметров SMTP сервера учетной записи
	// если указан p_phpmailer, то он уже содержит данные параметры
	$smtp_params = null;
	if ($p_emailaccountid == '') {
		$smtp_params = get_smtp_params($p_email_id);
	}
	
	// отправка письма
	$errm = SendEmail($con, $p_email_id, ( ($p_emailaccountid == '') ? $email_info['emailaccountid'] : $p_emailaccountid ), $smtp_params, $email_info['e_to'], $email_info['e_from'], $email_info['e_from'], $email_info['subject'], $email_info['body'], $attachments_arr, $p_phpmailer);
	if ($errm != '') {
		return '{"status": "-", "message": "Ошибка: '.trim(strip_tags($errm)).'"}';
	}

	// проставление статуса "Отправленое" (или "Рассылка - отправленное")
	$cmd=$con->prepare("update iris_email set emailtypeid = (select et.id from iris_emailtype et where et.code=:code) where id=:id");
	$cmd->execute(array(":id" => $p_email_id, ":code" => (($p_send_mode == 'Outbox') ? 'Sent' : 'Mailing_sent') ));
	// -----------------------------------
	
	// проставление прав на письмо и приложеные файлы исходя из аккаунта
	
	// -----------------------------------
	
	return '{"status": "+", "message": "Письмо отправлено"}';
}

function get_smtp_params($p_email_id) {
	$con = db_connect();
	
	$cmd = $con->prepare("select smtp_address as address, smtp_encryption as encryption, smtp_port as port, smtp_login as login, smtp_password as password, (case when smtp_authtype <> 'PLAIN' then true else false end) as isauthneed, smtp_authtype as authtype, id as emailaccountid from iris_emailaccount where id=(select emailaccountid from iris_email where id=:email_id)");
	$cmd->execute(array(":email_id" => $p_email_id));
	return current($cmd->fetchAll(PDO::FETCH_ASSOC));
}

function create_phpmailer($p_smtp_params) {
	include_once(realpath(GetPath().'/core/engine/classes/class.phpmailer.php'));
	$con = db_connect();
	
	// считаем значение системного параметра email_send_type
	$sysvar_data = $con->query("select code, stringvalue as val from iris_systemvariable where code = 'email_send_type'")->fetchAll(PDO::FETCH_ASSOC);
	$email_send_type = $sysvar_data ? $sysvar_data[0]['val'] : null;
	if (empty($sysvar_data[0]['code'])) {
		// если нет системного параметра email_send_type, то создадим его со значением sendmail для unix или smtp для windows
		$email_send_type = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? 'smtp' : 'sendmail';
		$sql = "insert into iris_SystemVariable (ID, createid, createdate, modifyid, modifydate, Name, Code, StringValue, IntValue, FloatValue, DateValue, GUIDValue, VariableTypeID, Description) values ('beae2468-27e5-4cab-97e2-65793a6ea286', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), E'Способ отправки email из системы', E'email_send_type', E'". $email_send_type ."', null, E'0.000000', null, null, E'ef4d2122-c9cb-469c-9d5b-dd628ad86f03', E'возможные значения: (smtp|sendmail|qmail)');";
		$cmd = $con->prepare($sql);
		$cmd->execute();
	}
	
	try {	
		$mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch

		switch ($email_send_type) {
			case 'smtp':
				$mail->IsSMTP(); // telling the class to use SMTP
			
				$mail->Host = $p_smtp_params['address'];
				if (($p_smtp_params['encryption'] != 'no') and ($p_smtp_params['encryption'] != '')) {
					$mail->SMTPSecure = $p_smtp_params['encryption'];
				}
				$mail->Port = (int)$p_smtp_params['port'];
				$mail->SMTPAuth = (bool)$p_smtp_params['isauthneed'];
				$mail->AuthType = $p_smtp_params['authtype'];
				$mail->Username = $p_smtp_params['login'];
				$mail->Password = $p_smtp_params['password'];
				
				//$mail->SMTPKeepAlive = true;
				break;

			case 'sendmail':
				$mail->IsSendmail(); // telling the class to use SendMail transport
				break;

			case 'qmail':
				$mail->IsQmail(); // telling the class to use QMail transport
				break;

			default:
				$mail->IsSMTP(); // telling the class to use SMTP
		}
		$mail->Encoding = "base64";
			
		$mail->CharSet = "UTF-8";
	} catch (phpmailerException $e) {
		return $e->errorMessage(); //Pretty error messages from PHPMailer
	} catch (Exception $e) {
		return $e->getMessage(); //Boring error messages from anything else!
	}
	
	return $mail;
}

?>