<?php
/**********************************************************************
формирование печатных фор документов
**********************************************************************/

function GeneratePrintForm($p_pf_id, $p_rec_id, $path) {
	include_once GetPath().'/core/engine/printform.php';

	$con = db_connect();
	// покажем печатную форму только если пользователь имеет права на ее чтение
	$record_perm_arr = CheckRecordPermission('iris_printform', $p_pf_id, $con); // считали права на запись
	if ($record_perm_arr['R'] == 0) {
		header("Content-Type: text/html");
		return 'permission denied';
	}
	
	$result = FillForm($p_pf_id, $p_rec_id);

	return $result;
}


if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}			
}

$path = $_SESSION['INDEX_PATH'];

//include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/config/common/Lib/access.php';


//SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

$_POST['_func'] = $_GET['_func'];
$_POST['pf_id'] = $_GET['pf_id'];
$_POST['rec_id'] = $_GET['rec_id'];
$func = stripslashes($_POST['_func']);

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
    switch ($func) {
		case 'GeneratePrintForm':
			$response = GeneratePrintForm($_POST['pf_id'], $_POST['rec_id'], $path);
			break;
	
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}

echo $response;

?>
