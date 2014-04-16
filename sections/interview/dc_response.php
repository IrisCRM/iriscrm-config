<?php
//********************************************************************
// Раздел "Интервью". Закладка "Ответы".
//********************************************************************

//Параметры интервью
function Interview_Response_GetInterviewParams($p_id)
{
	$con = GetConnection();

	//Номер добавляемой позиции
	$select_sql = "select pollid from iris_Interview where id = :p_id";
	$statement = $con->prepare($select_sql);
	$statement->bindParam(':p_id', $p_id);
	$statement->execute();
	$statement->bindColumn(1, $PollID);
	$res = $statement->fetch();
  
  $result = null;
  $result['Params']['PollID'] = $PollID;

	return $result;
}



//Обработка изменения значения поля
function Interview_Response_FieldOnChange($p_FieldName, $p_FieldValue, $p_id, $p_con = null, $p_result = null)
{
	$con = GetConnection($p_con);
	$result = $p_result;
		
	switch ($p_FieldName) {

		case 'PollQuestionID':
      //Получение QuestionID и передача его на клиент
			$result = GetLinkedValues('Poll_Question', $p_FieldValue, array('Question'), $con, $result);
      //TODO: сразу передавать значения Interview_Response_GetQuestionInfo()
			break;

    case 'ResponseID':
      //Получение оценки и передача её на клиент
      $mark = GetFieldValueByID('Response', $p_FieldValue, 'Mark', $con);
      $result = FieldValueFormat('mark', $mark, null, $result);
      break;

		default:
			//TODO: сделать правильную посылку и обработку сообщений об ошибке
			$result = 'Неверное название поля: '.$p_FieldName;
	}

	return $result;
}


//Получить параметры вопроса по id вопроса в опросе
function Interview_Response_GetQuestionInfo($p_pollquestionid, $p_interviewid)
{
	$con = GetConnection();
  $result_values = null;

	//Получить ИД и код типа вопроса
	$select_sql = <<<EOD
select rt1.code as responsetypecode, q1.id as questionid
from iris_Poll_Question pq1 
left join iris_Question q1 on q1.id=pq1.questionid
left join iris_ResponseValueType rt1 on rt1.id=q1.valuetypeid
where pq1.id = :p_pollquestionid
EOD;
	$statement = $con->prepare($select_sql);
	$statement->execute(array(
    'p_pollquestionid' => $p_pollquestionid,
  ));
  $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
  $row = $rows[0];
  
  $result = null;
  $result['Params']['ResponseTypeCode'] = $row['responsetypecode'];
  $questionid = $row['questionid'];
  
  //Если множественный выбор, то вернём также и поля-чекбоксы
  if ('Multi' == $row['responsetypecode']) {
    $select_sql = <<<EOD
select 
r1.stringvalue as name, 
r1.id as responseid, 
( select max(ir2.id) 
  from iris_interview_response ir2 
  where ir2.responseid=r1.id 
  and ir2.interviewid = :p_interviewid
  and ir2.pollquestionid = :p_pollquestionid
) as interviewresponseid,
( select max(ir3.intvalue) from iris_interview_response ir3 
  where ir3.responseid=r1.id 
  and ir3.interviewid = :p_interviewid
  and ir3.pollquestionid = :p_pollquestionid
) as responsevalue
from iris_response r1 
where r1.questionid = :p_questionid
order by r1.orderpos
EOD;
    $statement = $con->prepare($select_sql);
    $statement->execute(array(
      'p_questionid' => $questionid,
      'p_interviewid' => $p_interviewid,
      'p_pollquestionid' => $p_pollquestionid,
    ));
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $result = FieldValueFormat(json_encode_str($row['name']), 
        $row['responseid'], $row['interviewresponseid'], $result);
      $result['FieldValues'][count($result['FieldValues'])-1]['ResponseValue'] = $row['responsevalue'];
    }
  }
  
  //Вернём диапазоны для расчёта оценки
  $valuefield = 'Single' != $row['responsetypecode'] && 'Multi' != $row['responsetypecode'] 
    ? 'r1.'.iris_strtolower($row['responsetypecode']).'value' 
    : 'r1.id';
  $resultfield = $valuefield;
  if ('Date' == $row['responsetypecode']) {
    $resultfield = _db_date_to_string($valuefield);
  }
  else
  if ('Datetime' == $row['responsetypecode']) {
    $resultfield = _db_datetime_to_string($valuefield);
  }
  $select_sql = "select $resultfield as value, $valuefield as sortvalue, ".<<<EOD
r1.mark as responsevalue
from iris_response r1 
where r1.questionid = :p_questionid
order by sortvalue
EOD;
  $statement = $con->prepare($select_sql);
  $statement->execute(array(
    'p_questionid' => $questionid,
  ));
  $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $result_values = FieldValueFormat(json_encode_str($row['value']), json_encode_str($row['value']), null, $result_values);
    $result_values['FieldValues'][count($result_values['FieldValues'])-1]['ResponseValue'] = $row['responsevalue'];
  }
  $result['ResponseValues'] = $result_values['FieldValues'];

	return $result;
}


