<?php
//********************************************************************
// Раздел Сообщения
//********************************************************************

ini_set('display_errors', 'on');


//При сохранении карточки изменяет права доступа сообщения. 
//Получатель может читать сообщение, а создатель только читать
function ChangeAccess($p_rec_id) {
	$con = GetConnection();
	list ($AuthorID, $RecipientID) = 
		GetFieldValuesByID('Message', $p_rec_id, array('AutorID', 'RecipientID'), $con);
	
	$permissions[] = array(
		'userid' => $AuthorID, 
		'roleid' => '', 
		'r' => 1, 
		'w' => 0, 
		'd' => 0, 
		'a' => 0
	);
	$permissions[] = array(
		'userid' => $RecipientID, 
		'roleid' => '', 
		'r' => 1, 
		'w' => 0, 
		'd' => 0, 
		'a' => 0
	);
	$res = ChangeRecordPermissions('iris_Message', $p_rec_id, $permissions);
}


// если пользователь = "кому", то установим статус сообщения в "прочитано"
// если пользователь = автор то его записывать в прочитавшие не будем
// если пользователь открыл карточку, то запомним что он ее читал
//TODO: привести  к единому формату
function SaveReaded($p_rec_id) {
	$con = db_connect();
	$user_id = GetUserID($con);	
	
	$query = $con->prepare("select autorid, recipientid from iris_message where id=:id");
	$query->execute(array(":id" => $p_rec_id));
	$message_res = $query->fetchAll(PDO::FETCH_ASSOC);
	
	// если пользователь = "кому", то установим статус сообщения в "прочитано"
	if ($message_res[0]['recipientid'] == $user_id) {
		$upd_query = $con->prepare("update iris_message set StatusID=(select id from iris_messagestatus where code='Readed') where id=:id");
		$upd_query->execute(array(":id" => $p_rec_id));

	}
	
	// если пользователь = автор то его записывать в прочитавшие не будем
	if ($message_res[0]['autorid'] == $user_id) {
		return;
	}
	
	// найдем, есть ли запись о том, что данный пользователь уже прочитал сообщение
	$ex_query = $con->prepare("select 1 from iris_message_contact where messageid=:messageid and contactid=:contactid");
	$ex_query->execute(array(":messageid" => $p_rec_id, ":contactid" => $user_id));
	$ex_query_res = $ex_query->fetchAll();

	// если записи еще нет, то вставим ее
	if ($ex_query_res[0][0] != 1) {
		$ins_query = $con->prepare("insert into iris_message_contact (id, messageid, contactid, readdate) values (:id, :messageid, :contactid, now())");
		$ins_query->execute(array(":id" =>  create_guid(), ":messageid" => $p_rec_id, ":contactid" => $user_id));
	}
	return array("success" => 1);
}


//Получить получателя из проекта
function GetRecipientFromProject($record_id, $p_user_id, $p_user_type) {
//	$con = GetConnection();
		
	//Возьмем из проекта Контакта и Ответственного
	$result = GetLinkedValuesDetailed('iris_Project', $record_id, array(
		array('Field' => 'OwnerID',
			'GetField' => 'Name',
			'GetTable' => 'iris_Contact'),
		array('Field' => 'ContactID',
			'GetField' => 'Name',
			'GetTable' => 'iris_Contact')
	));
	
	$ContactID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'ContactID', 'Value');	
	
	//Если пользователь не клиент, то Кому - клиент, иначе - ответственный
	if ($ContactID != $p_user_id) {
		$result['FieldValues'][0] = $result['FieldValues'][1];
	}
	unset($result['FieldValues'][1]);
	$result['FieldValues'][0]['Name'] = 'RecipientID';
		
	return $result;	
}


//Получить получателя из решения
function GetRecipientFromAnswer($record_id, $p_user_id, $p_user_type) {
//	$con = GetConnection();
		
	//Возьмем из проекта Контакта и Ответственного
	$result = GetLinkedValuesDetailed('iris_Answer', $record_id, array(
		array('Field' => 'OwnerID',
			'GetField' => 'Name',
			'GetTable' => 'iris_Contact')//,
//		array('Field' => 'AuthorID',
//			'GetField' => 'Name',
//			'GetTable' => 'iris_Contact')
	));
	
	$ContactID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'OwnerID', 'Value');	
	
	//Если пользователь не клиент, то Кому - клиент, иначе - ответственный
	if ($ContactID == $p_user_id) {
		$result['FieldValues'][0]['Value'] = null;
	}
