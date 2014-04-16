<?php
/******************************************************
Раздел Задачи. Закладка Продукты. Карточка.
*******************************************************/


//Обработка изменения значения поля
function Task_Product_FieldOnChange($p_FieldName, $p_FieldValue)
{
	$con = GetConnection();		
	
	switch ($p_FieldName) {

		//Получить компанию по id объекта
		case 'ProductID':
			$p_result = GetValuesFromTable('Product', $p_FieldValue, array('UnitID', 'Price'), $con);
			break;
			
		default:
			//TODO: сделать правильную посылку и обработку сообщений об ошибке
			$p_result = 'Неверное название поля: '.$p_FieldName;
	}

	return json_encode($p_result);
}



///////////////////////////////////////////////////////

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}
$path = realpath(dirname(__FILE__)."/./../../../");

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';


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
		case 'FieldOnChange':
			$response = Task_Product_FieldOnChange(
				stripslashes($_POST['_p_FieldName']),
				stripslashes($_POST['_p_FieldValue'])
			);
			break;			
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}

echo $response;

?>