<?php
//********************************************************************
// Раздел Рассылка, закладка файлы (таблица)
//********************************************************************

function AttachFile($p_file_id, $p_mailing_id) {
	$con = db_connect();
	
	// проверим, может ли данный пользователь править рассылку. если нет, то не дадим добавить файл
	GetCurrentUserRecordPermissions('iris_mailing', $p_mailing_id, $permissions, $con);
	if ($permissions['w'] == 0)
		return array('errno' => 3, 'errm' => json_convert('Для добавления файла пользователь должен иметь права записи на рассылку'));

	// проверим, не прикреплен ли уже файл
	$cmd = $con->prepare("select id from iris_mailing_file where fileid=:fileid and mailingid = :mailingid");
	$cmd->execute(array(":fileid" => $p_file_id, ":mailingid" => $p_mailing_id));
	$file_exists = current($cmd->fetchAll(PDO::FETCH_ASSOC));
	if ($file_exists['id'] != '')
		return array('errno' => 1, 'errm' => json_convert('Этот файл уже прикреплен'));
		
	// прикрепим файл
	$ins_cmd = $con->prepare("insert into iris_mailing_file (id, fileid, mailingid) values (iris_genguid(), :fileid, :mailingid)");
	$ins_cmd->execute(array(":fileid" => $p_file_id, ":mailingid" => $p_mailing_id));
	if ($ins_cmd->errorCode() != '00000')
		return array('errno' => 2, 'errm' => json_convert('Не удалось прикрепить файл'));

	return array('errno' => 0, 'errm' => 'ok');
}

function DeattachFile($p_file_id, $p_mailing_id) {
	$con = db_connect();

	// проверим, может ли данный пользователь править рассылку. если нет, то не дадим удалить файл
	GetCurrentUserRecordPermissions('iris_mailing', $p_mailing_id, $permissions, $con);
	if ($permissions['w'] == 0)
		return array('errno' => 3, 'errm' => json_convert('Для открепления файла пользователь должен иметь права записи на рассылку'));
	
	// открепим файл
	$cmd = $con->prepare("delete from iris_mailing_file where fileid = :fileid and mailingid = :mailingid");
	$cmd->execute(array(":fileid" => $p_file_id, ":mailingid" => $p_mailing_id));
	if ($cmd->errorCode() != '00000') {
		return array('errno' => 2, 'errm' => json_convert('Не удалось открепить файл'));
	}

	return array('errno' => 0, 'errm' => 'ok');	
}

// -----------------------------------------------------------------------

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
include_once $path.'/config/common/Lib/access.php';

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
			$response = AttachFile($_POST['file_id'], $_POST['mailing_id']);
			break;
		case 'DeattachFile':
			$response = DeattachFile($_POST['file_id'], $_POST['mailing_id']);
			break; 
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}
echo json_encode($response);

?>
