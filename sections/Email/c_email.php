<?php
//********************************************************************
// Раздел E-mail
//********************************************************************

//echo '23';
//ini_set('display_errors', 'on');

function FillTemplate($contactid, $templateid, $fillsubject, $fillbody, $address, $p_result=null) {
	$con = GetConnection();

	list($subject, $body) = GetFieldValuesByID('email', $templateid, array('subject', 'body'), $con);

	if ($fillsubject) {
		$subject = FillFormFromText($subject, 'Contact', $contactid);
		$p_result = FieldValueFormat('Subject', $subject, '', $p_result);
	}

	if ($fillbody) {
		$body = FillFormFromText($body, 'Contact', $contactid);	
		$p_result = FieldValueFormat('body', $body, '', $p_result);
	}
	
	//Также заполним при необходимости адрес email
	if (!$address && $contactid) {
		$address = GetFieldValueByID('contact', $contactid, 'email', $con);
		$p_result = FieldValueFormat('e_to', $address, '', $p_result);
	}
	
	return $p_result;
}

//Значения по умолчанию
function Email_GetDefaultValues() {
	$con = GetConnection();

	//Значения справочников
	$l_result = GetDictionaryValues(
		array (
			array ('Dict' => 'EmailType', 'Code' => 'Outbox')
		), 
		$con);
		
	//Ответственный
	$username = GetUserName();
	$l_result = GetDefaultOwner($username, $con, $l_result);
	
	$templateid = GetFieldValueByFieldValue('contact', 'login', $username, 'emailtemplateid', $con);
	if ($templateid) {
		$templatename = GetFieldValueByID('email', $templateid, 'subject', $con);
		$l_result = FieldValueFormat('emailtemplateid', $templateid, $templatename, $l_result);
	}
				
	return $l_result;
}


//Обработка изменения значения поля
function Email_FieldOnChange($p_FieldName, $p_FieldValue, $p_result=null, $p_id=null)
{
	$con = GetConnection();		
		
	switch ($p_FieldName) {

		//Получить компанию и объект по id контакта
		case 'ContactID':
			$p_result = GetLinkedValues('Contact', $p_FieldValue, array('Account', 'Object'), $con);
			break;
			
		//Обязательные поля в зависимости от типа письма
		case 'EmailTypeID':
			$EmailTypeCode = GetFieldValueByID('emailtype', $p_FieldValue, 'Code', $con);
			if ('Template' == $EmailTypeCode) {
				$p_result['Attributes'][0]['FieldName'] = 'emailaccountid';
				$p_result['Attributes'][0]['AttributeName'] = 'mandatory';
				$p_result['Attributes'][0]['AttributeValue'] = 'no';
				$p_result['Attributes'][1]['FieldName'] = 'e_to';
				$p_result['Attributes'][1]['AttributeName'] = 'mandatory';
				$p_result['Attributes'][1]['AttributeValue'] = 'no';
				$p_result['Attributes'][2]['FieldName'] = 'e_from';
				$p_result['Attributes'][2]['AttributeName'] = 'mandatory';
				$p_result['Attributes'][2]['AttributeValue'] = 'no';
			}
			else {
				$p_result['Attributes'][0]['FieldName'] = 'emailaccountid';
				$p_result['Attributes'][0]['AttributeName'] = 'mandatory';
				$p_result['Attributes'][0]['AttributeValue'] = 'yes';
				$p_result['Attributes'][1]['FieldName'] = 'e_to';
				$p_result['Attributes'][1]['AttributeName'] = 'mandatory';
				$p_result['Attributes'][1]['AttributeValue'] = 'yes';
				$p_result['Attributes'][2]['FieldName'] = 'e_from';
				$p_result['Attributes'][2]['AttributeName'] = 'mandatory';
				$p_result['Attributes'][2]['AttributeValue'] = 'yes';
			}
			break;
		
		//Получить компанию по id объекта
		case '_emailaddress':
			//Посмотрим где есть такой id
			list($account) = GetFieldValuesByID('Account', $p_FieldValue, array('Name'), $con);
			list($contact) = GetFieldValuesByID('Contact', $p_FieldValue, array('Name'), $con);
			list($object) = GetFieldValuesByID('Object', $p_FieldValue, array('Name'), $con);

			if ($account != null) {
				$p_result = FieldValueFormat('AccountID', $p_FieldValue, $account, $p_result);
			}
			if ($contact != null) {
				$p_result = FieldValueFormat('ContactID', $p_FieldValue, $contact, $p_result);
				$p_result = GetLinkedValues('Contact', $p_FieldValue, array('Account', 'Object'), $con, $p_result);
			}
			if ($object != null) {
				$p_result = FieldValueFormat('ObjectID', $p_FieldValue, $object, $p_result);
				$p_result = GetLinkedValues('Object', $p_FieldValue, array('Account', 'Contact'), $con, $p_result);
			}
			
			break;
					
		default:
			//TODO: сделать правильную посылку и обработку сообщений об ошибке
			$p_result = 'Неверное название поля: '.$p_FieldName;
	}

	return $p_result;
}


