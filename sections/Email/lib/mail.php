<?php
ini_set('display_errors', 'on');

//ini_set('log_errors', 'on');
//ini_set('error_log', '/admin/logs/error.log');

/*
if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}			
}
// miv 20.05.2009: Заканчивам текущую сессию и сохраняем данные сессии
// Поскольку данные сессии блокируются для предотвращения конкурирующей записи, только один скрипт может работать с сессией в данный момент времени
// Данные сессии нам нужны тоьлко для чтения, поэтому сразу зарываем сессию
session_write_close();

$path = $_SESSION['INDEX_PATH'];
*/
$path = realpath(dirname(__FILE__)."/./../../../../");

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/sections/Email/lib/common.php';
include_once $path.'/config/common/Lib/lib.php';

session_write_close();

SendRequestHeaders();

function removeBOM($str=""){
    if(substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
        $str=substr($str, 3);
    }
	// miv 23.03.2011: в письмах outlook есть "вредные символы" в стилях из-за которых письмо не переводится в нужную кодировку в iconv
	$str = str_replace(array(pack("CCC",0xef,0x82,0xb7), pack("CCC",0xef,0x82,0xa7)), array('', ''), $str);
    return $str;
}

function save_email($p_con, $p_text, $p_ownerID, $p_emailaccountID) {
ini_set('display_errors', 'on');
	$mime=new mime_parser_class;
	
	// Set to 0 for parsing a single message file
	// Set to 1 for parsing multiple messages in a single file in the mbox format
	$mime->mbox = 1;
	
	// Set to 0 for not decoding the message bodies
	$mime->decode_bodies = 1;

	// Set to 0 to make syntax errors make the decoding fail
	$mime->ignore_syntax_errors = 1;

	$parameters=array(
		///'File'=>'message.eml',
		'Data'=>$p_text,
		
		// Do not retrieve or save message body parts     
		'SkipBody'=>0,
	);
	fb_debug('----begin parsing 1...');
	if(!$mime->Decode($parameters, $decoded)) {
		fb_debug('MIME message decoding error: '.$mime->error.' at position '.$mime->error_position);
		//fb_debug($p_text);
		return;
	}
	fb_debug('----begin parsing 2...');	
	for($message = 0; $message < count($decoded); $message++) {
		if($mime->Analyze($decoded[$message], $results) == false) {
			fb_debug('MIME message analyse error: '.$mime->error);
			continue;
		}
		// от кого
		$email_from = iconv($results['From'][0]['Encoding'], GetDefaultEncoding(), $results['From'][0]['address']);
		// кому
		$email_to = '';
		if (is_array($results['To']) == true)
			foreach ($results['To'] as $res_to)
				$email_to .= iconv($res_to['Encoding'], GetDefaultEncoding(), $res_to['address']).' ';
		$email_to = trim($email_to); // miv 14:52 01.11.2010: убираем пробел в конце

		// тема
		fb_debug($results['Subject'], 's1');
		fb_debug($results['Subject'][0], 's1_1');
		fb_debug($results['Subject'][1], 's1_2');
		if (is_array($results['Subject']) == true) {
			$subj = $results['Subject'][0];
		} else
			$subj = $results['Subject'];
		fb_debug($subj, 's2');
		fb_debug($results['SubjectEncoding'], 's21-encoding');

		//orig: $email_subject = iconv($results['SubjectEncoding'], GetDefaultEncoding(), $results['Subject']);
		if (($results['SubjectEncoding'] == '') or ($results['SubjectEncoding'] == 'NULL'))
			$email_subject = DecodeAttachmentName($subj);
		else
			$email_subject = iconv($results['SubjectEncoding'], GetDefaultEncoding(), $subj);
		fb_debug($email_subject, 's3');
/*
		$email_subject = iconv($results['SubjectEncoding'], GetDefaultEncoding(), $results['Subject']);
		fb_debug($email_subject);
		$email_subject = DecodeAttachmentName($email_subject); // для корявых тем
		fb_debug($email_subject);
*/

		// тело ообщения
		//$email_body = iconv($results['Encoding'], GetDefaultEncoding(), $results['Data']);
		$email_body = iconv($results['Encoding'], GetDefaultEncoding(), removeBOM($results['Data'])); // miv 09.09.2010: для корректной работы utf8 убираем BOM
		
		if ($results['Type'] == 'text')
			$email_body = '<font face="Tahoma" size="-1">'.iris_str_replace(chr(10), '<br>', $email_body).'</font>';
				
		// вставляем письмо
		$EmailID = create_guid();
		$CONN = $p_con; //db_connect();
		
		// тип письма - входящее
//fb_debug('1');
		$et_res = $CONN->query("select id from iris_emailtype where code='Inbox'")->fetchAll();
//fb_debug('2');
		// компания, контакт и ответсвенный
		$accountID = null;
		$contactID = null;
		$ownerID = null;
		$contact_sql = PerformMacroSubstitution("select id, accountid, ownerid from iris_contact where _iris_lower[email]='".strtolower($results['From'][0]['address'])."' or id in (select contactid from iris_contact_email where email='".strtolower($results['From'][0]['address'])."')");
		$contact_res = $CONN->query($contact_sql)->fetchAll();
		if ($contact_res[0][0] != '') {
			$contactID = $contact_res[0][0];
			$accountID = $contact_res[0][1];
			$ownerID = $contact_res[0][2];
		} else {
			$account_sql = PerformMacroSubstitution("select id, ownerid, primarycontactid from iris_account where _iris_lower[email]='".strtolower($results['From'][0]['address'])."' or id in (select accountid from iris_account_email where email='".strtolower($results['From'][0]['address'])."')");
			$account_res = $CONN->query($account_sql)->fetchAll();
			if ($account_res[0][0] != '') {
				$contactID = $account_res[0][2]; // miv 12.12.2011: основной контакт компании
				$accountID = $account_res[0][0];
				$ownerID = $account_res[0][1];
			}
		}
		$p_ownerID = $ownerID; // miv 21.10.2010: ответсвенный теперь берется от контакта или компании, если он не указан
		
		// miv 01.11.2010: если письмо пришло на ящик саппорта, то автоматически создадим для него инцидент
		$support_emails = get_support_emails();
		if (in_array($email_to, $support_emails) == true) {
			$Number = GenerateNewNumber('IncidentNumber', null, $con); // номер будущего инцидента
			$incident_id = create_guid(); // id будущего инцидента			
			// сформируем текстовое содержимое письма, которое не превышает 1000 символов
			$short_body = $email_body;
			$short_body = iris_str_replace(chr(13).chr(10), '', $short_body);
			$short_body = iris_str_replace(chr(10).chr(13), '', $short_body);
			$short_body = iris_str_replace('<br>', chr(10), $short_body);
			$short_body = iris_str_replace('<BR>', chr(10), $short_body);
			$short_body = strip_tags($short_body);
			if (iris_strlen($short_body) >= 1000)
				$short_body = iris_substr($short_body, 0, 1000);
			
			// вставка инцидента
			$ins_inc_cmd = $CONN->prepare("insert into iris_incident (id, number, name, description, accountid, contactid, ownerid, date, incidentstateid, isremind, reminddate) values (:id, :number, :name, :description, :accountid, :contactid, :ownerid, now(), (select id from iris_incidentstate where code='Plan'), 1, now())");
			$ins_inc_cmd->execute(array(":id" => $incident_id, ":number" => $Number, ":name" => $email_subject, ":description" => $short_body, ":accountid" => $accountID, ":contactid" => $contactID, ":ownerid" => $p_ownerID));
			if ($ins_inc_cmd->errorCode() == '00000') {
				// изменим тему письма, вставив в ее начало номер инцидента. тогда письмо при сохранении автоматически будет привязано к инциденту
				$email_subject = '['.$Number.'] '.$email_subject;
				// увеличим номер инцидента
				UpdateNumber('Incident', $incident_id, 'IncidentNumber');
				// вставка прав на инцидент
				$acc_ins_sql  = "insert into iris_incident_access (id, recordid, contactid, r, w, d, a) ";
				$acc_ins_sql .= "select iris_genguid(), :incidentid, contactid, r, w, d, a from iris_emailaccount_defaultaccess where emailaccountid=:emailaccountid";
				$acc_ins_cmd = $CONN->prepare($acc_ins_sql);
				$acc_ins_cmd->execute(array(":incidentid" => $incident_id, ":emailaccountid" => $p_emailaccountID));
			}
		}
		
		// miv 02.08.2010: привязка письма к инциденту
		if (iris_preg_match("/\\[\\d{6}-\\d+\\]/", $email_subject, $matches, PREG_OFFSET_CAPTURE)) {
			fb_debug($matches[0][0], 'Incident fetched:');
			$incident_number = trim($matches[0][0], "\x5B..\x5D"); // обрезаем скобки [ и ]
			$cmd = $CONN->prepare("select id as id from iris_incident where number = :number");
			$cmd->execute(array(":number" => $incident_number));
			$incident = $cmd->fetchAll(PDO::FETCH_ASSOC);
			fb_debug($incident[0]['id'], 'Incident id:');
		} else {
			$incident[0]['id'] = null;
		}

		//$insert_sql = "insert into iris_email(id, e_from, e_to, subject, body, emailtypeid, accountid, contactid, ownerid, messagedate) values (:id, :e_from, :e_to, :subject, :body, :emailtypeid, :accountid, :contactid, :ownerid, _iris_convert_datetimestring_to_date[".$results['Date']."])";
		//$insert_sql = PerformMacroSubstitution($insert_sql);
		// miv 22.09.2005: если дата в письме не указана, то макроподстановка выдает ошибку, вставка пустой строки вместо даты тоже
/*
		if (substr($results['Date'], 3, 1) == ',')
			$date_length = 25;
		else
			$date_length = 20;

		$emaildatestr = trim(substr($results['Date'], 0, $date_length));
*/
		$emaildatestr = $results['Date'];
		$messageDateStr = date('d.m.Y H:i:s', strtotime(substr($emaildatestr, 5))); // miv 13.12.2011: переводим с учетом часового пояса
		if ($messageDateStr == '') {
			$messageDateStr = date('d.m.Y H:i:s');
		}
		$insert_sql = "insert into iris_email(id, createdate, e_from, e_to, subject, body, emailtypeid, accountid, contactid, ownerid, messagedate, incidentid) values (:id, now(), :e_from, :e_to, :subject, :body, :emailtypeid, :accountid, :contactid, :ownerid, to_timestamp('".$messageDateStr."', 'DD.MM.YYYY HH24:MI:SS'), :incidentid)";

		$cmd=$CONN->prepare($insert_sql);
		$cmd->bindParam(":id", $EmailID);
		$cmd->bindParam(":e_from", $email_from);
		$cmd->bindParam(":e_to", $email_to);
		$cmd->bindParam(":subject", $email_subject);
		$cmd->bindParam(":body", $email_body);
		$cmd->bindParam(":emailtypeid", $et_res[0][0]);
		$cmd->bindParam(":accountid", $accountID);
		$cmd->bindParam(":contactid", $contactID);
		$cmd->bindParam(":ownerid", $p_ownerID);
		$cmd->bindParam(":incidentid", $incident[0]['id']);
		$cmd->execute();
		fb_debug($cmd->errorInfo(), 'inserting email status', 'info');
		if ($cmd->errorCode() != '00000')
			fb_debug('email is not inserted!');


    // расширяем массивы прикрепленных файлов и inline изображений
    if (!isset($results['Attachments'])) {
      $results['Attachments'] = array();
    }
    if (!isset($results['Related'])) {
      $results['Related'] = array();
    }

    $attachments_and_related =
      array_merge($results['Attachments'], $results['Related']);

    // вставляем прикрепленные файлы и изображения
    foreach($attachments_and_related as $attachment) {
      fb_debug('attachment');
      $attachment_info = InsertAttachment($CONN, $attachment, array(
        "emailid" => $EmailID,
        "accountid" => $accountID,
        "contactid" => $contactID,
        "ownerid" => $p_ownerID,
        ));

      if ($attachment_info["isInline"]) {
        updateInlineImagesLinks($CONN, $EmailID, $attachment_info);
      }
    }
	}

	fb_debug('----end parsing...');
	for($warning = 0, Reset($mime->warnings); $warning < count($mime->warnings); Next($mime->warnings), $warning++)	{
		$w = Key($mime->warnings);
		fb_debug('Warning: '.$mime->warnings[$w].' at position '.$w);
	}
	return $EmailID;
}

