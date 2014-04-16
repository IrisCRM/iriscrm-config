<?php
//********************************************************************
// Раздел Рассылка, закладка получатели (таблица)
//********************************************************************

function get_sec() {
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    return $mtime;
}

function AddContact($p_contact_id, $p_mailing_id) {
	$con = db_connect();

	// проверим, может ли данный пользователь править рассылку. если нет, то не дадим добавить контакт
	GetCurrentUserRecordPermissions('iris_mailing', $p_mailing_id, $permissions, $con);
	if ($permissions['w'] == 0)
		return array('errno' => 3, 'errm' => json_convert('Для добавления контакта пользователь должен иметь права записи на рассылку'));

	// проверим, не добавлен ли уже контакт
	$cmd = $con->prepare("select id from iris_mailing_contact where contactid=:contactid and mailingid = :mailingid");
	$cmd->execute(array(":contactid" => $p_contact_id, ":mailingid" => $p_mailing_id));
	$file_exists = current($cmd->fetchAll(PDO::FETCH_ASSOC));
	if ($file_exists['id'] != '')
		return array('errno' => 1, 'errm' => json_convert('Этот файл уже прикреплен'));
		
	// добавим контакт
	$ins_cmd = $con->prepare("insert into iris_mailing_contact (id, contactid, mailingid) values (iris_genguid(), :contactid, :mailingid)");
	$ins_cmd->execute(array(":contactid" => $p_contact_id, ":mailingid" => $p_mailing_id));
	if ($ins_cmd->errorCode() != '00000')
		return array('errno' => 2, 'errm' => json_convert('Не удалось добавить контакт'));

	return array('errno' => 0, 'errm' => 'ok');
}

function RemoveContact($p_contact_id, $p_mailing_id) {
	$con = db_connect();

	// проверим, может ли данный пользователь править рассылку. если нет, то не дадим удалить файл
	GetCurrentUserRecordPermissions('iris_mailing', $p_mailing_id, $permissions, $con);
	if ($permissions['w'] == 0)
		return array('errno' => 3, 'errm' => json_convert('Для исключения контакта из рассылки пользователь должен иметь права записи на рассылку'));
	
	// если у этого пользователя есть отправленное письмо расылки, то не дадим удалить
	$sql  = "select T0.emailid as id, T2.code as code from iris_mailing_contact T0 ";
	$sql .= "left join iris_email T1 on T0.emailid = T1.id ";
	$sql .= "left join iris_emailtype T2 on T1.emailtypeid = T2.id ";
	$sql .= "where T0.contactid=:contactid and T0.mailingid =:mailingid";
	$cmd = $con->prepare($sql);
	$cmd->execute(array(":contactid" => $p_contact_id, ":mailingid" => $p_mailing_id));
	$email = current($cmd->fetchAll(PDO::FETCH_ASSOC));
	if ($email['code'] == 'Mailing_sent')
		return array('errno' => 4, 'errm' => json_convert('Невозможно удалить получателя, так как у него есть отправленное письмо'));
	
	// исключим пользователя из рассылки
	$cmd = $con->prepare("delete from iris_mailing_contact where contactid = :contactid and mailingid = :mailingid");
	$cmd->execute(array(":contactid" => $p_contact_id, ":mailingid" => $p_mailing_id));
	if ($cmd->errorCode() != '00000')
		return array('errno' => 2, 'errm' => json_convert('Не удалось исключить контакт'));

	// удалим письмо рассылки
	$cmd = $con->prepare("delete from iris_email where id=:id");
	if ($cmd->execute(array(":id" => $email['id'])) == 0)
		return array('errno' => 2, 'errm' => json_convert('Не удалось удалить письмо рассылки'));
	
	return array('errno' => 0, 'errm' => 'ok');	
}