//Обновление списка прочитавших
function Email_UpdateReaders($p_id, $p_readers, $p_con=null)
{
	$con = GetConnection($p_con);
	
	//Обновим список прочитавших
	$Fields = FieldValueFormat('has_readed', $p_readers);
	UpdateRecord('Email', $Fields['FieldValues'], $p_id, $con);
	
	//Результат не используется
	$result['UpdateReaders']['id'] = $p_id;
	return $result;
}


function Email_GetReplyFields($p_parent_id) {
	$con = db_connect();
	
	$result = GetFormatedFieldValuesByFieldValue('Email', 'ID', $p_parent_id, array('e_from', 'Subject', 'body'), $con);
	$result['FieldValues'][0]['Name'] = 'e_to';
	// miv 02.08.2010: если письмо привязано к инциденту, то в теме письма долен быть его номер
	$subject = $result['FieldValues'][1]['Value'];
	list($incident_id) = GetFieldValuesByFieldValue('email', 'id', $p_parent_id, array('incidentid'), $con);
	if ($incident_id != '') {
		list($incident_number) = GetFieldValuesByFieldValue('incident', 'id', $incident_id, array('number'), $con);
		$result = FieldValueFormat('IncidentID', $incident_id, $incident_number, $result);
		if (iris_preg_match("/\\[\\d{6}-\\d+\\]/", $subject, $matches, PREG_OFFSET_CAPTURE) == 0) {
			// если в теме письма не обнаружили инцидента
			$result['FieldValues'][1]['Value'] = 'Re: ['.$incident_number.'] '.$result['FieldValues'][1]['Value'];
		}
	} else {
		if (iris_substr($subject, 0, 3) != 'Re:')
			$result['FieldValues'][1]['Value'] = 'Re: '.$subject;
	}
	
	// подставим текст письма как шаблон ответа + текст старого письма
	$templateid = GetFieldValueByFieldValue('contact', 'id', GetUserId(), 'emailtemplateid', $con);
	$templatebody = GetFieldValueByFieldValue('email', 'id', $templateid, 'body', $con);
	$contactid = GetFieldValueByFieldValue('email', 'id', $p_parent_id, 'contactid', $con);
	$templatebody = FillFormFromText($templatebody, 'Contact', $contactid);	
	
	//$parentbody = '<br><br>Вы писали:<br>'.GetFieldValueByFieldValue('email', 'id', $p_parent_id, 'body', $con);
	$parentbody = '<br><br>Вы писали:<br><BLOCKQUOTE style="margin: 5px 0 0 5px; padding: 0 0 0 5px; border-left: 2px solid #484F9E">'.GetFieldValueByFieldValue('email', 'id', $p_parent_id, 'body', $con).'</BLOCKQUOTE>';
	$result['FieldValues'][2]['Value'] = json_convert($templatebody).json_convert($parentbody);
	$result['FieldValues'][] = array("Name" => '_parent_body', "Value" => json_convert($parentbody));

	$result = GetLinkedValues('Email', $p_parent_id, array('Account', 'Contact'), $con, $result);

	return $result;
}






if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}

$path = $_SESSION['INDEX_PATH'];

//include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/core/engine/printform.php';
SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

//echo 'dfsd';

$func = stripslashes(!empty($_POST['_func']) ? $_POST['_func'] : '');

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
    switch ($func) {
		case 'GetDefaultValues':
			$response = Email_GetDefaultValues();
			break;

		case 'FieldOnChange':
			$response = Email_FieldOnChange(
				stripslashes($_POST['_p_FieldName']),
				stripslashes($_POST['_p_FieldValue'])
			);
			break;			
			
		case 'FillTemplate':
			$response = FillTemplate(
				stripslashes($_POST['_p_contactid']),
				stripslashes($_POST['_p_emailtemplateid']),
				stripslashes($_POST['_p_fillsubject']),
				stripslashes($_POST['_p_fillbody']),
				stripslashes($_POST['_p_address'])
			);
			break;			
			
		case 'UpdateReaders':
			$response = Email_UpdateReaders(stripslashes($_POST['_p_id']), stripslashes($_POST['_p_readers']));
			break;
		
		case 'GetReplyFields':
			$response = Email_GetReplyFields(stripslashes($_POST['_p_parent_id']));
			break;
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}
echo json_encode($response);

?>