function InsertAttachment($con, $attachment, $params) {
  $file_real_name = create_guid();
  $file_path = str_replace(chr(92), chr(47),
    realpath(GetPath().'/files').'/'.$file_real_name); // заменяем \ на /
  fb_debug($file_path, '--------------------filename');
  file_put_contents($file_path, $attachment['Data']);
  $attachment_name = DecodeAttachmentName($attachment["FileName"]);

  $sql = "insert into iris_file (id, createdate, file_file, file_filename, ".
         "EmailID, AccountID, ContactID, OwnerID, filestateid, date) values ".
         "(:id, now(), :file_file, :file_filename, :EmailID, :AccountID, ".
         ":ContactID, :OwnerID, ".
         "(select id from iris_filestate where code = 'Active'), now())";
  $fileId = create_guid();
  $cmd = $con->prepare($sql);
  $cmd->bindParam(":id", $fileId);
  $cmd->bindParam(":file_file", $file_real_name);
  $cmd->bindParam(":file_filename", $attachment_name);
  $cmd->bindParam(":EmailID", $params['emailid']);
  $cmd->bindParam(":AccountID", $params['accountid']);
  $cmd->bindParam(":ContactID", $params['contactid']);
  $cmd->bindParam(":OwnerID", $params['ownerid']);
  $cmd->execute();
  fb_debug($cmd->errorInfo(), 'inserting email attachment status', 'info');

  return array(
    "isInline" => isset($attachment['ContentID']),
    "file_id" => $fileId,
    "ContentID" => $attachment['ContentID']
  );
}

