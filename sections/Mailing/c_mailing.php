<?php
//********************************************************************
// Раздел Рассылка, карточка
//********************************************************************

function m_get_sec() {
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    return $mtime;
}

function SendMailing($p_mailing_id) {
	$con = db_connect();
	$start_time = m_get_sec();

	// если нет доступа на праву рассылки - то ругаемся
	GetCurrentUserRecordPermissions('iris_mailing', $p_mailing_id, $user_permissions, $con);
	if ($user_permissions['w'] == 0)
		return array('errno' => 1, 'errm' => json_convert('Чтобы отправить рассылку, Вы должны иметь доступ на ее изменение'));
	
	// если есть несозданные письма - то ругаемся
	$cmd = $con->prepare("select count(id) as cnt from iris_mailing_contact where mailingid=:mailingid and emailid is null");
	$cmd->execute(array(":mailingid" => $p_mailing_id));
	$res = $cmd->fetchAll(PDO::FETCH_ASSOC);
	if ($res[0]['cnt'] != 0)
		return array('errno' => 1, 'errm' => json_convert('У некоторых получателей отсутсвуют письма. Их нужно сгенерировать Во вкладке "получатели"'));

	// если нет ни одного получателя письма - то ругаемся
	$cmd = $con->prepare("select count(id) as cnt from iris_mailing_contact where mailingid=:mailingid");
	$cmd->execute(array(":mailingid" => $p_mailing_id));
	$res = $cmd->fetchAll(PDO::FETCH_ASSOC);
	if ($res[0]['cnt'] == 0)
		return array('errno' => 1, 'errm' => json_convert('Нужно добавить хотя бы одного получателя, чтобы отправить рассылку'));


	// получение количества уже отправленных писем и общего количества писем
	$maliing_status = GetMailingStatus($p_mailing_id);
	$sended_cnt = $maliing_status['sended'];
	$all_cnt = $maliing_status['all'];
	
	// получение списка писем для отправки (которые еще не отправлены)
	$sql  = "select T0.id as mc_id, T0.emailid as emailid from iris_mailing_contact T0 ";
	$sql .= "left join iris_email T1 on T0.emailid = T1.id ";
	$sql .= "left join iris_emailtype T2 on T1.emailtypeid = T2.id ";
	$sql .= "where mailingid=:mailingid ";
	$sql .= "and T2.code = 'Mailing_outbox'";
	$cmd = $con->prepare($sql);
	$cmd->execute(array(":mailingid" => $p_mailing_id));
	$emails = $cmd->fetchAll(PDO::FETCH_ASSOC);

	if (count($emails) > 0) {
		// установим дату начала рассылки
		$cmd = $con->prepare("update iris_mailing set startdate=now() where id=:id and startdate is null");
		$cmd->execute(array(":id" => $p_mailing_id));			
	}
	
	// отправка письмем в цикле и подсчет их количества
	$count = 0;
	$smtp_params = get_smtp_params($emails[0]['emailid']);
	$phpmailer = create_phpmailer($smtp_params);
	$phpmailer->ClearAddresses();
	foreach($emails as $email) {
		$res = send_email_message($email['emailid'], 'Mailing_outbox', $phpmailer, $smtp_params['emailaccountid']);
		$res = json_decode(UtfEncode($res), true);
		if ($res['status'] != '+')
			return array('errno' => 2, 'errm' => json_convert('Не удалось отправить письмо рассылки<br>'.UtfDecode($res['message']).'<br>Попробуйте повторить операцию еще раз<br>Рассыла прервана'));
		
		// установка времени отправки в iris_mailing_contact для отправленного письма
		$cmd = $con->prepare("update iris_mailing_contact set senddate=now() where id=:id");
		$cmd->execute(array(":id" => $email['mc_id']));
		
		$count++; // подсчет количества отправленных за запрос писем

		// смотрим время выполнения, заканчиваем если осталось 10 сек
		$exec_time = m_get_sec() - $start_time;
		if ($exec_time + 10 > ini_get('max_execution_time')) {
			break;
		}
	}
	
	
	// если отправили все письма, то 
	if ($sended_cnt+$count >= $all_cnt) {
		// сделаем рассылку доступной только для чтения
		GetRecordPermissions('iris_mailing', $p_mailing_id, $permissions, $con);
		foreach ($permissions as $key => $val)
			$permissions[$key]['w'] = 0;
		ChangeRecordPermissions('iris_mailing', $p_mailing_id, $permissions, $con);
		
		// установим дату окончания рассылки (и признак отправлена)
		$cmd = $con->prepare("update iris_mailing set enddate=now() where id=:id");
		$cmd->execute(array(":id" => $p_mailing_id));			
	}

	return array('errno' => 0, 'errm' => 'ok', 'sended' => $sended_cnt+$count, 'all' => $all_cnt);
}

function GetMailingStatus($p_mailing_id) {

	// получение количества уже отправленных писем и общего количества писем
	$con = db_connect();
	$sql  = "select count(T0.id) as cnt, T2.code as code from iris_mailing_contact T0 ";
	$sql .= "left join iris_email T1 on T0.emailid = T1.id ";
	$sql .= "left join iris_emailtype T2 on T1.emailtypeid = T2.id ";
	$sql .= "where mailingid=:mailingid ";
	$sql .= "group by T2.code ";
	$sql .= "order by T2.code desc";
	$cmd = $con->prepare($sql);
	$cmd->execute(array(":mailingid" => $p_mailing_id));
	$count = $cmd->fetchAll(PDO::FETCH_ASSOC);
	$sended_cnt = ($count[0]['code'] == 'Mailing_outbox' ? 0 : (int)$count[0]['cnt']);
	$all_cnt = (int)$count[0]['cnt'] + (!empty($count[1]['cnt']) ? (int)$count[1]['cnt'] : 0);
	
	return array('sended' => $sended_cnt, 'all' => $all_cnt);
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

session_write_close();

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/config/common/Lib/access.php';
include_once $path.'/config/sections/Email/lib/common.php';
include_once $path.'/config/sections/Email/lib/send_lib.php';

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
		case 'SendMailing':
			$response = SendMailing($_POST['mailing_id']);
			break;

		case 'GetMailingStatus':
			$response = GetMailingStatus($_POST['mailing_id']);
			break;
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}
echo json_encode($response);

?>
