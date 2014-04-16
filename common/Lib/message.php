<?php
/**********************************************************************
функции для работы с сообщениями
используется в разделе "Мои сообщения" и site/question.php
**********************************************************************/

// послать уведомление получателю сообщения
function SendEmailToUser($p_rec_id) {
ini_set('display_errors', 'on');

	$con = db_connect();
	$message_res = $con->query(
	"select T0.recipientid as recipientid, T0.message as message, T1.name as autorname, T0.subject as subject , T2.number as projectnumber, T2.name as projectname 
	from iris_message T0 
	left join iris_contact T1 on T0.autorid = T1.id 
	left join iris_project T2 on T0.projectid = T2.id where T0.id='".$p_rec_id."'")->fetchAll(PDO::FETCH_ASSOC);

	$sql = "select T0.id as id, T0.name as name, T0.email as email, T0.isnotify as isnotify, T0.password as password, T1.Code as ct_code from iris_contact T0 left join iris_contacttype T1 on T0.contacttypeid = T1.id	where T0.id='".$message_res[0]['recipientid']."'";
	$recipient = current($con->query($sql)->fetchAll(PDO::FETCH_ASSOC));
	if ($recipient['password'] == '') {
		//$message = file_get_contents('notify-notreg.html'); // шаблон письма для незарегистрированного клиента
		$message = file_get_contents(GetPath().'/config/sections/Message/notify-notreg.html'); // шаблон письма для незарегистрированного клиента
	} else {
		if ($recipient['ct_code'] == 'Client') {
			//$message = file_get_contents('notify-reg.html'); // шаблон письма для зарегистрированного клиента 
			$message = file_get_contents(GetPath().'/config/sections/Message/notify-reg.html');
		} else {
			//$message = file_get_contents('notify-manager.html'); // шаблон письма для сотрудника
			$message = file_get_contents(GetPath().'/config/sections/Message/notify-manager.html');
		}
	}
	$message = iris_str_replace('#charset#', GetDefaultEncoding(), $message);
	
	//if ($recipient['isnotify'] != 1)
	if (($recipient['password'] != '') and ($recipient['isnotify'] != 1))
		return json_encode(array('result' => 'notify disabled')); // если уведомления отключены, то выйдем

//return json_encode(array('result' => 'under construction'));
	
	//require 'mailfunc.php';
	if (function_exists('get_mail_settings') == false) {
		require_once GetPath().'/config/common/Lib/mailfunc.php';
	}

	$email_params = get_mail_settings(GetPath().'/site/options.xml');
	
	$to = $recipient['email'];
	if ($to == '')
		return json_encode(array('result' => 'recipient is not have a email')); // если у получателя не указан email
	
	// имя компании
	$companyname_res = $con->query("select T0.name, T0.phone1, t0.web from iris_account T0 left join iris_accounttype T1 on T0.accounttypeid=T1.id where T1.code='Your'")->fetchAll();
	$subject = 'Новое сообщение: '.$message_res[0]['subject'];
	///$message = file_get_contents('notify-notreg.html'); // шаблон письма незарегистрированного клиента
	
	$message = iris_str_replace('[fio_autor]', $message_res[0]['autorname'], $message);
	$message = iris_str_replace('[message]', $message_res[0]['message'], $message);
	$message = iris_str_replace('[yourcompanyname]', $companyname_res[0][0], $message);
	$message = iris_str_replace('[yc_phone]', $companyname_res[0][1], $message);
	$message = iris_str_replace('[yc_web]', '<a href="http://'.$companyname_res[0][2].'">'.$companyname_res[0][2].'</a>', $message);

	$message = iris_str_replace('[fio]', $recipient['name'], $message);

	// если уведомление отсылается зарегестрированному клиенту или сотруднику, то в шаблоне будут еще 2 поля, связанные с проектом
	$message = iris_str_replace('[project_num]', $message_res[0]['projectnumber'], $message);
	$message = iris_str_replace('[project_name]', $message_res[0]['projectname'], $message);

	$admin_res = $con->query("select email from iris_contact T0 left join iris_accessrole T1 on T0.accessroleid=T1.id where T1.code in ('admin') order by email")->fetchAll();
	if ($admin_res[0][0] != '')
		$admin_res[0][0] = ' ('.$admin_res[0][0].')';
	$message = iris_str_replace('[admin_email]', $admin_res[0][0], $message);
	
	
	$optxml = simplexml_load_file(GetPath().'/site/options.xml');
	$link = (string)$optxml->INDEX_LINK.'/enter.php';
	$message = iris_str_replace('[login_form]', '<a href="'.$link.'">'.$link.'</a>', $message);	

	$message = iris_str_replace('[question_form]', '<a href="'.(string)$optxml->INDEX_LINK.'/site/question.php">"Задать вопрос"</a>', $message);	
	$message = iris_str_replace('[cabinet]', '<a href="'.(string)$optxml->INDEX_LINK.'/enter.php">личный кабинет</a>', $message);	
	
	
	$res = SendEmail($email_params, $to, $subject, $message);
	if ($res != 0)
		return json_encode(array('result' => 'error'));
	
	return json_encode(array('result' => 'ok'));
}
?>
