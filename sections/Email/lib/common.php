<?php

/*********************
* общие функции раздела email
* в основном это функции, дублирующие applib
* + функция fb_debug() для отдалки
***********************/


function fb_debug() {
	/*
	$path = realpath(dirname(__FILE__));
	$xml = simplexml_load_file($path.'/options.xml');
	if ($xml->OPTIONS['fb_debug'] == 'on') {
		$args = func_get_args();
		file_put_contents($path.'/debug.log', $args[1].': '.var_export($args[0], true).chr(10), FILE_APPEND);
		//return call_user_func_array('fb', $args);
	}
	*/
}


// // вставляет права на входящие или исходящие письма
// если $p_access_mode = default или не указан, то работаем с входящим письмом
// при этом письмо может быть указано p_EmailID либо p_MessageID
// если $p_access_mode = outbox, то работаем с исходящим письмом
// в этом случае письмо должно быть указано параметром p_EmailID
function AddAccessInformation($p_con, $p_EmailID, $p_MessageID, $p_accountID, $p_access_mode = 'default') {
	fb_debug('== AddAccessInformation begin');
	// проверка того, что права доступа на таблицу включены
	$res = $p_con->query("select id from iris_table where code='iris_email' and is_access='1'")->fetchAll();;
	if ($res[0][0] == '')
		return; // если права на записи не установлены то выйдем

	// получение id письма
	if ($p_EmailID != '')
		$rec_id	= $p_EmailID;
	else {
		//$email_res = $p_con->query("select emailid from iris_emailrecieved where messageid='".$p_MessageID."'")->fetchAll();
		// miv 13:07 01.09.2009: иногда в message_id встречаются кавычки, поэтому select теперь через параметр
		$cmd = $p_con->prepare("select emailid from iris_emailrecieved where messageid=:messageid");
		$cmd->execute(array(":messageid" => $p_MessageID));
		$email_res = $cmd->fetchAll();
		
		$rec_id	= $email_res[0][0];
	}
	
fb_debug($p_access_mode, 'p_access_mode');
	//if $p_access_mode == 'outbox'
	
	//$access_res = $p_con->query("select contactid, r, w, d, a from iris_emailaccount_defaultaccess where emailaccountid='".$p_accountID."'")->fetchAll();
	$access_res = $p_con->query("select contactid, r, w, d, a from iris_emailaccount_".$p_access_mode."access where emailaccountid='".$p_accountID."'")->fetchAll();
	foreach ($access_res as $access_row) {
		// вставка прав на письмо
		$l_user_access_sql = "insert into iris_email_access (ID, RecordID, ContactID, R, W, D, A) values ('".create_guid()."', '".$rec_id."', '".$access_row['contactid']."', '".$access_row['r']."', '".$access_row['w']."', '".$access_row['d']."', '".$access_row['a']."')";
		fb_debug($l_user_access_sql, 'email sql');
		$p_con->exec($l_user_access_sql);
		fb_debug($p_con->errorInfo(), 'inserting email access');		
		
		// вставка прав на файлы
		$email_attachment_res = $p_con->query("select id from iris_file where emailid='".$rec_id."'")->fetchAll();
		foreach ($email_attachment_res as $email_attachment) {
			$l_user_access_attachment_sql = "insert into iris_file_access (ID, RecordID, ContactID, R, W, D, A) values ('".create_guid()."', '".$email_attachment[0]."', '".$access_row['contactid']."', '".$access_row['r']."', '".$access_row['w']."', '".$access_row['d']."', '".$access_row['a']."')";
			fb_debug($l_user_access_attachment_sql, 'attachment sql');
			$p_con->exec($l_user_access_attachment_sql);
			fb_debug($p_con->errorInfo(), 'inserting attachments access');		
		}
	}
	fb_debug('== AddAccessInformation end');
}

// декодирует имя файла (и тему письма тоже может)
// функция не нужна. оказывается класс нормально декодирует имена файлов. просто они были в кодировке utf-8 ...
/*
function ConverEncodedMailString($p_str) {
	if (substr($p_str, 0, 2) == '=?') {
		$str_array = explode('?', $p_str);
		$encoding = strtolower($str_array[1]);
	
		switch (strtolower($str_array[2])) {
			case 'b':
				$decoded = iconv($encoding, GetDefaultEncoding(), base64_decode($str_array[3]));
				//$p_decoded = convert_cyr_string(base64_decode($str_array[3]), "k","w");
			break;
			case 'q':
				$decoded = iconv($encoding, GetDefaultEncoding(), quoted_printable_decode($str_array[3]));
			break;
			default:
				$decoded = $p_str;
		}	
		return $decoded;
	} else
	return $p_str;
}
*/

?>