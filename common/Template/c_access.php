<?php

function Access_applyAccess($p_table, $p_id_list, $access) {
	$con = db_connect();
	
	// Только админ
	$info = GetUserAccessInfo($con);
	if ($info['userrolecode'] != 'admin')
		return array("success" => 0, "message" => json_convert('Данная функция доступна только администратору'));
	
	// если такой таблицы нет или у нее не включен доступ по записям, то "Для данной таблицы эта функция невозможа"
	$cmd = $con->prepare("select id from iris_table where code=:code and is_access = '1'");
	$cmd->execute(array(":code" => strtolower($p_table)));
	$res = $cmd->fetchAll(PDO::FETCH_ASSOC);
	if ($res[0][0] != '')
		return array("success" => 0, "message" => json_convert('У данной таблицы не включен доступ по запиям'));
	
	
	$access = json_decode($access, true);
	$p_id_list = json_decode($p_id_list, true);

	//print_r($access);
	// проверим, что задан что то одно: userid или accessroleid
	$acr_flag = (strlen($access['accessroleid']) == 36);
	$usr_flag = (strlen($access['userid']) == 36);
	if (($acr_flag xor $usr_flag) == false)
		return array("success" => 0, "message" => json_convert('Необходимо указать или роль или пользователя'));
	
	if ($acr_flag) {
		$column_name = 'accessroleid';
		$value = $access['accessroleid'];
	} else {
		$column_name = 'contactid';
		$value = $access['userid'];
	}
	
	// удалим старые записи
	$del_sql = "delete from ".$p_table."_access where ".$column_name." =:column_id and recordid in (select * from iris_explode_str(',', :id_list))";
	$del_cmd = $con->prepare($del_sql);
	$del_cmd->execute(array(":column_id" => $value, ":id_list" => implode(',', $p_id_list)));
	//echo $del_cmd->getSQL(array(":column_id" => $value, ":id_list" => implode(',', $p_id_list)));
	if ($del_cmd->errorCode() != '00000')
		return array("success" => 0, "message" => json_convert('<b>Внимание!</b> Возникла ошибка при удалении старых значений прав доступа'));

	if ( !(($access['mode'] == 'soft') and ($access['r'] == 0) and ($access['w'] == 0) and ($access['d'] == 0) and ($access['a'] == 0)) ) {
		// добавим новые записи
		$ins_sql  = "insert into ".$p_table."_access (id, recordid, ".$column_name.", r, w, d, a) ";
		$ins_sql .= "select iris_genguid(), iris_explode_str, :value, :r, :w, :d, :a from iris_explode_str(',', :id_list)";
		$ins_cmd = $con->prepare($ins_sql);
		$ins_cmd->execute(array(":value" => $value, ":r" => $access['r'], ":w" => $access['w'], ":d" => $access['d'], ":a" => $access['a'], ":id_list" => implode(',', $p_id_list)));
		//echo $ins_cmd->getSQL(array(":value" => $value, ":r" => $access['r'], ":w" => $access['w'], ":d" => $access['d'], ":a" => $access['a'], ":id_list" => implode(',', $p_id_list)));
		if ($ins_cmd->errorCode() != '00000')
			return array("success" => 0, "message" => json_convert('<b>Внимание!</b> Возникла ошибка при добавлении новых значений прав доступа'));
	}
	
	return array("success" => 1, "message" => json_convert('Доступ успешно изменен'));
}

///////////////////////////////////////////////////////

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}
}
$path = realpath(dirname(__FILE__)."/./../../../");

include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';

SendRequestHeaders();


if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}


$func = stripslashes($_POST['_func']);
$response = '';
if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
} 
else {

    switch ($func) {

		case 'applyAccess':
			$response = Access_applyAccess($_POST['table'], $_POST['id_list'], $_POST['access']);
			break;

		default:
			$response = 'Неверное имя функции: '.$func;
	}
}

if ((is_array($response) == true) and (count($response) > 0)) {
	echo json_encode($response);
}

?>