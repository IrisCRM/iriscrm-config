<?php
//********************************************************************
// Раздел "Мои настройки"
//********************************************************************

function ChangePassword($p_old, $p_new1, $p_new2) {
	$result['errm'] = '';

	// проверим указаный пароль
	$con = db_connect();
	$user_id = GetUserID($con);
	$res = $con->query("select password from iris_contact where id='".$user_id."'")->fetchAll();
	$current_db_pwd = $res[0][0];
	
	if (md5($p_old) != $current_db_pwd) {
		$result['errm'] = json_convert('Необходимо указать верный текущий пароль');
		return json_encode($result);
	}
	
	if ($p_new1 == '') {
		$result['errm'] = json_convert('Не указан новый пароль');
		return json_encode($result);
	}

	if ($p_new1 != $p_new2) {
		$result['errm'] = json_convert('Поле "Пароль" и "Подтверждение" различаются. необходимо указать одинаковые значения');
		return json_encode($result);
	}

	// пароль длинный и содержит цифры
	if ($p_new1 != $p_new2) {
		$result['errm'] = json_convert('Поле "Пароль" и "Подтверждение" различаются. необходимо указать одинаковые значения');
		return json_encode($result);
	}

	// новый пароль совпадает со старым 
	if ($p_old == $p_new1) {
		$result['errm'] = json_convert('Новый пароль не должен совпадать со старым');
		return json_encode($result);
	}
	
	
	if (iris_strlen($p_new1) < 6) {
		$result['errm'] = json_convert('Пароль должен быть не менее 6 символов');
		return json_encode($result);
	}

	$pattern1 = '.[A-Za-z].'; // символы английского алфавита
	$pattern2 = '.[0-9].'; // цифры
	$pattern3 = ".[а-яА-я\\.,!@#$%\\^&\\*() ~`_+\\\\\\[\\]\\{\\}]."; // недопустимые символы
	$pm1 = iris_preg_match($pattern1, $p_new1); 
	$pm2 = iris_preg_match($pattern2, $p_new1); 
	$pm3 = iris_preg_match($pattern3, $p_new1);
/*	
	if (!(($pm1 == 1) and ($pm2 == 1) and ($pm3 == 0))) {
		$result['errm'] = json_convert('Пароль должен состоять из символов И цифр английского алфавита');
		return json_encode($result);
	}
*/	
	if ($pm3 == 1) {
		$result['errm'] = json_convert('Пароль может содержать только цифры и символы английского алфавита');
		return json_encode($result);
	}


	$cmd = $con->prepare("update iris_contact set password=:pwd where id=:id");
	$cmd->execute(array(":pwd" => md5($p_new1), ":id" =>$user_id));
	$error_info = $cmd->errorInfo();
	if ($error_info[0] != '00000')
		$result['errm'] = json_convert('Не удалось сменить пароль');
	
	return json_encode($result);
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


if (strlen($_POST['_func']) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
	switch ($_POST['_func']) {
	case 'ChangePassword':
		$response = ChangePassword($_POST['current'], $_POST['new1'], $_POST['new2']);
		break;

	default:
		$response = 'Неверное имя функции: '.$_POST['_func'];
	}
}

echo $response;

?>