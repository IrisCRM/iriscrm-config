<?php
/********************************************************************************************************
функции для копирования прав доступа дочерним записям (сервер)
********************************************************************************************************/

function GetChildRules() {
	// массив, определяющий дочерние записи для всех разделов
	$childrules = array(
		array (
			"parent_table" => 'iris_account',
			"default_parent_column" => 'accountid',
			"child_tables" => array(
				'iris_contact' => null,
				'iris_email' => null,
				'iris_message' => '((T0.autorid in (select id from iris_contact where accountid=:parent_id)) or (T0.recipientid in (select id from iris_contact where accountid=:parent_id)))',
				'iris_task' => null,
				'iris_project' => null,
				'iris_invoice' => null,
				'iris_payment' => null,
				'iris_object' => null,
				'iris_incident' => null,
				'iris_file' => null,
			),
		),
		
		array (
			"parent_table" => 'iris_contact',
			"default_parent_column" => 'contactid',
			"child_tables" => array(
				'iris_email' => null,
				'iris_message' => '(T0.autorid=:parent_id or T0.recipientid=:parent_id)',
				'iris_task' => null,
				'iris_project' => null,
				'iris_invoice' => null,
				'iris_payment' => null,
				'iris_incident' => null,
				'iris_file' => null,
			)			
		),
		
		array (
			"parent_table" => 'iris_object',
			"default_parent_column" => 'objectid',
			"child_tables" => array(
				'iris_contact' => null,
				'iris_email' => '((T0.accountid in (select accountid from iris_object where id=:parent_id)) or (T0.contactid in (select contactid from iris_object where id=:parent_id)))',
				'iris_message' => '((T0.autorid in (select id from iris_contact where objectid=:parent_id)) or (T0.recipientid in (select id from iris_contact where objectid=:parent_id)))',
				'iris_task' => null,
				'iris_project' => null,
				'iris_incident' => null,
				'iris_file' => null,
			)			
		),

		array (
			"parent_table" => 'iris_task',
			"default_parent_column" => 'taskid',
			"child_tables" => array(
				'iris_task' => '((((T0.AccountID=(select AccountID from iris_Task where ID=:parent_id)) and (T0.AccountID is not null)) or ((T0.ContactID=(select ContactID from iris_Task where ID=:parent_id)) and (T0.ContactID is not null))) and (T0.ID != :parent_id))',
				'iris_contact' => '(((T0.AccountID=(select AccountID from iris_Task where ID=:parent_id) or (T0.AccountID=(select ccc.AccountID from iris_Task ttt inner join iris_Contact ccc on ttt.ContactID=ccc.ID where ttt.ID=:parent_id)) )) and (T0.AccountID is not null))',
				'iris_incident' => null,
				'iris_file' => null,
			)			
		),
		
		array (
			"parent_table" => 'iris_email',
			"default_parent_column" => 'emailid',
			"child_tables" => array(
				'iris_file' => 'T0.emailid = :parent_id or T0.id in (select fileid from iris_email_file where emailid = :parent_id)',
			)			
		),
		
		array (
			"parent_table" => 'iris_project',
			"default_parent_column" => 'projectid',
			"child_tables" => array(
				'iris_message' => null,
				'iris_offer' => null,
				'iris_pact' => null,
				'iris_invoice' => null,
				'iris_payment' => null,
				'iris_factinvoice' => null,
				'iris_bug' => null,
				'iris_task' => null,
				'iris_incident' => null,
				'iris_file' => null,
			),
		),
		
		array (
			"parent_table" => 'iris_payment',
			"default_parent_column" => 'paymentid',
			"child_tables" => array(
				'iris_task' => null,
				'iris_incident' => null,
				'iris_file' => null,
			),
		),
		
		array (
			"parent_table" => 'iris_issue',
			"default_parent_column" => 'issueid',
			"child_tables" => array(
				'iris_bug' => null,
				'iris_task' => null,
				'iris_incident' => null,
				'iris_file' => null,
			),
		),
		
		array (
			"parent_table" => 'iris_incident',
			"default_parent_column" => 'incidentid',
			"child_tables" => array(
				'iris_task' => null,
				'iris_answer' => null,
				'iris_file' => null,
				'iris_email' => null,
			),
		),
		
		array (
			"parent_table" => 'iris_answer',
			"default_parent_column" => 'answerid',
			"child_tables" => array(
				'iris_incident' => null,
				'iris_message' => null,
				'iris_file' => null,
			),
		),
		
		array (
			"parent_table" => 'iris_bug',
			"default_parent_column" => 'bugid',
			"child_tables" => array(
				'iris_task' => null,
				'iris_message' => null,
				'iris_file' => null,
			),
		),			
	);
	
	return $childrules;
}