//Обновить
function Interview_Response_UpdateMultiResponse($p_id, $p_pollquestionid, $p_interviewid, $p_values)
{
	$result['success'] = '1';
	$con = GetConnection();

  $values = json_decode($p_values, true);
  
  list ($userid, $username) = GetShortUserInfo(GetUserName(), $con);

  foreach($values as $val) {
    //Если ответ уже был в базе и сейчас он обновляется
    if (!IsEmptyValue($val['interviewresponseid']) && $val['interviewresponseid'] != 'undefined') {
      $sql_upd = 'update iris_interview_response '.
        'set interviewid = :interviewid, '.
        'responseid = :responseid, '.
        'intvalue = :responsevalue, '.
        'pollquestionid = :pollquestionid, '.
        'questionid = (select questionid from iris_poll_question where id = :pollquestionid), '.
        'modifydate = now(), '.
        'modifyid = :userid, '.
        'mark = (select mark from iris_response where id = :responseid) * :responsevalue, '.
        'orderpos = (select pq1.orderpos from iris_poll_question pq1 where pq1.id = :pollquestionid), '.
        'valueforprint = (select stringvalue from iris_response where id = :responseid), '.
        'orderforprint = ('.
        '  select to_char(coalesce(pq1.orderpos::integer, 0), \'FM0000MI\')||\':\''.
        '  ||to_char(coalesce((select r1.orderpos::integer from iris_response r1 where r1.id = :responseid), 0), \'FM0000MI\')||\':\''.
        '  ||pq1.id::varchar' .
        '  from iris_poll_question pq1 '.
        '  where pq1.id = :pollquestionid '.
        ') '.
        'where id = :id';
      $statement = $con->prepare($sql_upd, array(PDO::ATTR_EMULATE_PREPARES => true));
      $statement->execute(array(
        'id' => $val['interviewresponseid'],
        'interviewid' => $p_interviewid,
        'responseid' => $val['responseid'],
        'responsevalue' => $val['responsevalue'],
        'pollquestionid' => $p_pollquestionid,
        'userid' => $userid,
      ));
      
      $result['sql'] = $sql_upd;
      $result['params'] = array(
        'id' => $val['interviewresponseid'],
        'interviewid' => $p_interviewid,
        'responseid' => $val['responseid'],
        'responsevalue' => $val['responsevalue'],
        'pollquestionid' => $p_pollquestionid,
        'userid' => $userid,
      );
    }
    //Если ответа ещё не было в базе
    else {
      $sql_ins = 'insert into iris_interview_response '.
        '(id, interviewid, responseid, intvalue, pollquestionid, questionid, '.
        'dateofanswer, createdate, modifydate, createid, modifyid, mark, orderpos, '.
        'valueforprint, orderforprint) '.
        'values (iris_genguid(), :interviewid, :responseid, :responsevalue, :pollquestionid, '.
        '(select questionid from iris_poll_question where id = :pollquestionid), '.
        'now(), now(), now(), :userid, :userid, '.
        '(select mark from iris_response where id = :responseid) * :responsevalue, '.
        '(select pq1.orderpos from iris_poll_question pq1 where pq1.id = :pollquestionid), '.
        '(select stringvalue from iris_response where id = :responseid),'.
        '('.
        '  select to_char(coalesce(pq1.orderpos::integer, 0), \'FM0000MI\')||\':\''.
        '  ||to_char(coalesce((select r1.orderpos::integer from iris_response r1 where r1.id = :responseid), 0), \'FM0000MI\')||\':\''.
        '  ||pq1.id::varchar' .
        '  from iris_poll_question pq1 '.
        '  where pq1.id = :pollquestionid '.
        ') )';
      $statement = $con->prepare($sql_ins, array(PDO::ATTR_EMULATE_PREPARES => true));
      $statement->execute(array(
        'interviewid' => $p_interviewid,
        'responseid' => $val['responseid'],
        'responsevalue' => $val['responsevalue'],
        'pollquestionid' => $p_pollquestionid,
        'userid' => $userid,
      ));

      $sql_del = 'delete from iris_interview_response '.
        'where id = :id';
      $statement = $con->prepare($sql_del);
      $statement->execute(array(
        'id' => $p_id,
      ));
    }
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
$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
//



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

    case 'GetInterviewParams':
      $response = Interview_Response_GetInterviewParams(
        stripslashes($_POST['_p_id'])
      );
      break;

    case 'FieldOnChange':
      $response = Interview_Response_FieldOnChange(
        stripslashes($_POST['_p_FieldName']),
        stripslashes($_POST['_p_FieldValue']),
        stripslashes($_POST['_p_id'])
      );
      break;

    case 'GetQuestionInfo':
      $response = Interview_Response_GetQuestionInfo(
        stripslashes($_POST['_p_pollquestionid']),
        stripslashes($_POST['_p_interviewid'])
      );
      break;

    case 'UpdateMultiResponse':
      $response = Interview_Response_UpdateMultiResponse(
        stripslashes($_POST['_p_id']), 
        stripslashes($_POST['_p_pollquestionid']), 
        stripslashes($_POST['_p_interviewid']), 
        stripslashes($_POST['_p_values'])
      );
      break;

    default:
      $response = 'Неверное имя функции: '.$func;
  }
}

echo json_encode($response);

?>