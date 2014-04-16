<?php
//********************************************************************
// Раздел "Интервью" (вкладка в разделе "Опросы"). Таблица.
//********************************************************************

function AddFromReport($p_reportid, $p_reportcode, $p_filters, $p_pollid) {
	if ($p_reportcode != '') {
		$con = db_connect();
		$cmd = $con->prepare("select id from iris_report where code=:code");
		$cmd->execute(array(":code" => $p_reportcode));
		$res = $cmd->fetchAll(PDO::FETCH_ASSOC);
		$p_reportid = $res[0]['id'];
	}
	
	$p_filters = json_decode(stripslashes($p_filters));

	//Подготовка отчета
	list($sql, $params, $show_info) = BuildReportSQL($p_reportid, $p_filters);
	$report_info = GetReportInfo($p_reportid);
	list($contacts, $tmp) = BuildReportData($show_info, $sql, $params);
	if (count($contacts) == 0) {
		return array("errno" => 2, "errm" => json_convert('Не найдено контактов, удовлетворяющих условиям поиска'));
  }

	
	$sql = '';
	$con = db_connect();

  list ($userid, $username) = GetShortUserInfo(GetUserName(), $con);

	$cmd = $con->prepare("select id from iris_interview where pollid = :pollid and contactid = :contactid");
	foreach($contacts as $contact) {
		$cmd->execute(array(
      ":pollid" => $p_pollid, 
      ":contactid" => $contact['id']
    ));
		$exists_id = current($cmd->fetchAll(PDO::FETCH_ASSOC));
    
		// если этого контакта еще нет в списке рассылки, то добавим его
		if ($exists_id == '') {
			$sql .= "insert into iris_interview (id, pollid, contactid, phone, phoneaddl, accountid, InterviewStateID, ownerid,".
        "createdate, modifydate, createid, modifyid) ".
        "values (iris_genguid(), '".$p_pollid."', '".$contact['id']."', ".
        "(select (case length(phone2)>5 when true then phone2 else phone1 end) from iris_contact where id='".$contact['id']."'), ".
        "(select (case length(phone2)>5 when true then '' else Phone1addl end) from iris_contact where id='".$contact['id']."'), ".
        "(select accountid from iris_contact where id='".$contact['id']."'), ".
        "(select id from iris_interviewstate where code='plan'), ".
        "'".$userid."', ".
        "now(), now(), '".$userid."', '".$userid."' ".
        "); ".chr(10);
      //TODO: назначать права при добавлении записей
		}
	}
	if ($sql == '') {
		return array("errno" => 3, "errm" => json_convert('Данные контакты уже добавлены в опрос'));
  }
	
	if ($con->exec($sql) == 0) {
		return array("errno" => 11, "errm" => json_convert('Не удалось добавить контактные лица в опрос'));
  }

	return array("errno" => 0, "errm" => '');
//	return array("errno" => 0, "errm" => json_convert($sql));
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
include_once $path.'/config/common/Lib/report.php';


SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

if (!empty($_GET['mode']) && $_GET['mode'] == 'preview') {
	$_POST['_func'] = 'preview';
}

$func = stripslashes($_POST['_func']);

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
    switch ($func) {
		case 'AddFromReport':
			$response = AddFromReport(
					!empty($_POST['_reportid']) ? $_POST['_reportid'] : null, 
					!empty($_POST['_reportcode']) ? $_POST['_reportcode'] : null,
					$_POST['_filters'], $_POST['_pollid']);
			break;
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}
echo json_encode($response);

?>
