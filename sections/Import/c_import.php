<?php
//********************************************************************
// Карточка импорта
//********************************************************************

//Значения по умолчанию
function Import_GetDefaultValues($p_result=null)
{
	$con = GetConnection();
	
	//Значения справочников
	$result = GetDictionaryValues(
		array (
			array ('Dict' => 'ImportType', 'Code' => 'XLS')
		), $con, $p_result);

	//Ответственный
	$UserName = GetUserName();
	$result = GetDefaultOwner($UserName, $con, $result);

	//Кодировка
	$result = FieldValueFormat('Encoding', 'cp1251', null, $result);
	
	//Дата
//	$Date = GetCurrentDBDate($con);
//	$result = FieldValueFormat('Date', $Date, null, $result);

	return $result;
}


///////////////////////////////////////////////////////

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}

// не закрывать сессию так как тут пишутся параметры для смены ответсвенного
//session_write_close();

$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/applib.php';
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
	
		case 'GetDefaultValues':
			$response = Import_GetDefaultValues();
			break;
	
//		default:
//			$response = 'Неверное имя функции: '.$func;
	}
}

if ((is_array($response) == true) and (count($response) > 0)) {
	echo json_encode($response);
}

?>