<?php
//********************************************************************
// Раздел E-mail закладка файлы
//********************************************************************

function AttachFile($p_file_id, $p_email_id) {
	$con = db_connect();
	
	// проверим, не прикреплен ли уже файл
	$cmd = $con->prepare("select id from iris_email_file where fileid=:fileid and emailid = :emailid");
	$cmd->execute(array(":fileid" => $p_file_id, ":emailid" => $p_email_id));
	$file_exists = current($cmd->fetchAll(PDO::FETCH_ASSOC));
	if ($file_exists['id'] != '')
		return array('errno' => 1, 'errm' => json_convert('Этот файл уже прикреплен'));
		
	// прикрепим файл
	$ins_cmd = $con->prepare("insert into iris_email_file (id, fileid, emailid) values (iris_genguid(), :fileid, :emailid)");
	$ins_cmd->execute(array(":fileid" => $p_file_id, ":emailid" => $p_email_id));
	if ($ins_cmd->errorCode() != '00000')
		return array('errno' => 2, 'errm' => json_convert('Не удалось прикрепить файл'));

	return array('errno' => 0, 'errm' => 'ok');	
}

function DeattachFile($p_file_id, $p_email_id) {
	$con = db_connect();

	// открепим файл
	$cmd = $con->prepare("delete from iris_email_file where fileid = :fileid and emailid = :emailid");
	$cmd->execute(array(":fileid" => $p_file_id, ":emailid" => $p_email_id));
	if ($cmd->errorCode() != '00000') {
		return array('errno' => 2, 'errm' => json_convert('Не удалось открепить файл'));
	}

	return array('errno' => 0, 'errm' => 'ok');	
}

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
		case 'AttachFile':
			$response = AttachFile($_POST['file_id'], $_POST['email_id']);
			break;
		case 'DeattachFile':
			$response = DeattachFile($_POST['file_id'], $_POST['email_id']);
			break; 
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}
echo json_encode($response);

?>