function updateInlineImagesLinks($con, $emailid, $attachment_info) {
  if (!isset($attachment_info['ContentID'])) {
    return;
  }
  $findFromString = 'cid:' . $attachment_info['ContentID'];
  $replaceToString =
    "core/engine/web.php?_func=DownloadFile&table=iris_File&".
    "id=".$attachment_info["file_id"]."&column=file_file";

  $cmd = $con->prepare("select body from iris_email where id = :id");
  $cmd->bindParam(":id", $emailid);
  $cmd->execute();
  $emails = $cmd->fetchAll(PDO::FETCH_ASSOC);

  $body = $emails[0]["body"];
  $body = str_replace($findFromString, $replaceToString, $body);

  $cmd = $con->prepare("update iris_email set body = :body where id = :id");
  $cmd->bindParam(":id", $emailid);
  $cmd->bindParam(":body", $body);
  $cmd->execute();
}

// декодирует имя вложения из нужной кодировки
function DecodeAttachmentName($p_str) {
	$filename_encoding = detect_cyr_charset($p_str);
	switch ($filename_encoding) {
	case 'i':
		$filename_decoded = UtfDecode($p_str);
	break;
	case 'k':
		$filename_decoded = convert_cyr_string($p_str, 'k' , 'w');
	break;
	case 'w':
		$filename_decoded = $p_str;
	break;
	}
	if ($filename_decoded == '')
		$filename_decoded = $p_str;
	return $filename_decoded;

}
// определяет кодировку строки
function detect_cyr_charset($str) {
    $charsets = Array('k' => 0, 'w' => 0, 'd' => 0, 'i' => 0, 'm' => 0);
	$lowercase = 3;
	$uppercase = 1;
					  
    for ( $i = 0, $length = strlen($str); $i < $length; $i++ ) {
        $char = ord($str[$i]);
        //non-russian characters
        if ($char < 128 || $char > 256) continue;
        
        //CP866
        if (($char > 159 && $char < 176) || ($char > 223 && $char < 242)) $charsets['d']+=$lowercase;
        if (($char > 127 && $char < 160)) $charsets['d']+=$uppercase;
        
        //KOI8-R
        if (($char > 191 && $char < 223)) $charsets['k']+=$lowercase;
        if (($char > 222 && $char < 256)) $charsets['k']+=$uppercase;
        
        //WIN-1251
        if ($char > 223 && $char < 256) $charsets['w']+=$lowercase;
        if ($char > 191 && $char < 224) $charsets['w']+=$uppercase;
        
        //MAC
        if ($char > 221 && $char < 255) $charsets['m']+=$lowercase;
        if ($char > 127 && $char < 160) $charsets['m']+=$uppercase;
        
        //ISO-8859-5
        if ($char > 207 && $char < 240) $charsets['i']+=$lowercase;
        if ($char > 175 && $char < 208) $charsets['i']+=$uppercase;
        
    }
    arsort($charsets);
    return key($charsets);
}


