<?php

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}
$path = realpath(dirname(__FILE__)."/./../../../");

include_once $path.'/config/common/Lib/lib.php';


SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}


$func = stripslashes($_POST['_func']);
$response = '';
if (strlen($func) == 0) {
//	$response = PrintError('Имя функции не задано');
} 
else {

    switch ($func) {
		case 'ChangeOwner':
			$response = ChangeOwner($_POST['mode'], $_POST['record_id'], $_POST['owner_b_id']);
			break;

		case 'ChangeChildOwner':
			$response = ChangeChildOwner($_POST['table'], $_POST['record_id'], $_POST['owner_b_id'], $_POST['mode']);
			break;
						
//		default:
//			$response = 'Неверное имя функции: '.$_POST['_func'];
	}
}

if ((is_array($response) == true) and (count($response) > 0)) {
	echo json_encode($response);
}





//Функция смены ответсвенного для сущности (компания, контакт, проект, ...)
//$p_table_name - таблица сущности без префикса ('account', 'contact'...)
//$p_record_id - id записи сущности
//$p_owner_b_id - id нового ответственного
//[$p_parent_mode=0] - режим: 0 - работать с основной таблицей; 1 - работать с дочерней таблицей
//[$p_parent_table=''] - название родительской таблицы
//[$p_parent_id=''] - id записи в родительской таблице
function ChangeOwner($p_table_name, $p_record_id, $p_owner_b_id, $p_parent_mode=0, $p_parent_table='' , $p_parent_id='') {
	$con = GetConnection();

	//Такущий пользователь не администратор? Тогда выход
	if (0 == IsUserInAdminGroup($con)) {
		return array('errm' => 'permission denied');
	}

	if (0 != $p_parent_mode) {
		//mnv: Старого ответственного получим из id записи
		$p_owner_a_id = $_SESSION['CHOWN_OLD_OWNERID'];

		//Текст запроса для получения записей для обновления прав
		//если это таблица сообщений, то условие другое
		if ($p_table_name == 'message') {
			if ('contact' == $p_parent_table) {
				$record_list_sql = "select id from iris_message where autorid=:owna or recipientid=:owna";
				$first_param = array(":owna" => $p_owner_a_id);
			}
			else
			if ('account' == $p_parent_table) {
				$record_list_sql = "select id from iris_message where autorid in (select id from iris_contact where accountid=:p_account_id and ownerid=:owna) or recipientid in (select id from iris_contact where accountid=:p_account_id and ownerid=:owna)";
				$first_param = array(
					":p_account_id" => $p_parent_id,
					":owna" => $p_owner_a_id
				);
			}
			else
			if ('project' == $p_parent_table) {
				$record_list_sql = "select id from iris_message where projectid=:rec_id and ((autorid=:p_owner_id1) or (recipientid=:p_owner_id2))";
				$first_param = array(
					":rec_id" => $p_parent_id,
					":p_owner_id1" => $p_owner_a_id,
					":p_owner_id2" => $p_owner_a_id
				);
			}
		}
		else {
			$record_list_sql = "select id from iris_".$p_table_name." where ".$p_parent_table."id=:rec_id and ownerid=:p_owner_id";
			$first_param = array(
				":rec_id" => $p_parent_id,
				":p_owner_id" => $p_owner_a_id
			);
		}
	}
	else {
		//mnv: Старого ответственного получим из id записи
		$p_owner_a_id = GetFieldValueByID($p_table_name, $p_record_id, 'OwnerID', $con);
		$_SESSION['CHOWN_OLD_OWNERID'] = $p_owner_a_id;

		//Выбрать текущую запись
		$record_list_sql = "select id from iris_".$p_table_name." where id=:p_id";
		$first_param = array(":p_id" => $p_record_id);	
	}

	//1. получим список записей для обновления прав
	//(у которых ответсвенный равен p_owner_a_id ([R]) либо просто текущую запись
	$sel_qry = $con->prepare($record_list_sql);
	$sel_qry->execute($first_param);
	$sel_res = $sel_qry->fetchAll(PDO::FETCH_ASSOC); //тут хранятся id записей [R]

	if (0 == $p_parent_mode) {
		//записываем [R] в сессию. mnv: теперь тут будет только 1 id, родительский
		$_SESSION['CHOWN_ID_LIST'] = json_encode($sel_res);
	}

	//2. поменяем права доступа у всех записей [R]
	//Если в доступе указан ответственный Б, то убираем эти записи из вкладки «Доступ», чтобы не дублировать. 
	$del_qry = $con->prepare("delete from iris_".$p_table_name."_access where recordid in (".$record_list_sql.") and contactid=:ownb");
	$del_qry->execute(array_merge($first_param, array(":ownb" => $p_owner_b_id)));

	//Смотрим, прописан ли в доступе ответственный А. Если да, то в доступе записи с ответственным А меняем на ответственного Б
	//TODO: этот запрос не срабатывает для message
	$upd_qry = $con->prepare("update iris_".$p_table_name."_access set contactid=:ownb where recordid in (".$record_list_sql.") and contactid=:owna");
	$upd_qry->execute(array_merge($first_param, array(":owna" => $p_owner_a_id, ":ownb" => $p_owner_b_id)));
//$sql = "update iris_".$p_table_name."_access set contactid=:ownb where recordid in (".$record_list_sql.") and contactid=:owna";
//$arr = array_merge($first_param, array(":owna" => $p_owner_a_id, ":ownb" => $p_owner_b_id));
//	$upd_qry = $con->prepare($sql);
//	$upd_qry->execute($arr);
//print_r($sql);
//print_r($arr);

//echo $record_list_sql;
//print_r($first_param);
//echo $p_owner_a_id.'/'.$p_owner_b_id;
	
	// 3. сменим ответсвенного у записей [R]
	if ('message' != $p_table_name) {
		$rec_upd_qry = $con->prepare("update iris_".$p_table_name." set ownerid=:ownb where id in (".$record_list_sql.")");
		foreach ($sel_res as $row) {
			$rec_upd_qry->execute(array_merge($first_param, array(
				":ownb" => $p_owner_b_id
			)));
		}
	}
}

function ChangeChildOwner($p_table_name, $p_record_id, $p_owner_b_id, $p_parent_table) {
	//Теперь этот массив = $p_record_id
	$rec_id_arr = json_decode($_SESSION['CHOWN_ID_LIST'], true);

	foreach ($rec_id_arr as $rec) {
		ChangeOwner($p_table_name, $p_record_id, $p_owner_b_id, 1, $p_parent_table, $rec['id']);
		//Если это таблица сообщений, то достаточно изменить только 1 и только 1 раз
		if ($p_table_name == 'message') {
			return;
		}
	}
}

?>