function CreateEmailFields($p_contactid, $p_mailingid, $p_con) {
	$cmd = $p_con->prepare("select subject, text from iris_mailing where id=:id");
	$cmd->execute(array(":id" => $p_mailingid));
	$mailing = current($cmd->fetchAll(PDO::FETCH_ASSOC));

	$subject = FillFormFromText($mailing['subject'], 'Contact', $p_contactid);
	$body = FillFormFromText($mailing['text'], 'Contact', $p_contactid);
	
	return array ("subject" => $subject, "body" => $body);
}

function PrewiewEmail($p_contactid, $p_mailingid) {
	header("Content-Type: text/html; charset=".GetDefaultEncoding());	

	$con = db_connect();

	// проверим, может ли данный пользователь читать рассылку. если нет, то не дадим делать предпросмотр
	GetCurrentUserRecordPermissions('iris_mailing', $p_mailingid, $permissions, $con);
	if ($permissions['r'] == 0)
		return 'У пользователя нет прав для просмотра данной рассылки';

	$res = CreateEmailFields($p_contactid, $p_mailingid, $con);
	return 'Тема: '.$res['subject'].'<hr>'.$res['body'];
}

function CreateEmails($p_mailingid) {
	$con = db_connect();
	$start_time = get_sec();
	
	//"delete from iris_email where id in (select emailid from iris_mailing_contact where mailingid=:mailingid) and emailtypeid = (select id from iris_emailtype where code = 'Mailing_outbox')"

	// считаем из рассылки ответственного, почтовый ящик (2 поля)
	$mailing_cmd = $con->prepare("select T0.ownerid as ownerid, T0.emailaccountid as emailaccountid, T1.email as email from iris_mailing T0 left join iris_emailaccount T1 on T0.emailaccountid = T1.id where T0.id=:id");
	$mailing_cmd->execute(array(":id" => $p_mailingid));
	$mailind = current($mailing_cmd->fetchAll(PDO::FETCH_ASSOC));

	// тип письма - Рассылка исходящее
	$emailtype = current($con->query("select id from iris_emailtype where code = 'Mailing_outbox'")->fetchAll(PDO::FETCH_ASSOC));

	// сформируем права доступа на добавляемые письма (права как у рассылки, только убираем запись)
	GetRecordPermissions('iris_mailing', $p_mailingid, $permissions, $con);
	foreach ($permissions as $key => $val)
		$permissions[$key]['w'] = 0; 
	
	//$sql = "select T0.contactid as id, T0.emailid as emailid, T3.email as email, T3.accountid, T3.ownerid as ownerid from iris_mailing_contact T0 left join iris_email T1 on T0.emailid = T1.id left join iris_emailtype T2 on T1.emailtypeid = T2.id left join iris_contact T3 on T0.contactid = T3.id where T0.mailingid=:mailingid and (T2.code is null or T2.code = 'Mailing_outbox')";
	$sql = "select T0.contactid as id, T3.email as email, T3.accountid, T3.ownerid as ownerid from iris_mailing_contact T0 left join iris_contact T3 on T0.contactid = T3.id where T0.mailingid=:mailingid and T0.emailid is null order by T3.name";
	$cmd = $con->prepare($sql);
	$cmd->execute(array(":mailingid" => $p_mailingid));
	$contacts = $cmd->fetchAll(PDO::FETCH_ASSOC);
	$leftcount = count($contacts);
	$createcount = 0;

	$sql = "insert into iris_email (id, e_from, emailaccountid, e_to, contactid, accountid, ownerid, emailtypeid, subject, body) values (:id, :e_from, :emailaccountid, :e_to, :contactid, :accountid, :ownerid, :emailtypeid, :subject, :body)";
	$ins_cmd = $con->prepare($sql);
	foreach ($contacts as $contact) {
		$email_fields = CreateEmailFields($contact['id'], $p_mailingid, $con);
		
		$new_id = create_guid();
		$ins_cmd->bindParam(":id", $new_id);
		$ins_cmd->bindParam(":e_from", $mailind['email']);
		$ins_cmd->bindParam(":emailaccountid", $mailind['emailaccountid']);
		$ins_cmd->bindParam(":e_to", $contact['email']);
		$ins_cmd->bindParam(":contactid", $contact['id']);
		$ins_cmd->bindParam(":accountid", $contact['accountid']);
		$ins_cmd->bindParam(":ownerid", $mailind['ownerid']);
		$ins_cmd->bindParam(":emailtypeid", $emailtype['id']);
		$ins_cmd->bindParam(":subject", $email_fields['subject']);
		$ins_cmd->bindParam(":body", $email_fields['body']);
		if ($ins_cmd->execute() == 0) {
			return array('success' => 0, 'message' => json_convert('ошибка при добавлении письма'));
		}
		
		// вставка прав доступа на письмо
		ChangeRecordPermissions('iris_email', $new_id, $permissions, $con);
		
		// дадим доступ на чтение ответственному за контакта (если у него не было доступа на чтение рассылки)
		GetUserRecordPermissions('iris_mailing', $p_mailingid, $contact['ownerid'], $contact_perm, $con);
		if ($contact_perm['r'] == 0) {
			$add_perm[] = array('userid' => $contact['ownerid'], 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
			ChangeRecordPermissions('iris_email', $new_id, $add_perm, $con);
		}
		
		// проставим ссылку на новое письмо в iris_mailing_contact
		$upd_cmd = $con->prepare("update iris_mailing_contact set emailid=:emailid where mailingid=:mailingid and contactid=:contactid");
		$upd_cmd->execute(array(":emailid"=> $new_id, ":mailingid" => $p_mailingid, ":contactid" => $contact['id']));
		if ($upd_cmd->errorCode() != '00000')		
			return array('success' => 0, 'message' => json_convert('ошибка при добавлении письма в расссылку'));

		// удалим старое письмо рассылки
		/*
		$del_cmd = $con->prepare("delete from iris_email where id=:id");
		$del_cmd->execute(array(":id" => $contact['emailid']));
		if ($del_cmd->errorCode() != '00000')		
			return array('success' => 0, 'message' => json_convert('ошибка при удалении старого письма'));
		*/
		
		// вставка файлов к письму
		$files_cmd = $con->prepare("insert into iris_email_file (id, fileid, emailid) (select iris_genguid(), fileid, '".$new_id."' from iris_mailing_file where mailingid=:mailingid)");
		if ($files_cmd->execute(array(":mailingid" => $p_mailingid)) == 0)
			return array('success' => 0, 'message' => json_convert('ошибка при добавлении файлов к письму'));
		
		$createcount++;
		
		// если скрипт скоро завершит работу, то остановимся
		$exec_time = get_sec() - $start_time;
		if ($exec_time + 5 > ini_get('max_execution_time')) {
			return array('success' => 1, 'leftcount' => $leftcount - $createcount, 'message' => '');	
		}
	}

	return array('success' => 1, 'message' => json_convert('Все письма созданы'));	
}

function GetFullSql($p_show_info, $p_sql, $p_params, $p_con=null)
{
	//Получим данные отчета
	$con = GetConnection($p_con);
	
	//Пустые значения приведем к null
	foreach ($p_params as $key => $value) {
		if ('' == $value) {
			$p_params[$key] = null;
		}
	}
	
	$statement = $con->prepare($p_sql, array(PDO::ATTR_EMULATE_PREPARES => true));
	$statement->execute($p_params);
	
	$res_data = $statement->fetchAll(PDO::FETCH_ASSOC);
	return $res_data;
}

function AddContactFromReport($p_reportid, $p_reportcode, $p_filters, $p_mailingid) {
	if ($p_reportcode != '') {
		$con = db_connect();
		$cmd = $con->prepare("select id from iris_report where code=:code");
		$cmd->execute(array(":code" => $p_reportcode));
		$res = $cmd->fetchAll(PDO::FETCH_ASSOC);
		$p_reportid = $res[0]['id'];
	}
	
	$p_filters = json_decode(stripslashes($p_filters));

	//Подготовка отчета
	list($sql, $params, $show_info) = BuildReportSQL($p_reportid, $p_filters);
	$report_info = GetReportInfo($p_reportid);
	list($contacts, $tmp) = BuildReportData($show_info, $sql, $params); //mnv: с новой версией отчётов работает так
	//$contacts = $contacts[0]; // miv 13.01.2012
	if (count($contacts) == 0)
		return array("errno" => 2, "errm" => json_convert('Не найдено контактов, удовлетворяющих условиям поиска'));
	
	$sql = '';
	$con = db_connect();
	$cmd = $con->prepare("select id from iris_mailing_contact where mailingid=:mailingid and contactid=:contactid");
	foreach($contacts as $contact) {
		$cmd->execute(array(":mailingid" => $p_mailingid, ":contactid" => $contact['id']));
		$exists_id = current($cmd->fetchAll(PDO::FETCH_ASSOC));
		// если этого контакта еще нет в списке рассылки, то добавим его
		if ($exists_id == '') {
			$sql .= "insert into iris_mailing_contact (id, mailingid, contactid) values (iris_genguid(), '".$p_mailingid."', '".$contact['id']."');".chr(10);
		}
	}
	if ($sql == '')
		return array("errno" => 3, "errm" => json_convert('Данные контакты уже добавлены в рассылку'));
	
	if ($con->exec($sql) == 0)
		return array("errno" => 11, "errm" => json_convert('Не удалось добавить контактов в рассылку'));

	return array("errno" => 0, "errm" => '');
}

function DeleteEmails($p_mailingid) {
	$con = db_connect();
	
	GetCurrentUserRecordPermissions('iris_mailing', $p_mailingid, $permissions, $con);
	if ($permissions['w'] == 0) {
		return array('success' => 0, 'message' => json_convert('Для удаления писем необходимо иметь доступ на редактирование рассылки'));
	}
	
	$del_sql = "delete from iris_email where id in (select emailid from iris_mailing_contact where mailingid=:mailingid) and emailtypeid = (select id from iris_emailtype where code = 'Mailing_outbox')";
	$cmd = $con->prepare($del_sql);
	$cmd->execute(array(":mailingid" => $p_mailingid));
	if ($cmd->errorCode() != '00000') {
		return array('success' => 0, 'message' => json_convert('Не удалось удалить письма рассылки'));
	}
	
	return array('success' => 1, 'message' => '');
}

// -----------------------------------------------------------------------

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}