//	unset($result['FieldValues'][1]);
	$result['FieldValues'][0]['Name'] = 'RecipientID';
		
	return $result;	
}

//Получить получателя из замечания
function GetRecipientFromBug($record_id, $p_user_id, $p_user_type) {
//	$con = GetConnection();
		
	//Возьмем из проекта Контакта и Ответственного
	$result = GetLinkedValuesDetailed('iris_Bug', $record_id, array(
		array('Field' => 'OwnerID',
			'GetField' => 'Name',
			'GetTable' => 'iris_Contact'),
		array('Field' => 'FindID',
			'GetField' => 'Name',
			'GetTable' => 'iris_Contact')
	));
	
	$ContactID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'FindID', 'Value');	
	
	//Если пользователь не клиент, то Кому - клиент, иначе - ответственный
	if ($ContactID == $p_user_id) {
		$result['FieldValues'][0] = $result['FieldValues'][1];
	}
	unset($result['FieldValues'][1]);
	$result['FieldValues'][0]['Name'] = 'RecipientID';
		
	return $result;	
}


//Получить получателя из контакта
function GetRecipientFromContact($record_id) {
//	$con = GetConnection();
		
	//Возьмем Ответственного контакта
	$result = GetLinkedValuesDetailed('iris_Contact', $record_id, array(
		array('Field' => 'ID',
			'GetField' => 'Name',
			'GetTable' => 'iris_Contact')
	));
	
	$result['FieldValues'][0]['Name'] = 'RecipientID';
		
	return $result;	
}


//Получатель = ответсвенный за клиента или ответсвенный за последний активный заказ
function GetMessageOwner($p_user_id, $p_con=null) {

	//ini_set('display_errors', 'on');
	$con = GetConnection($p_con); //db_connect();
	$user_id = $p_user_id;
	
	//Получим последний проекта пользователя	
	$result = SetProject($user_id, $con);
	$project_id = GetArrayValueByParameter($result['FieldValues'], 'Name', 'ProjectID', 'Value');
	
	//Если есть проект, то возьмем из проекта Ответственного
	if (!IsEmptyValue($project_id)) {
		$result = GetLinkedValuesDetailed('iris_Project', $project_id, array(
			array('Field' => 'OwnerID',
				'GetField' => 'Name',
				'GetTable' => 'iris_Contact')
		), $con, $result);
	} 
	//Иначе возьмем Ответственного за текущего пользователя
	else {
		$result = GetLinkedValuesDetailed('iris_Contact', $user_id, array(
			array('Field' => 'OwnerID',
				'GetField' => 'Name',
				'GetTable' => 'iris_Contact')
		), $con, $result);
	}

	//Заменим OwnerID на Recipient (Кому)
	$result['FieldValues'][1]['Name'] = 'RecipientID';
	return $result;	
}


//Последний активный заказ
function SetProject($p_client_id, $p_con=null) {
	$con = GetConnection($p_con);
	require_once GetPath().'/config/common/Lib/project.php'; // тут функция GetRecentProject
	return GetRecentProject($p_client_id, $con);
}


//Значения полей при ответе
//TODO: привести  к единому формату
function GetReplyFields($p_message_id) {
	$con = db_connect();
	$sql  = "select T0.subject as subject, T1.id as project_id, T1.name as project_name, T2.id as product_id, T2.name as product_name, T3.id as recipient_id, T3.name as recipient_name from iris_message T0";
	$sql .= " left join iris_project T1 on T0.projectid=T1.id";
	$sql .= " left join iris_product T2 on T0.productid=T2.id";
	$sql .= " left join iris_contact T3 on T0.autorid=T3.id";
	$sql .= " where T0.id=:id";
	$query = $con->prepare($sql);
	$query->execute(array(":id" => $p_message_id));
	$query_res = $query->fetchAll(PDO::FETCH_ASSOC);
	$query_res = $query_res[0]; // перейдем на первую строку

	$query_res['subject'] = json_convert($query_res['subject']);
	$query_res['project_name'] = json_convert($query_res['project_name']);
	$query_res['product_name'] = json_convert($query_res['product_name']);
	$query_res['recipient_name'] = json_convert($query_res['recipient_name']);

	return $query_res;
}

