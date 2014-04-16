<?php
//********************************************************************
// Карточка Работы
//********************************************************************

function Work_getParentInfo($p_parent_id, $p_project_id) {
    $con = db_connect();

    if (($p_parent_id == '') and ($p_project_id != '')) {
        $cmd = $con->prepare("select count(id) as workcount from iris_work where projectid = :projectid and parentworkid is null");
        $cmd->execute(array(":projectid" => $p_project_id));
        $count = current($cmd->fetchAll(PDO::FETCH_ASSOC));
        return array("workcount" => $count['workcount']);
    }

    $cmd = $con->prepare("select id, number from iris_work where id = :id");
    $cmd->execute(array(":id" => $p_parent_id));
    $work = current($cmd->fetchAll(PDO::FETCH_ASSOC));

    $cmd = $con->prepare("select count(id) as workcount from iris_work where parentworkid = :id");
    $cmd->execute(array(":id" => $p_parent_id));
    $count = current($cmd->fetchAll(PDO::FETCH_ASSOC));
    $work['workcount'] = $count['workcount'];

    return $work;
}

///////////////////////////////////////////////////////

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}
}
session_write_close();

// не закрывать сессию так как тут пишутся параметры для смены ответсвенного
$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';

include_once $path.'/config/common/Lib/chown.php';


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

		case 'getParentInfo':
			$response = Work_getParentInfo($_POST['parent_id'], $_POST['project_id']);
			break;

//		default:
//			$response = 'Неверное имя функции: '.$func;
	}
}

if ((is_array($response) == true) and (count($response) > 0)) {
	echo json_encode($response);
}

?>