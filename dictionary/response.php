<?php

/**********************************************************************
response.php
Справочник - Ответы
**********************************************************************/


//Обработка изменения значения поля
function Response_FieldOnChange($p_FieldName, $p_FieldValue, $p_id = null, $p_con = null, $p_result = null)
{
	$con = GetConnection($p_con);
	$result = $p_result;
		
	switch ($p_FieldName) {

		case 'QuestionID':
      //TODO: сразу передавать значения Interview_Response_GetQuestionInfo()
      $result = Response_GetQuestionInfo($p_FieldValue, $con, $result);
			break;

		default:
			//TODO: сделать правильную посылку и обработку сообщений об ошибке
			$result = 'Неверное название поля: '.$p_FieldName;
	}

	return $result;
}


//Получить параметры вопроса по id вопроса в опросе
function Response_GetQuestionInfo($p_questionid, $p_con = null, $p_result = null)
{
	$con = GetConnection($p_con);

	//Получить ИД и код типа вопроса
	$select_sql = <<<EOD
select rt1.code as responsetypecode
from iris_Question q1 
left join iris_ResponseValueType rt1 on rt1.id = q1.valuetypeid
where q1.id = :p_questionid
EOD;
	$statement = $con->prepare($select_sql);
	$statement->execute(array(
    'p_questionid' => $p_questionid,
  ));
  $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
  $row = $rows[0];
  
  $result = $p_result;
  $result['Params']['ResponseTypeCode'] = $row['responsetypecode'];

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
$path = realpath(dirname(__FILE__)."/./../../");

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
      $response = Response_FieldOnChange(
        stripslashes($_POST['_p_FieldName']),
        stripslashes($_POST['_p_FieldValue']),
        stripslashes($_POST['_p_id'])
      );
      break;

    case 'GetQuestionInfo':
      $response = Response_FieldOnChange(
        'QuestionID',
        stripslashes($_POST['_p_questionid']), 
        null
      );
      break;

    default:
      $response = 'Неверное имя функции: '.$func;
  }
}

echo json_encode($response);

?>