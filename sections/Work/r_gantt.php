<?php


function gantt_getWorksTree($p_con, $p_projectid, $p_parentwork = null, $p_cmd = null) {
    if ($p_cmd == null) {
        //$sql = "select id, number, name, planstartdate, planfinishdate from iris_work where projectid = :projectid and ((parentworkid = :parentworkid) or (parentworkid is null and :parentworkid is NULL)) order by planstartdate";
        $sql  = "select T0.id, T0.number, T0.name, T0.planstartdate, T0.planfinishdate, T0.planstartdate - T1.planstartdate as startday, T0.planfinishdate+1 - T0.planstartdate as days ";
        $sql .= "from iris_work T0 left join iris_project T1 on T0.projectid = T1.id ";
        $sql .= "where T0.projectid = :projectid and ((T0.parentworkid = :parentworkid) or (T0.parentworkid is null and :parentworkid is NULL)) order by T0.planstartdate, T0.number";
        $p_cmd = $p_con->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
    }
    
    $p_cmd->bindParam(":projectid", $p_projectid);
    if ($p_parentwork == null)
        $p_cmd->bindParam(":parentworkid", $p_parentwork, PDO::PARAM_NULL);
    else
        $p_cmd->bindParam(":parentworkid", $p_parentwork);
    $p_cmd->execute();
    //echo '-- '.$p_cmd->getSQL().' --';

    $works = $p_cmd->fetchAll(PDO::FETCH_ASSOC);
    //print_r($works);
    $result = array();
    if (count($works) >= 1) {
        foreach ($works as $work) {
            $child_works = null;
            $childs = gantt_getWorksTree($p_con, $p_projectid, $work['id'], $p_cmd);
            //if ($childs != null)
            //    $child_works[] = $childs;
            //$result[] = array("work" => $work, "childs" => $child_works);

            $result[] = $work;
            foreach ($childs as $child)
                $result[] = $child;
        }
        //print_r($result);
        return $result;
    } else
        return null;
}

function gantt_showDiagramm($p_projectid) {
    $con = db_connect();

    // считаем даты заказа
    $sql = "select to_char(planstartdate, 'MM/DD/YYYY') as planstartdate, planfinishdate - planstartdate as days from iris_project where id=:id";
    $cmd = $con->prepare($sql);
    $cmd->execute(array(":id" => $p_projectid));
    $project = current($cmd->fetchAll(PDO::FETCH_ASSOC));

    $works = gantt_getWorksTree($con, $p_projectid);

    return array("data" => array("works" => json_convert_array($works), "project" => $project));
}

///////////////////////////////////////////////////////

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}
}
// не закрывать сессию так как тут пишутся параметры для смены ответсвенного
$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/auth.php';
include_once $path.'/core/engine/applib.php';
//include_once $path.'/config/common/Lib/lib.php';

//include_once $path.'/config/common/Lib/chown.php';
include_once $path.'/core/engine/pdo.php';


SendRequestHeaders();


if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}



$func = stripslashes($_POST['_func']);
$response = '';
if (strlen($func) == 0) {
//	$response = PrintError('Имя функции не задано');
} 
else {

    switch ($func) {

		case 'showDiagramm':
			$response = gantt_showDiagramm($_POST['project_id']);
			break;

//		default:
//			$response = 'Неверное имя функции: '.$func;
	}
}

if ((is_array($response) == true) and (count($response) > 0)) {
	echo json_encode($response);
}

?>