function ApplyAccess($p_table_name, $p_record_id) {
	$childrules = GetChildRules();
	
	$con = db_connect(); // соединение с БД
 	// проверим, что пользователь имеет право изменять доступ у данной записи
	GetCurrentUserRecordPermissions($p_table_name, $p_record_id, $permissions, $con); // TODO: избавиться от зависимости на access.php
	if ($permissions['a'] != 1)
		return array("success" => 0, "message" => json_convert("Пользователь не имеет прав на изменение доступа данной записи"));;
	
	$table_rule = null; // получим правила для нужной таблицы
	foreach ($childrules as $rule) {
		if ($rule['parent_table'] == $p_table_name)
			$table_rule = $rule;
	}
	// если для данной таблицы не заданы правила дочерних записей, то сообщим об этом
	if ($table_rule == null)
		return array("success" => 0, "message" => json_convert("Для данной таблицы данная функция не предусмотрена"));
	
	// заменим доступ для всех дочерних записей всех таблиц на доступ от родительской записи
	foreach ($table_rule["child_tables"] as $table => $custom_clause) {
		// для каждой таблицы получим перечень дочерних записей, которые ссылаются на родительскую запись
		if ($custom_clause == null)
			$sql = "select id as id from ".$table." T0 where ".$table_rule["default_parent_column"]."=:parent_id";
		else
			$sql = "select id as id from ".$table." T0 where (".$custom_clause.")";
		$cmd = $con->prepare($sql);
		$cmd->execute(array(":parent_id" => $p_record_id));
		if ($cmd->errorCode() != '00000')
			return array("success" => 0, "message" => json_convert("Произошла ошибка при получения списка дочерних записей у таблицы <b>".$table."</b><br>Операция прервана"));
		
		$child_records = $cmd->fetchAll(PDO::FETCH_ASSOC);

		$del_sql = "delete from ".$table."_access where recordid=:child_id";
		$del_cmd = $con->prepare($del_sql);

		$ins_sql  = "insert into ".$table."_access (id, recordid, accessroleid, contactid, r, w, d, a) ";
		$ins_sql .= "select iris_genguid(), :child_id, accessroleid, contactid, r, w, d, a from ".$p_table_name."_access ";
		$ins_sql .= "where recordid = :parent_id";			
		$ins_cmd = $con->prepare($ins_sql);
		// для каждой дочерней записи удалим ее права доступа и установим права доступа от родительской записи
		$err_flag = 0;
		foreach ($child_records as $child_record) {
			
			// удаление старых прав дочерней записи
			//echo $del_cmd->getSQL(array(":child_id" => $child_record['id']));
			$del_cmd->execute(array(":child_id" => $child_record['id']));
			$del_res = $del_cmd->errorCode();
			if ($del_res != '00000')
				$err_flag = 1;
			
			// применение новых прав дочерней записи
			//echo $ins_cmd->getSQL(array(":child_id" => $child_record['id'], ":parent_id" => $p_record_id));
			$ins_cmd->execute(array(":child_id" => $child_record['id'], ":parent_id" => $p_record_id));
			$ins_res = $ins_cmd->errorCode();
			if ($ins_res != '00000')
				$err_flag = 1;
		}
		if ($err_flag == 1)
			return array("success" => 0, "message" => json_convert("Произошла ошибка при смене доступа у дочеренй таблицы <b>".$table."</b><br>Операция прервана"));
	}

	return array("success" => 1, "message" => json_convert("Права доступа применены успешно"));;
}

///////////////////////////////////////////////////////////////////////
if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}			
}
$path = realpath(dirname(__FILE__)."/./../../../");

include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/access.php';

SendRequestHeaders();

if (!isAuthorised()) {
	echo 'Не авторизован';
	die;
}

if (strlen($_POST['_func']) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
	switch ($_POST['_func']) {
		
		case 'ApplyAccess':
			$response = ApplyAccess($_POST['table_name'], $_POST['rec_id']);
			break;
		
		default:
			$response = 'Неверное имя функции: '.$_POST['_func'];
	}
}

if ((is_array($response) == true) and (count($response) > 0)) {
	echo json_encode($response);
}

?>