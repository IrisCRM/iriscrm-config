<?php

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
//include_once $path.'/config/common/Lib/money.php';


//SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}


/////////////////////////

$LastCount = 10;

$RecordID = stripslashes($_GET['_p_id']);
$con = GetConnection();

//Получим значения из основной записи
$Values = GetFormatedFieldValuesByFieldValue('Account', 'ID', $RecordID, array(
	'Name', 
	'FullName', 
	'FirstContactDate',
	'Phone1',
	'Phone2',
	'Phone3',
	'Fax',
	'Email',
	'Web',
	'ZIP',
	'Address',
	'Description'
), $con);
//echo print_r($Values, true);

$Values = GetLinkedValues('Account', $RecordID, array(
	'AccountType',
	'AccountFace',
	'Marketing',
	'Space',
	'Category',
	'AccountState',
	'Industry',
	'Country',
	'City',
	'Region'
	//'Owner'
), $con, $Values);

$Values = GetLinkedValuesDetailed('iris_Account', $RecordID, array(
	array(
		'Field' => 'OwnerID',
		'GetTable' => 'iris_Contact',
		'GetField' => 'Name',
	) 
), $con, $Values);
	

$Values = $Values['FieldValues'];





//Сформируем отчет
$result = LoadTemplate('r_accounthistory.html');

$result = iris_str_replace('#charset#', GetDefaultEncoding(), $result);


//Карточка компании
$result = iris_str_replace('[Название]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Name', 'Value')), $result);
$result = iris_str_replace('[Полное название]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'FullName', 'Value')), $result);
$result = iris_str_replace('[Тип]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'AccountTypeID', 'Caption')), $result);
$result = iris_str_replace('[Лицо]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'AccountFaceID', 'Caption')), $result);
$result = iris_str_replace('[Категория]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'CategoryID', 'Caption')), $result);
$result = iris_str_replace('[Статус]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'AccountStateID', 'Caption')), $result);
$result = iris_str_replace('[Ответственный]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'OwnerID', 'Caption')), $result);
$result = iris_str_replace('[Отрасль]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'IndustryID', 'Caption')), $result);
$result = iris_str_replace('[Дата первого обращения]', GetArrayValueByParameter($Values, 'Name', 'FirstContactDate', 'Value'), $result);
$result = iris_str_replace('[Мероприятие]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'MarketingID', 'Caption')), $result);
$result = iris_str_replace('[Рекламная площадка]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'SpaceID', 'Caption')), $result);

$result = iris_str_replace('[Телефон 1]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Phone1', 'Value')), $result);
$result = iris_str_replace('[Телефон 2]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Phone2', 'Value')), $result);
$result = iris_str_replace('[Телефон 3]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Phone3', 'Value')), $result);
$result = iris_str_replace('[Факс]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Fax', 'Value')), $result);
$result = iris_str_replace('[E-mail]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Email', 'Value')), $result);
$result = iris_str_replace('[Сайт]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Web', 'Value')), $result);

