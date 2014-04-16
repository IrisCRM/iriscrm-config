<?php
//********************************************************************
// Закладка "История изменений"
//********************************************************************

function GetMonitorInfo($p_rec_id) {
	$con = db_connect();
	$sql = $con->prepare("select "._db_datetime_to_string('monitorstartdate')." as monitorstartdate, id as id from iris_changelogmonitor where userid=:userid and recordid=:recordid");
	$sql->execute(array(":userid" => GetUserID($con), ":recordid" => $p_rec_id));
	return current($sql->fetchAll());
}

function Changelog_DrawControl($p_rec_id, $p_grid_id) {
	$result = array();
	
	$html_template = '<table class="changelog_control"><tr><td class="changelog_cb">#c1#</td><td class="changelog_label">#c2#</td></tr></table>';
	$random_id = 'changelog_'.rand();

	$monitor_info = GetMonitorInfo($p_rec_id);
	if ($monitor_info['monitorstartdate'] == '') {
		$checked = '';
		$attr = 'date_str=""';
		$add_caption = '';
	} else {
		$checked = ' checked';
		$attr = ' date_str="'.$monitor_info['monitorstartdate'].'"';
		$add_caption = ' (отслеживаются с '.$monitor_info['monitorstartdate'].')';
	}
	$html_template = iris_str_replace('#c1#', '<input id="'.$random_id.'" '.$attr.' grid_id="'.$p_grid_id.'" type="checkbox" onclick="g_Changelog_switchmonitoring('.chr(39).$p_grid_id.chr(39).', '.chr(39).'switch'.chr(39).', this) "'.$checked.'>', $html_template);
	$html_template = iris_str_replace('#c2#', '<label for="'.$random_id.'">Следить за историей'.$add_caption.'</label>', $html_template);
	
	$result['html'] = json_convert($html_template);
	
	return $result;
}


function Changelog_SwitchMonitoring($p_rec_id, $p_grid_id, $p_mode) {
	// если первый раз, то просто нарисуем чекбокс
	if ($p_mode == 'init') {
		return Changelog_DrawControl($p_rec_id, $p_grid_id);
	}

	$con = db_connect();
	$monitor_info = GetMonitorInfo($p_rec_id);
	if ($monitor_info['monitorstartdate'] == '') {
		$sql = $con->prepare("insert into iris_changelogmonitor (id, userid, recordid, monitorstartdate, ownerid) values (:id, :userid, :recordid, "._db_current_datetime().", :ownerid)");
		$newid = create_guid();
		$sql->execute(array(":id" => $newid, ":userid" => GetUserID($con), ":recordid" => $p_rec_id, ":ownerid" => GetUserID($con)));
	} else {
		$sql = $con->prepare("delete from iris_changelogmonitor where id=:id");
		$sql->execute(array(":id" => $monitor_info['id']));
	}

	return Changelog_DrawControl($p_rec_id, $p_grid_id);
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

$path = realpath(dirname(__FILE__)."/./../../../../");

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
		
		case 'Changelog_SwitchMonitoring':
			$response = Changelog_SwitchMonitoring($_POST['p_rec_id'], $_POST['p_grid_id'], $_POST['p_mode']);
			break;
			
		default:
			$response = 'Неверное имя функции: '.$_POST['_func'];
	}
}

echo json_encode($response);

?>