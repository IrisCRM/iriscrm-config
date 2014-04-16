<?php
//********************************************************************
// Раздел "Интервью". Карточка.
//********************************************************************

//Обработка изменения значения поля
function Interview_FieldOnChange($p_FieldName, $p_FieldValue, $p_id, $p_con, $p_result)
{
	$con = GetConnection($p_con);
	$result = $p_result;
		
	switch ($p_FieldName) {

		//Получить Реквизиты по id компании
		case 'AccountID':
			$result = GetLinkedValuesDetailed('iris_Account', $p_FieldValue, array(array(
        'GetTable' => 'iris_Contact',
        'Field' => 'PrimaryContactID',
        'GetField' => 'Name',
      )), $con, $result);
			$result['FieldValues'][count($result['FieldValues'])-1]['Name'] = 'ContactID';
			$phone = GetFieldValueByID('Contact', $result['FieldValues'][count($result['FieldValues'])-1]['Value'], 'Phone1', $con);
			$result = FieldValueFormat('Phone', $phone, null, $result);
			break;
			
		//Получить компанию по id контакта
		case 'ContactID':
			$result = GetLinkedValues('Contact', $p_FieldValue, array('Account'), $con, $result);
			$phone = GetFieldValueByID('Contact', $p_FieldValue, 'Phone1', $con);
			$result = FieldValueFormat('Phone', $phone, null, $result);
			break;

		//Изменение состояния ведет к изменению даты оплаты (если оплачено)
		//и суммы оплаты (если нет платежей)
		case 'InterviewResultID':
			$ResultCode = GetFieldValueByID('InterviewResult', $p_FieldValue, 'Code', $con);
			if ($ResultCode == 'finished') {
        //Дата последней попытки
				$date = GetCurrentDBDate($con);
				$result = FieldValueFormat('LastDate', $date, null, $result);
        //Состояние
        $result = GetDictionaryValues( array (
          array ('Dict' => 'InterviewState', 'Code' => 'finished')
        ), $con, $result);
        //TODO: подсчёт результата - тут или в ответах
			}
			break;

		default:
			//TODO: сделать правильную посылку и обработку сообщений об ошибке
			$result = 'Неверное название поля: '.$p_FieldName;
	}

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
//miv 20.05.2009: Заканчивам текущую сессию и сохраняем данные сессии
//Поскольку данные сессии блокируются для предотвращения конкурирующей записи, 
//только один скрипт может работать с сессией в данный момент времени
//Данные сессии нам нужны тоьлко для чтения, поэтому сразу зарываем сессию
session_write_close();

$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/config/common/Lib/access.php';

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

		case 'FieldOnChange':
			$response = Interview_FieldOnChange(
				stripslashes($_POST['_p_FieldName']),
				stripslashes($_POST['_p_FieldValue']),
				stripslashes($_POST['_p_id'])
			);
			break;

//		default:
//			$response = 'Неверное имя функции: '.$func;
	}
	
}

if ((is_array($response) == true) and (count($response) > 0)) {
	echo json_encode($response);
}

?>