$path = $_SESSION['INDEX_PATH'];
session_write_close();

include_once $path.'/core/engine/applib.php';
include_once $path.'/core/engine/printform.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/config/common/Lib/access.php';
include_once $path.'/config/common/Lib/report.php';

SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

if (!empty($_GET['mode']) && $_GET['mode'] == 'preview') {
	$_POST['_func'] = 'preview';
}

$func = stripslashes($_POST['_func']);

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
    switch ($func) {
		case 'AddContact':
			$response = AddContact($_POST['contact_id'], $_POST['mailing_id']);
			break;
		case 'RemoveContact':
			$response = RemoveContact($_POST['contact_id'], $_POST['mailing_id']);
			break; 
		case 'preview':
			echo PrewiewEmail($_GET['contactid'], $_GET['mailingid']);
			return;
			break; 
		case 'CreateEmails':
			$response = CreateEmails($_POST['mailing_id']);
			break;
		case 'AddContactFromReport':
			$response = AddContactFromReport(!empty($_POST['_reportid']) ? $_POST['_reportid'] : null, 
					!empty($_POST['_reportcode']) ? $_POST['_reportcode'] : null, 
					$_POST['_filters'], $_POST['_mailingid']);
			break;
		case 'DeleteEmails':
			$response = DeleteEmails($_POST['mailing_id']);
			break;
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}
echo json_encode($response);

?>
