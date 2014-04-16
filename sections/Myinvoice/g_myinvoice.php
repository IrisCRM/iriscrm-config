<?php
//********************************************************************
// Раздел "Мои счета". Таблица
//********************************************************************

//ini_set('display_errors', 'on');

function CheckBalanceNeened($p_invoice_id) {
	$con = db_connect();

	//$cmd = $con->prepare("select id, number, Amount as amount, contactid, ownerid, projectid from iris_invoice where id=:id");
	$cmd = $con->prepare("select T0.id as id, T0.number as number, T0.Amount as amount, T0.contactid as contactid, T0.ownerid as ownerid, T0.projectid as projectid, T1.code as code from iris_invoice T0 left join iris_invoicestate T1 on T0.invoicestateid = T1.id where T0.id=:id");
	$cmd->execute(array(":id" => $p_invoice_id));
	$inv_res = $cmd->fetchAll(PDO::FETCH_ASSOC);

	if ($inv_res[0]['code'] == 'Payed') {
		$result['errm'] = json_convert('Данный счет уже оплачен');
		return $result;
	}
	$user_id = GetUserID($con);
	if ($inv_res[0]['contactid'] != $user_id) {
		$result['errm'] = json_convert('Данный счет может оплатить только тот клиент, который указан в счете');
		return $result;
	}

	$contact_res = $con->query("select balance from iris_contact where id='".$user_id."'")->fetchAll();
	$balance = $contact_res[0][0];
	if ($balance < $inv_res[0]['amount'])
		$result['isok'] = 0;
	else
		$result['isok'] = 1;
	
	$result['amount'] = $inv_res[0]['amount'];
	$result['balance'] = $balance;
	$result['invoiceid'] = $inv_res[0]['id'];

	// требуются при оплате счета
	$result['number'] = $inv_res[0]['number'];
	$result['contactid'] = $inv_res[0]['contactid'];
	$result['ownerid'] = $inv_res[0]['ownerid'];
	$result['projectid'] = $inv_res[0]['projectid'];
	
	return $result;
}