function highlightNewMessages($p_ids) {
	$result = null;
	$ids_arr = json_decode($p_ids, true);
	$id_str = '';
	$pattern = ".[а-яА-я\\.,!@#$%\\^&\\*() ~`_+\\\\\\[\\]\\{\\}]."; // недопустимые символы
	foreach ($ids_arr as $id) {
		if (iris_preg_match($pattern, $id))
			return;
		$id_str .= ", '".$id."'";
	}
	$id_str = substr($id_str, 2);
	
	// сообщения новые, если пользователь не их автор и его нет в списке прочитавших
	$sql  = "select T0.id as msg_id, T0.autorid, T0.message, T1.contactid ";
	$sql .= "from iris_message T0 ";
	$sql .= "left join iris_message_contact T1 on T0.ID = T1.messageid and T1.contactid = :user_id ";
	$sql .= "where T0.id in (".$id_str.") ";
	$sql .= "  and T0.autorid <> :user_id ";
	$sql .= "  and T1.contactid is null";
	$con = db_connect();
	$query = $con->prepare($sql);
	$query->execute(array(":user_id" => GetUserID($con)));
	$query_res = $query->fetchAll(PDO::FETCH_ASSOC);
	
	foreach ($query_res as $rec) {
		$result[] = $rec['msg_id'];
	}

	return json_encode($result);
}



if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}
// miv 20.05.2009: Заканчивам текущую сессию и сохраняем данные сессии
// Поскольку данные сессии блокируются для предотвращения конкурирующей записи, только один скрипт может работать с сессией в данный момент времени
// Данные сессии нам нужны тоьлко для чтения, поэтому сразу зарываем сессию
session_write_close();

$path = $_SESSION['INDEX_PATH'];

//include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/config/common/Lib/access.php';

SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

//$_POST['_func'] = $_GET['_func'];
$func = stripslashes($_POST['_func']);
$response = null;

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
    switch ($func) {
		case 'ChangeAccess':
			$response = ChangeAccess($_POST['rec_id']);
			break;

		case 'SaveReaded':
			$response = SaveReaded($_POST['rec_id']);
			break;
			
		case 'GetRecipientFromProject':
			$response = GetRecipientFromProject(stripslashes($_POST['record_id']), stripslashes($_POST['user_id']), stripslashes(!empty($_POST['p_user_type']) ? $_POST['p_user_type'] : null));
			break;
		case 'GetRecipientFromAnswer':
			$response = GetRecipientFromAnswer(stripslashes($_POST['record_id']), stripslashes($_POST['user_id']), stripslashes(!empty($_POST['p_user_type']) ? $_POST['p_user_type'] : null));
			break;
		case 'GetRecipientFromBug':
			$response = GetRecipientFromBug(stripslashes($_POST['record_id']), stripslashes($_POST['user_id']), stripslashes(!empty($_POST['p_user_type']) ? $_POST['p_user_type'] : null));
			break;
			
		case 'GetRecipientFromContact':
			$response = GetRecipientFromContact(stripslashes($_POST['record_id']));
			break;
			
		case 'GenerateNewOwner':
			$response = GetMessageOwner(stripslashes($_POST['user_id']));
			break;

		case 'SetProject':
			$response = SetProject(stripslashes($_POST['client_id']));
			break;
			
		case 'GetReplyFields':
			$response = GetReplyFields($_POST['message_id']);
			break;

		case 'SendEmailToUser':
			//$response = SendEmailToUser($_POST['rec_id']);
			//require_once GetPath().'/config/common/Lib/message.php';
			//$response = SendEmailToUser($_POST['rec_id']);
			break;
		
		case 'highlightNewMessages':
			$response = highlightNewMessages($_POST['ids']);
			break;				

		default:
			$response = 'Неверное имя функции: '.$func;
	}
}

echo json_encode($response);
?>
