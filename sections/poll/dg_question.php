<?php
//********************************************************************
// Раздел "Опросы". Вкладка "Вопросы". Таблица.
//********************************************************************


//Перенумерация номеров продуктов в закладке
function Poll_Question_Renumber($p_poll_id, $p_orderpos)
{
	$con = GetConnection();
  
  $select_sql = <<<EOD
update iris_Poll_Question set orderpos = (orderpos::integer - 1)::varchar
where PollID = :pollid 
and orderpos::integer > :pos
EOD;
	$statement = $con->prepare($select_sql);
	$statement->execute(array(
    ':pollid' => $p_poll_id,
    ':pos' => $p_orderpos,
  ));
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

		case 'Renumber':
			$response = Poll_Question_Renumber(stripslashes($_POST['_p_id']), stripslashes($_POST['_p_orderpos']));
			break;

		default:
			$response = 'Неверное имя функции: '.$func;
	}
}

echo json_encode($response);

?>