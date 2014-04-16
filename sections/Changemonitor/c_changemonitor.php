<?php
//********************************************************************
// карочка "changemonitor"
//********************************************************************

function Changelog_GetCardInfo($p_rec_id) {
	$con = db_connect();
	$sql = $con->prepare("select T1.dictionary as dictionary, T1.detail as detail, T2.code as section from iris_table T1 left join iris_section T2 on T1.SectionID = T2.ID where T1.id = :id");
	$sql->execute(array(":id" => $p_rec_id));
	return current($sql->fetchAll(PDO::FETCH_ASSOC));
}

///////////////////////////////////////////////////////

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		//TODO: позаботиться о правильном выводе ошибки
		echo 'Невозможно создать сессию!';
	}			
}
// miv 20.05.2009: Заканчивам текущую сессию и сохраняем данные сессии
// Поскольку данные сессии блокируются для предотвращения конкурирующей записи, только один скрипт может работать с сессией в данный момент времени
// Данные сессии нам нужны тоьлко для чтения, поэтому сразу зарываем сессию
session_write_close();

$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';

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
		
		case 'Changelog_GetCardInfo':
			$response = Changelog_GetCardInfo($_POST['p_table_id']);
			break;
			
		default:
			$response = 'Неверное имя функции: '.$_POST['_func'];
	}
}

echo json_encode($response);

?>