function PayInvoice($p_invoice_id) {
//ini_set('display_errors', 'on');
	$check = CheckBalanceNeened($p_invoice_id);
	if ($check['errm'] != '')
		return array('message' => $check['errm']);
		
	if ($check['isok'] == 0) 
		return array('message' => json_convert('Средств баланса не достаточно для оплаты счета'));

	$con = db_connect();

///$res_debug = current($con->query("select id, number, name, ProjectStageID from iris_project where id=(select projectid from iris_invoice where id='".$p_invoice_id."')")->fetchAll(PDO::FETCH_ASSOC));
	
	// уменьшим баланс клиента на сумму счета
	$cmd = $con->prepare("update iris_contact set balance = balance - :amount where id=:id");
	$cmd->execute(array(":id" => GetUserID($con), ":amount" => $check['amount']));
	if ($cmd->errorCode() != '00000') 
		return array('message' => json_convert('Не удалось изменить баланс'));

	// создадим платеж
	$payment_id = create_guid();
	$cmd = $con->prepare("insert into iris_payment (id, Number, Name, PaymentTypeID, PaymentStateID, ContactID, OwnerID, PaymentDate, CurrencyID, Amount, InvoiceID, ProjectID, iscash) 
	values (:id, :Number, :Name, (select id from iris_PaymentType where code='In'), (select id from iris_PaymentState where code='Completed'), :ContactID, :OwnerID, now(), (select id from iris_Currency where code='RUB'), :Amount, :InvoiceID, :ProjectID, 1)");
	$cmd->bindParam(":id", $payment_id);
	$number = GenerateNewNumber('PaymentNumber', 'PaymentNumberDate', $con);		
	$cmd->bindParam(":Number", $number);
	$name = $number.' - оплата счета '.$check['number'];
	$cmd->bindParam(":Name", $name);
	$cmd->bindParam(":ContactID", $check['contactid']);
	$cmd->bindParam(":OwnerID", $check['ownerid']);
	$cmd->bindParam(":Amount", $check['amount']);
	$cmd->bindParam(":InvoiceID", $p_invoice_id);
	$cmd->bindParam(":ProjectID", $check['projectid']);
	$cmd->execute();
	if ($cmd->errorCode() == '00000') {
		UpdateNumber('Payment', stripslashes($p_invoice_id), 'PaymentNumber', 'PaymentNumberDate');
	}
	else {
		return array('message' => json_convert('Внимание! Баланс изменен, но платеж не был создан. Обратитесь к своему менеджеру'));
	}
	
///$res_debug = current($con->query("select id, number, name, ProjectStageID from iris_project where id=(select projectid from iris_invoice where id='".$p_invoice_id."')")->fetchAll(PDO::FETCH_ASSOC));
///return array('message' => json_convert('test message'));	


	// добавим права на созданый платеж
	$role_res = array_pop($con->query("select id from iris_accessrole where code='leader'")->fetchAll(PDO::FETCH_ASSOC));
	$permissions[] = array('userid' => $check['contactid'], 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
	$permissions[] = array('userid' => $check['ownerid'], 'roleid' => '', 'r' => 1, 'w' => 0, 'd' => 0, 'a' => 0);
	$permissions[] = array('userid' => '', 'roleid' => $role_res['id'], 'r' => 1, 'w' => 1, 'd' => 1, 'a' => 1);
	$res = ChangeRecordPermissions('iris_payment', $payment_id, $permissions);
	
	// переводем счет в состояние оплачен
//	$cmd = $con->prepare("update iris_invoice set InvoiceStateID=(select id from iris_InvoiceState where code='Payed') where id=:id");
//	$cmd->execute(array(":id" => $p_invoice_id));
//	if ($cmd->errorCode() != '00000') 
//		return array('message' => json_convert('Не удалось изменить состояние счета'));

///$res_debug = current($con->query("select id, number, name, ProjectStageID from iris_project where id=(select projectid from iris_invoice where id='".$p_invoice_id."')")->fetchAll(PDO::FETCH_ASSOC));
		
	//$notify_pay_res = @SendPayedProjectNotify($p_invoice_id);
	//$notify_state_res = @SendStateProjectNotyfy($p_invoice_id);

///$res_debug = current($con->query("select id, number, name, ProjectStageID from iris_project where id=(select projectid from iris_invoice where id='".$p_invoice_id."')")->fetchAll(PDO::FETCH_ASSOC));

	//return array('message' => json_convert('Счет был успешно оплачен'));
	return array('message' => json_convert('Счет был успешно оплачен'), 'notify_pay' => $notify_pay_res, 'notify_res' => $notify_state_res);
}



// При полной оплате заказа, к которому относится счет $p_invoice_id отсылает исполнителю уведомление
function SendPayedProjectNotify($p_invoice_id) {
	$con = db_connect();
	
	// получим информацию по проекту
	$cmd = $con->prepare("select T0.projectid as projectid, T1.Paid as amount from iris_invoice T0 left join iris_project T1 on T0.projectid = T1.id where T0.id=:id");
	$cmd->execute(array(":id" => $p_invoice_id));
	$project_info = current($cmd->fetchAll(PDO::FETCH_ASSOC));
	$project_id	= $cmd_res[0]['projectid'];

	// получим сумму оплаченых счетов по проекту, чтобы определить оплачен ли проект или нет
	$payment = current($con->query("select sum(T0.amount) as amount from iris_payment T0 left join iris_paymentstate T1 on T0.paymentstateid = T1.id 
	where T0.projectid='c50368e2-d3c6-408f-9ad7-3bb0c39b1156' 
	and T1.code ='Completed'")->fetchAll(PDO::FETCH_ASSOC));
	
	// если проект не оплачен полностью, то выйдем
	if ($payment['amount'] < $project_info['amount']) 
		return 'not payed yet';

	// из проекта получаем email ответственного и его флаг isnotify
	$sel_qry = $con->prepare("select T1.email as email, T1.isnotify as isnotify, T0.number as project_number from iris_project T0 left join iris_contact T1 on T0.ownerid = T1.id where T0.id=:id");
	$sel_qry->execute(array(":id" => $project_info['projectid']));
	$recipient = current($sel_qry->fetchAll(PDO::FETCH_ASSOC));
	if ($recipient['isnotify'] != 1)
		return 'notify disabled'; // если у ответственного уведомления отключены, то выйдем

	if ($recipient['email'] == '')
		return 'recipient is not have a email'; // если у получателя не указан email
	
//return array('result' => 'under construction');
	
	$printform = current($con->query("select id from iris_printform where code='project-payment-notify'")->fetchAll(PDO::FETCH_ASSOC));
	// в message - заполненая печатная форма
	require_once GetPath().'/core/engine/printform.php';
	$message = FillForm($printform['id'], $project_info['projectid']);
	
	// заменим в форме поля [cabinet]
	$admin_info = current($con->query("select email from iris_contact T0 left join iris_accessrole T1 on T0.accessroleid=T1.id where T1.code in ('admin') order by email")->fetchAll());
	if ($admin_info['email'] != '')
		$admin_info['email'] = ' ('.$admin_info['email'].')';
	$message = iris_str_replace('[admin_email]', $admin_info['email'], $message);

	// заменим в форме поле [admin_email]
	$optxml = simplexml_load_file(realpath('./../../../site/options.xml'));
	$message = iris_str_replace('[cabinet]', '<a href="'.(string)$optxml->INDEX_LINK.'/enter.php">личный кабинет</a>', $message);	

	$subject = 'Заказ '.$recipient['project_number'].' полностью оплачен';
	
	require_once GetPath().'/config/common/Lib/mailfunc.php';
	$email_params = get_mail_settings(realpath('./../../../site/options.xml'));

	$res = SendEmail($email_params, $recipient['email'], $subject, $message);
	if ($res != 0)
		return 'mail error';
	
	return 'ok';
}

// при переходе заказа в состояние "выполнение" (при полной оплате заказа) отправить клиенту уведомление об этом
function SendStateProjectNotyfy($p_invoice_id) {
	$con = db_connect();
	$cmd = $con->prepare("select projectid from iris_invoice where id=:id");
	$cmd->execute(array(":id" => $p_invoice_id));
	$res = $cmd->fetchAll(PDO::FETCH_ASSOC);

	require GetPath().'/config/common/Lib/projectstagenotify.php';
	return SendProjectStageNotify($res[0]['projectid']);
}



if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}

$path = $_SESSION['INDEX_PATH'];

//include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';
include_once $path.'/config/common/Lib/lib.php';
include_once $path.'/config/common/Lib/access.php';


SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

//$_POST['_func'] = $_GET['_func'];
$func = stripslashes($_POST['_func']);

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
    switch ($func) {
		case 'CheckBalanceNeened':
			$response = CheckBalanceNeened($_POST['invoice_id']);
			break;

		case 'PayInvoice':
			$response = PayInvoice($_POST['invoice_id']);
			break;

		default:
			$response = 'Неверное имя функции: '.$func;
	}
}

echo json_encode($response);
?>