// Функция для отправки запроса серверу
function write_pop3_response($socket, $msg) {
	$msg = $msg."\r\n";
	fwrite($socket, $msg);
	}
	
// Функция для чтения ответа сервера. Выбрасывает исключение в случае ошибки
function read_pop3_answer($socket, $top = false) {
	$read = fgets($socket);
	if ($top) {
		// Если читаем заголовки
		$line = $read;
		while (!ereg("^\.\r\n", $line)) {
			$line  = fgets($socket);
			$read .= $line;
		}
	$read .= fgets($socket);
	}
	if ($read{0} != '+') {
		if (!empty($read)) {
			throw new Exception('POP3 failed: '.$read."\n");
		} else {
			throw new Exception('Unknown error'."\n");
		}
	}
	return $read;
}


// Функция для чтения ответа сервера (запасной вариант)
function get_data($pop_conn) {
	$data="";
	$flag = 0;
	while (!feof($pop_conn)) {
		//$buffer = chop(fgets($pop_conn,1024));
		$buffer = chop(fgets($pop_conn));
		if(trim($buffer) == ".")
			break;
		if ($flag != 0)
			$data .= "$buffer\r\n";
		else
			$flag = 1;
		
	}
	return $data;
}	

function CheckMail() {
ini_set('display_errors', 'on');

//require_once(GetPath().'/core/engine/mail/rfc822_addresses.php');
//require_once(GetPath().'/core/engine/mail/mime_parser.php');

//require_once('rfc822_addresses.php');
//require_once('mime_parser.php');
require_once(GetPath().'/config/sections/Email/lib/rfc822_addresses.php');
require_once(GetPath().'/config/sections/Email/lib/mime_parser.php');


//set_time_limit(120); ////////////////////////////////////////////////////////////////////////////////////////////
	fb_debug('to turn off debug information edit config/sections/Email/lib/options.xml file,', 'debug mode is on', 'info');
	//$CONN = db_connect();
	$CONN = db_connect();
	$res = $CONN->query("select address, port, encryption, login, password, id, last_id, last_n from iris_emailaccount where isactive='Y'")->fetchAll(PDO::FETCH_ASSOC);
	$n = 1;
	$cnt = 0;
	foreach ($res as $row) {
		fb_debug($row['address'].' ['.$row['login'], ']-------------------------- new mailbox', 'warn');
		$account_messages = ReadNewMessages($CONN, $row['address'], $row['port'], $row['encryption'], $row['login'], $row['password'], $row['id'], $row['last_id'], $row['last_n']);
		if ($account_messages == -1)
			return;
		$cnt += $account_messages;
	}
	echo '{"messages_count": "'.$cnt.'"}';
}