$result = iris_str_replace('[Страна]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'CountryID', 'Caption')), $result);
$result = iris_str_replace('[Город]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'CityID', 'Caption')), $result);
$result = iris_str_replace('[Регион]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'RegionID', 'Caption')), $result);
$result = iris_str_replace('[Индекс]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'ZIP', 'Value')), $result);
$result = iris_str_replace('[Адрес]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Address', 'Value')), $result);
$result = iris_str_replace('[Описание]', json_decode_str(GetArrayValueByParameter($Values, 'Name', 'Description', 'Value')), $result);



//Контакты

$data_begin_pos = iris_strpos($result, '<!--contact_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--contact_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--contact_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--contact_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.name as name, t0.phone1 as phone1, t0.phone2 as phone2, t0.email as email, t1.name as ownername, t0.post as post, t2.name as postname ";
$select_sql .= "from iris_Contact t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_Post t2 on t2.ID=t0.PostID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.Name asc";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Контакты.ФИО]', $row['name'], $result_data_str);
	$result_data_str = iris_str_replace('[Контакты.Должность]', $row['post'].' ('.$row['postname'].')', $result_data_str);
	$result_data_str = iris_str_replace('[Контакты.Телефон]', $row['phone1'], $result_data_str);
	$result_data_str = iris_str_replace('[Контакты.Мобильный]', $row['phone2'], $result_data_str);
	$result_data_str = iris_str_replace('[Контакты.E-mail]', $row['email'], $result_data_str);
	$result_data_str = iris_str_replace('[Контакты.Ответственный]', $row['ownername'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;





//Почта

$data_begin_pos = iris_strpos($result, '<!--email_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--email_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--email_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--email_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.subject as subject, t0.messagedate as messagedate, t1.name as ownername, t2.name as contactname, t3.name as accountname ";
$select_sql .= "from iris_Email t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_Contact t2 on t2.ID=t0.ContactID ";
$select_sql .= "left join iris_Account t3 on t3.ID=t0.AccountID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.messagedate desc ";
$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Почта.Тема]', $row['subject'], $result_data_str);
	$result_data_str = iris_str_replace('[Почта.Контакт]', $row['contactname'], $result_data_str);
	$result_data_str = iris_str_replace('[Почта.Дата]', $row['messagedate'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Дела

$data_begin_pos = iris_strpos($result, '<!--task_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--task_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--task_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--task_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.name as name, t0.startdate as startdate, t0.finishdate as finishdate, t2.name as statename, t1.name as ownername, t3.name as resultname ";
$select_sql .= "from iris_Task t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_TaskState t2 on t2.ID=t0.TaskStateID ";
$select_sql .= "left join iris_TaskResult t3 on t3.ID=t0.TaskResultID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.startdate desc ";
$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Дела.Начало]', $row['startdate'], $result_data_str);
	$result_data_str = iris_str_replace('[Дела.Завершение]', $row['finishdate'], $result_data_str);
	$result_data_str = iris_str_replace('[Дела.Дело]', $row['name'], $result_data_str);
	$result_data_str = iris_str_replace('[Дела.Состояние]', $row['statename'], $result_data_str);
	$result_data_str = iris_str_replace('[Дела.Результат]', $row['resultname'], $result_data_str);
	$result_data_str = iris_str_replace('[Дела.Ответственный]', $row['ownername'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Заказы

$data_begin_pos = iris_strpos($result, '<!--project_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--project_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--project_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--project_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.number as number, t0.name as name, t0.startdate as startdate, t0.finishdate as finishdate, t2.name as statename, t1.name as ownername, t3.name as stagename ";
$select_sql .= "from iris_Project t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_ProjectState t2 on t2.ID=t0.ProjectStateID ";
$select_sql .= "left join iris_ProjectStage t3 on t3.ID=t0.ProjectStageID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.number desc ";
//$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Заказы.Номер]', $row['number'], $result_data_str);
	$result_data_str = iris_str_replace('[Заказы.Заказ]', $row['name'], $result_data_str);
	$result_data_str = iris_str_replace('[Заказы.Начало]', $row['startdate'], $result_data_str);
	$result_data_str = iris_str_replace('[Заказы.Завершение]', $row['finishdate'], $result_data_str);
	$result_data_str = iris_str_replace('[Заказы.Состояние]', $row['statename'], $result_data_str);
	$result_data_str = iris_str_replace('[Заказы.Стадия]', $row['stagename'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Счета

$data_begin_pos = iris_strpos($result, '<!--invoice_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--invoice_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--invoice_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--invoice_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.number as number, t0.planpaymentdate as planpaymentdate, t0.paymentdate as paymentdate, t2.name as statename, t1.name as ownername, t0.amount as amount, t0.paymentamount as paymentamount ";
$select_sql .= "from iris_invoice t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_InvoiceState t2 on t2.ID=t0.InvoiceStateID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.number desc ";
//$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Счета.Номер]', $row['number'], $result_data_str);
	$result_data_str = iris_str_replace('[Счета.Планируемая дата оплаты]', $row['planpaymentdate'], $result_data_str);
	$result_data_str = iris_str_replace('[Счета.Дата оплаты]', $row['paymentdate'], $result_data_str);
	$result_data_str = iris_str_replace('[Счета.Состояние]', $row['statename'], $result_data_str);
	$result_data_str = iris_str_replace('[Счета.Сумма счета (с НДС)]', $row['amount'], $result_data_str);
	$result_data_str = iris_str_replace('[Счета.Сумма оплаты]', $row['paymentamount'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Платежи

$data_begin_pos = iris_strpos($result, '<!--payment_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--payment_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--payment_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--payment_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.number as number, t0.name as name, t0.paymentdate as paymentdate, t0.amount as amount, t1.name as ownername, t2.name as statename, t3.name as typename, t4.number as projectnumber ";
$select_sql .= "from iris_Payment t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_PaymentState t2 on t2.ID=t0.PaymentStateID ";
$select_sql .= "left join iris_PaymentType t3 on t3.ID=t0.PaymentTypeID ";
$select_sql .= "left join iris_Project t4 on t4.ID=t0.ProjectID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.number desc ";
//$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Платежи.Краткое описание]', $row['name'], $result_data_str);
	$result_data_str = iris_str_replace('[Платежи.Заказ]', $row['projectnumber'], $result_data_str);
	$result_data_str = iris_str_replace('[Платежи.Дата платежа]', $row['paymentdate'], $result_data_str);
	$result_data_str = iris_str_replace('[Платежи.Тип]', $row['typename'], $result_data_str);
	$result_data_str = iris_str_replace('[Платежи.Состояние]', $row['statename'], $result_data_str);
	$result_data_str = iris_str_replace('[Платежи.Сумма]', $row['amount'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Объекты

$data_begin_pos = iris_strpos($result, '<!--object_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--object_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--object_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--object_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.name as name, t0.phone1 as phone1, t1.name as ownername, t2.name as statename, t3.name as typename ";
$select_sql .= "from iris_Object t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_ObjectState t2 on t2.ID=t0.ObjectStateID ";
$select_sql .= "left join iris_ObjectType t3 on t3.ID=t0.ObjectTypeID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.name asc ";
//$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Объекты.Название]', $row['name'], $result_data_str);
	$result_data_str = iris_str_replace('[Объекты.Телефон]', $row['phone1'], $result_data_str);
	$result_data_str = iris_str_replace('[Объекты.Тип]', $row['typename'], $result_data_str);
	$result_data_str = iris_str_replace('[Объекты.Состояние]', $row['statename'], $result_data_str);
	$result_data_str = iris_str_replace('[Объекты.Ответственный]', $row['ownername'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Инциденты

$data_begin_pos = iris_strpos($result, '<!--incident_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--incident_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--incident_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--incident_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.number as number, t0.name as name, t1.name as ownername, t2.name as statename, t3.name as typename ";
$select_sql .= "from iris_Incident t0 ";
$select_sql .= "left join iris_Contact t1 on t1.ID=t0.OwnerID ";
$select_sql .= "left join iris_IncidentState t2 on t2.ID=t0.IncidentStateID ";
$select_sql .= "left join iris_IncidentType t3 on t3.ID=t0.IncidentTypeID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.number desc ";
$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Инциденты.Номер]', $row['number'], $result_data_str);
	$result_data_str = iris_str_replace('[Инциденты.Краткое описание]', $row['name'], $result_data_str);
	$result_data_str = iris_str_replace('[Инциденты.Тип]', $row['typename'], $result_data_str);
	$result_data_str = iris_str_replace('[Инциденты.Ответственный]', $row['ownername'], $result_data_str);
	$result_data_str = iris_str_replace('[Инциденты.Состояние]', $row['statename'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Связи

$data_begin_pos = iris_strpos($result, '<!--account_link_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--account_link_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--account_link_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--account_link_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.description as description, t1.name as accountname, t2.name as rolename ";
$select_sql .= "from iris_Account_Link t0 ";
$select_sql .= "left join iris_Account t1 on t1.ID=t0.AccountLinkID ";
$select_sql .= "left join iris_AccountLinkRole t2 on t2.ID=t0.AccountLinkRoleID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t1.name asc ";
//$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Связи.Компания]', $row['accountname'], $result_data_str);
	$result_data_str = iris_str_replace('[Связи.Кем является]', $row['rolename'], $result_data_str);
	$result_data_str = iris_str_replace('[Связи.Описание]', $row['description'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Важные даты

$data_begin_pos = iris_strpos($result, '<!--account_date_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--account_date_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--account_date_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--account_date_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.name as name, t0.date as date, t0.description as description, t1.name as typename ";
$select_sql .= "from iris_Account_Date t0 ";
$select_sql .= "left join iris_AccountDateType t1 on t1.ID=t0.AccountDateTypeID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.date asc ";
//$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Важные даты.Тип]', $row['typename'], $result_data_str);
	$result_data_str = iris_str_replace('[Важные даты.Название]', $row['name'], $result_data_str);
	$result_data_str = iris_str_replace('[Важные даты.Описание]', $row['description'], $result_data_str);
	$result_data_str = iris_str_replace('[Важные даты.Дата]', $row['date'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;



//Файлы

$data_begin_pos = iris_strpos($result, '<!--file_data_begin-->');
$data_end_pos = iris_strpos($result, '<!--file_data_end-->');
$result_beg = iris_substr($result, 0, $data_begin_pos);
$result_end = iris_substr($result, $data_end_pos);
$result_data = iris_substr($result, $data_begin_pos + strlen('<!--file_data_begin-->'), 
	$data_end_pos - $data_begin_pos - strlen('<!--file_data_end-->') - 2);
$result = $result_beg;

$select_sql = "select t0.date as date, t0.file_filename as filename, t0.version as version, t0.description as description, t1.name as typename, t2.name as statename ";
$select_sql .= "from iris_File t0 ";
$select_sql .= "left join iris_FileType t1 on t1.ID=t0.FileTypeID ";
$select_sql .= "left join iris_FileState t2 on t2.ID=t0.FileStateID ";
$select_sql .= "where t0.AccountID=:p_id ";
$select_sql .= "order by t0.date desc ";
//$select_sql .= "limit ".$LastCount." ";
$statement = $con->prepare($select_sql);
$statement->bindParam(':p_id', $RecordID);
$statement->execute();
$res = $statement->fetchAll();

foreach ($res as $row) {
	$result_data_str = $result_data;
	$result_data_str = iris_str_replace('[Файлы.Файл]', $row['filename'], $result_data_str);
	$result_data_str = iris_str_replace('[Файлы.Тип]', $row['typename'], $result_data_str);
	$result_data_str = iris_str_replace('[Файлы.Состояние]', $row['statename'], $result_data_str);
	$result_data_str = iris_str_replace('[Файлы.Версия]', $row['version'], $result_data_str);
	$result_data_str = iris_str_replace('[Файлы.Описание]', $row['description'], $result_data_str);
	$result_data_str = iris_str_replace('[Файлы.Дата]', $row['date'], $result_data_str);
	$result .= $result_data_str;
}

$result .= $result_end;


echo $result;

?>