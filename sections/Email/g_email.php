<?php
//********************************************************************
// Раздел E-mail. таблица записей
//********************************************************************

function Email_triggerStar($p_rec_id, $p_value) {
//sleep(1);
	// права RW
	include_once getPath().'/config/common/Lib/access.php';
	GetUserRecordPermissions('iris_email', $p_rec_id, GetUserId(), $permissions);
	if (($permissions['r'] == 0) or ($permissions['w'] == 0))
		return array("success" => 0);
	
	$val = ($p_value == 'false' ? 1 : 0);
	$con = db_connect();
	$cmd = $con->prepare("update iris_email set isimportant = :val where id=:id");
	$cmd->execute(array(":id" => $p_rec_id, ":val" => $val));
	$success = ($cmd->errorCode() == '00000' ? 1 : 0);

	return array("success" => $success);
}

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}			
}

$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/core/engine/printform.php';
SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

$func = stripslashes($_POST['_func']);

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
    switch ($func) {
		case 'triggerStar':
			$response = Email_triggerStar($_POST['id'], $_POST['currentValue']);
			break;
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}
echo json_encode($response);

?>