// читает новые письма с ящика
function ReadNewMessages($p_con, $p_address, $p_port, $p_encrypton, $p_login, $p_pwd, $p_accountID, $p_last_uid, $p_last_n) {
ini_set('display_errors', 'on');
	// информация о ящике
	$address = $p_address;  	// адрес pop3-сервера
	if (($p_encrypton != 'no') and ($p_encrypton != ''))
		$address = $p_encrypton.'://'.$address; // miv 06.09.2010: для работы с gmail
	$port    = (int)$p_port;	// порт (стандартный pop3 - 110)
	$login   = $p_login;    	// логин к ящику
	$pwd     = $p_pwd;    		// пароль к ящику	

	$messages_count = 0;
	try {
		// считываем размер памяти, выделяемой скрипту
		$script_memory_limit = ((int)ini_get('memory_limit'))*1024*1024;	
	
		// Создаем и соединяем сокет к серверу
		fb_debug('connectiong to server...');
		$socket = fsockopen($address, $port, $errno, $errstr);
		if (!$socket) {
			//throw new Exception('fsockopen("'.$address.'", "'.$port.'") failed: '.$errno.' - '.$errstr."\n");
			fb_debug('fsockopen("'.$address.'", "'.$port.'") failed: '.$errno.' - '.$errstr);
			return;
		}
		fb_debug('connected');

		// Читаем информацию о сервере
		read_pop3_answer($socket);

		// Делаем авторизацию
		fb_debug('auth');
		write_pop3_response($socket, 'USER '.$login);
		read_pop3_answer($socket); // ответ сервера

		write_pop3_response($socket, 'PASS '.$pwd);
		$dummy_answer = read_pop3_answer($socket); // ответ сервера
		fb_debug($dummy_answer, 'server response');

		// Считаем количество сообщений в ящике и общий размер
		write_pop3_response($socket, 'STAT');
		$answer = read_pop3_answer($socket); // ответ сервера
		//fb_debug($answer, 'STAT');
		$answer_arr = explode(' ', $answer);
		$total_count = $answer_arr[1];
		fb_debug($total_count, 'total messages');
		if ($total_count == 0)
			return 0; // если сообщение нет, то перейдем к следующему ящику

		// проверим, поддерживается ли команда UIDL
		write_pop3_response($socket, 'UIDL');
		$buffer = fread($socket,3);
		if ($buffer != '+OK') {
			echo '{"error": "command UIDL is not supported", "messages_count": "-1", "email_account": "'.$login.'('.$address.')'.'"}';
			return -1;
		}
		
		// если поддерживается, то считаем список UID писем, находящихся в ящике
		$uids_string = get_data($socket);
		
		
		$uids_arr = explode("\r\n", $uids_string);
		$i=0;
		//foreach ($uids_arr as $elem)
		//	$uids_arr[$i++] = explode(" ", $elem);
		unset($uids_arr[$i-1]); // удаляем последний элемент массива, которыя является пустым
		// в uids_arr находится массив, елементы которого являются массивом. 0 - номер письма, 1 - его uid (uid уникален в пределах почтового яшика)
		// miv 01.10.2010: теперь в uids_arr содержится массив со строкой '<n> <uid>' (так нужно для ускорения считываения)
		
		//fb_debug($uids_arr, 'message uids array');
		$CONN = $p_con; //db_connect();
		// находим ответственного по ящику
		$OwnerID = null;

		$owncnt_res = $CONN->query("select count(id) from iris_emailaccount_defaultaccess where EmailAccountID='".$p_accountID."' and r='1'")->fetchAll();
		if ($owncnt_res[0][0] == 1) {
			$own_res = $CONN->query("select contactid from iris_emailaccount_defaultaccess where EmailAccountID='".$p_accountID."' and r='1'")->fetchAll();
			if ($own_res[0][0] != '')
				$OwnerID = $own_res[0][0];
		}		

		// Считаем список uid писем, содержащихся в БД, и, сравнив массивы, вычленим из uids_arr только новые письма
		// это ускорит проверку, так как будут сразу загружаться новые письма или добавляться в доступ, если письмо загружено с другого аккаунта
		$db_uids_array = $CONN->query("select '1 ' || uid as uid from iris_emailrecieved where emailaccountid='".$p_accountID."'")->fetchAll(PDO::FETCH_ASSOC);
		foreach ($db_uids_array as $key => $value)
			$db_uids_array[$key] = $value['uid'];
		
		$uid_server_ca = create_uidl_compare_array($uids_arr);
		$uid_db_ca = create_uidl_compare_array($db_uids_array);
		$uid_compared = array_diff_key($uid_server_ca, $uid_db_ca);
		
		$uids_only_new_array = array();
		foreach ($uid_compared as $key=>$val)
			$uids_only_new_array[] = array($val, $key);
		$uids_arr = $uids_only_new_array;
		//print_r($uids_arr);
		//return;

		// просмотрим каждый элемент массива uid
		fb_debug('checking uids array');				
		foreach ($uids_arr as $uid_item) {
			fb_debug($uid_item[0].' '.$uid_item[1], 'selected message', 'info');				
			// если данный uid уже есть в iris_mailrecieved для данного аккаунта, то письмо старое, пропустим его

			$res = $CONN->query("select id from iris_emailrecieved where uid='".$uid_item[1]."' and emailaccountid='".$p_accountID."'")->fetchAll();
			if ($res[0][0] != '')
				continue; // если письмо есть, то пропустим его и не будем загружать
			// TODO: это не ускоряет
			//if (in_array($uid_item[1], $db_uids_array))
			//	continue; // если письмо есть, то пропустим его и не будем загружать

			
			// если нет, то оно не обязательно является новым - может быть оно пришло с другого аккаунта (письмо могло быть выслано сразу нескольким пользователем)
			// можно в принципе при получении письма проставлять в iris_mailrecieved строки для всех аккаунтов на которые оно пришло и тогда проверять не нужно. но если в процессе работы добавлен аккаунт, то тогда может замусориться "новыми" письмами
			// проверим, есть ли данное письмо в БД. для этого счиатем его message-id при помощи команды TOP и найдем его в iris_mailrecieved
			fb_debug('TOP '.$uid_item[0].' 0');
			write_pop3_response($socket, 'TOP '.$uid_item[0].' 0');
/*			
			$buffer = fread($socket,3); // проверим, поддерживается ли команда TOP
			if ($buffer != '+OK') {
				echo '{"error": "command TOP is not supported", "messages_count": "-1", "email_account": "'.$login.'('.$address.')'.'"}';
				return -1;
			}
*/			
			$answer = get_data($socket); // считали заголовок письма номер $uid_item[0]
			
			$mime = new mime_parser_class;
			$mime->mbox = 1;
			$mime->decode_bodies = 0;
			$mime->ignore_syntax_errors = 1;

			$parameters=array('Data'=>$answer, 'SkipBody'=>1);
			if(!$mime->Decode($parameters, $decoded)) {
				continue;
			}
			$message_id = $decoded[0]["Headers"]["message-id:"]; // получили message-id письма
			if (($message_id == '') or ($message_id == null) or ($message_id == 'NULL')) {
				// если message-id пусто, то сгенерируем его на основе других заголовков (дата, от, кому, тема)
				$message_id = $decoded[0]["Headers"]["date:"].$decoded[0]["Headers"]["from:"].$decoded[0]["Headers"]["to:"].$decoded[0]["Headers"]["subject:"];
				fb_debug('Warning!! message-id is null and was generated from other headers');
			}
			$message_id = substr($message_id, 0, 1800); // обрезаем строку до длинны поля
			
			fb_debug($message_id, 'message-id');
			fb_debug($decoded[0]["Headers"]["subject:"], 'message subject');
			if ($decoded[0]["Headers"]["subject:"] == '')
				fb_debug('message subject is null');

			//$CONN = db_connect();
//set_time_limit(240);			
//ini_set('display_errors')
			//$res = $CONN->query("select emailid from iris_emailrecieved where messageid='".$message_id."'")->fetchAll();
			// miv 13:07 01.09.2009: иногда в message_id встречаются кавычки, поэтому select теперь через параметр
			$cmd = $CONN->prepare("select emailid from iris_emailrecieved where messageid=:messageid");
			$cmd->execute(array(":messageid" => $message_id));
			$res = $cmd->fetchAll();
			
			if ($res[0][0] != '') {
				fb_debug('this message is old and was loaded from another account');
				// если письмо есть, то оно не новое и нужно добавить права на данное письмо в соответсвии с текущим аккаунтом
				// TODO: если уже есть то заменить?..
				AddAccessInformation($CONN, $res[0][0], '', $p_accountID);

				// также добавим запись в iris_mailrecieved, что uid письма для данной учетной записи уже получим и сделаем ссылку на существующее письмо
				// сохраняем id письма в системе чтобы исключить его повторную загрузку
				//$cmd=$CONN->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values ('".create_guid()."', '".$res[0][0]."', '".$message_id."', '".$p_accountID."', '".$uid_item[1]."')");
				//$cmd->execute();
				// miv 13:07 01.09.2009: иногда в message_id встречаются кавычки, поэтому select теперь через параметр
				$cmd=$CONN->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values (:id, :emailid, :messageid, :emailaccountid, :uid)");
				$iris_emailrecieved_id = create_guid();
				$cmd->execute(array(":id" => $iris_emailrecieved_id, ":emailid" => $res[0][0], ":messageid" => $message_id, ":emailaccountid" => $p_accountID, ":uid" => $uid_item[1]));
				
				//fb_debug($cmd->errorCode(), 'iris_emailrecieved insert code');
				$messages_count++;
				continue; // переходим к следующему письму
			} else {
				fb_debug('this message is new');
				// если письма нет, то оно действительно новое, выполним 2+4 действия:

				// 0.1) если это "долгое" письмо, то не будем его загружать
				$message_is_long_flag = 0; // флаг того, что письмо "долгое" (загружается больше чем time_limit)
				$email_read_count = 1; // число попыток считывания данного письма с этого ящика
				if ($p_last_uid == $message_id) {
					$email_read_count+=$p_last_n;
					if ($p_last_n >= 2)
						$message_is_long_flag = 1;
				}
				//$emailaccount_update_sql = "update iris_emailaccount set last_id='".$message_id."', last_n=".$email_read_count." where id='".$p_accountID."'";
				//$cmd=$CONN->prepare($emailaccount_update_sql);
				//$cmd->execute();
				$emailaccount_update_sql = "update iris_emailaccount set last_id=:last_id, last_n=:last_n where id=:id";
				$cmd=$CONN->prepare($emailaccount_update_sql);
				$cmd->execute(array("last_id" => $message_id, "last_n" => $email_read_count, "id" => $p_accountID));

				if ($cmd->errorCode() != '00000') {
					fb_debug($emailaccount_update_sql, 'update long emailsql');		
					fb_debug($cmd->errorInfo(), 'update long email status');		
				}
				
				// 0.2) если скрипт имеет ограничение на размер выделяемой ему памяти, то узнаем размер письма
				$message_is_big_flag = 0; // флаг того, что письмо "большое" (его размер не может поместиться в память, выделенную php скрипту)
				if ($script_memory_limit > 0) {
					write_pop3_response($socket, 'LIST '.$uid_item[0]);
					$answer = read_pop3_answer($socket); // ответ сервера
					$email_size = explode(' ', $answer);
					$email_size = $email_size[2];
					$needed_size = (int)$email_size + memory_get_usage();
					if ($needed_size > $script_memory_limit) {
						fb_debug('size='.$needed_size.' limit='.$script_memory_limit, 'message is big');
						$message_is_big_flag = 1;
					}
				}
				
				// 1) считаем письмо с сервера (если оно "влезет" и "не долгое")
				if (($message_is_long_flag == 0) and ($message_is_big_flag == 0)) {
					write_pop3_response($socket, 'RETR '.$uid_item[0]);
					$answer = get_data($socket);
				} else {
					// если письмо "не влезет" или "долгое", то загрузим только его заголовок
					$answer = GetEmailTemplate($decoded[0]["Headers"], $message_is_big_flag);
					fb_debug('this message is big('.$message_is_big_flag.') or long('.$message_is_long_flag.') ...', 'skipping message body');
				}
				
				// 2) загрузим письмо в БД
				fb_debug('saving into db...');
				$EmailID = save_email($CONN, $answer, $OwnerID, $p_accountID); // сохраняем письмо в системе
				fb_debug('saving ok');
				// 3) добавим запись в iris_mailrecieved о новом письме, чтобы исключить его повторную загрузку
				//$cmd=$CONN->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values ('".create_guid()."', '".$EmailID."', '".$message_id."', '".$p_accountID."', '".$uid_item[1]."')");
				//$cmd->execute();
				$cmd=$CONN->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values (:id, :emailid, :messageid, :emailaccountid, :uid)");
				$iris_emailrecieved_id = create_guid();
				$cmd->execute(array("id" => $iris_emailrecieved_id, "emailid" => $EmailID, "messageid" => $message_id, "emailaccountid" => $p_accountID, "uid" => $uid_item[1]));

				//fb_debug($cmd->errorCode(), 'iris_emailrecieved insert code');
				// 4) проставим права и связи для письма
				AddAccessInformation($CONN, '', $message_id, $p_accountID);

				$messages_count++;
			}
		}

		// Отсоединяемся от сервера
		write_pop3_response($socket, 'QUIT');
		read_pop3_answer($socket); // ответ сервера
	} catch (Exception $e) {
		echo "\nError: ".$e->getMessage();
	}

	if (isset($socket)) {
		fclose($socket);
	}
	
	return $messages_count;		

}

function GetEmailTemplate($p_headers_array, $p_message_is_big_flag) {
	$mail_str  = 'Date: '.$p_headers_array['date:'].chr(10);
	$mail_str .= 'To: '.$p_headers_array['to:'].chr(10);
	$mail_str .= 'From: '.$p_headers_array['from:'].chr(10);
	$mail_str .= 'Subject: '.$p_headers_array['subject:'].chr(10);
	$mail_str .= 'Message-ID: '.$p_headers_array['message-id:'].chr(10);
	$mail_str .= 'Content-Type: text/html; charset = "'.GetDefaultEncoding().'"'.chr(10);
	$mail_str .= 'Content-Transfer-Encoding: 8bit'.chr(10);
	$mail_str .= chr(10);;
	$mail_str .= chr(10);;
	if ($p_message_is_big_flag == 1)
		$mail_str .= 'Данное письмо не может быть загружено в систему, так как оно превышает максимально допустимый размер';
	else
		$mail_str .= 'Данное письмо не может быть загружено в систему, так время его загрузки превысило максимально допустимое значение';
	
	return $mail_str;
}

function create_uidl_compare_array($p_uids_array) {
	//$uids_arr = explode(chr(10), $p_uids_string);

	$uids_list = array();
	foreach ($p_uids_array as $elem) {
		$elem_arr = explode(" ", $elem);
		if (isset($elem_arr[1]))
			$uids_list[$elem_arr[1]] = $elem_arr[0];
	}
	//print_r($uids_list);
	return $uids_list;
}

function get_support_emails() {
	if ($GLOBALS["support_emails"] == '') {
		$con = db_connect();
		$res = $con->query("select stringvalue as value from iris_systemvariable where code='support_email_addresses'")->fetchAll(PDO::FETCH_ASSOC);
		$emails = $res[0]['value'];
		if ($emails != '') {
			$emails = iris_str_replace(' ', '', $emails);
			$GLOBALS["support_emails"] = explode(',', $emails);
		} else 
			$GLOBALS["support_emails"] = array();
	}
	return $GLOBALS["support_emails"];
}

/// проверка почты
//CheckMail();
//////////////////

try {
	CheckMail();
} catch (Exception $e) {
	var_dump($e);
	//$err_msg = $e->getMessage();
	//if strpos(strtolower()), "maximum execution time")
	//echo "\nError: ".$e->getMessage();
}

//sleep(10);
//return '{"messages_count": "1"}';




?>
