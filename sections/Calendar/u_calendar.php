<?php
/**
 * Календарь
 */

function calendar_isCheckAccess($table) {
  return TableHasRecordAccessEnabled($table) == 1 and
         IsUserInAdminGroup($con) == 0;
}

function calendar_getTasks($from, $to, $id) {
  $con = db_connect();
  $params = array();

  $sql  = "select T.id as id, ";
  $sql .= "T.name as title, to_char(startdate, 'YYYY-MM-DDThh24:MI:SS+00:00') as start, ";
  $sql .= "to_char(finishdate, 'YYYY-MM-DDThh24:MI:SS+00:00') as end, ";
  $sql .= "TT.code as type, TI.code as importance, TS.code as state, TR.code as result, ";
  $sql .= "case when T.ownerid = :user_id then 1 else 0 end as my_task ";
  $sql .= "from iris_task T ";
  $sql .= "left join iris_tasktype TT on T.tasktypeid = TT.id ";
  $sql .= "left join iris_taskimportance TI on T.taskimportanceid = TI.id ";
  $sql .= "left join iris_taskstate TS on T.taskstateid = TS.id ";
  $sql .= "left join iris_taskresult TR on T.taskresultid = TR.id ";
  $params[':user_id'] = GetUserID($con);

  if (calendar_isCheckAccess('iris_task')) {
    $sql .= "left join iris_task_access PG on (PG.RecordID = T.ID and PG.AccessRoleID = :role_id) ";
    $sql .= "left join iris_task_access PU on (PU.RecordID = T.ID and PU.ContactID = :user_id) ";
    $params[':role_id'] = GetUserAccessRoleID($con);
  }
  if ($id == null) {
    $sql .= "where startdate::date >= to_date(:from, 'YYYY-MM-DD') ";
    $sql .= "  and startdate::date <= to_date(:to, 'YYYY-MM-DD') ";
    $params[':from'] = $from;
    $params[':to'] = $to;
  } else {
    $sql .= "where T.id = :id ";
    $params[':id'] = $id;
  }
  if (calendar_isCheckAccess('iris_task')) {
    $sql .= "and ((PU.R is not null and PU.R = '1') or (PU.R is null and PG.R = '1'))";
  }

  $cmd = $con->prepare($sql);
  $cmd->execute($params);
  $tasks = $cmd->fetchAll(PDO::FETCH_ASSOC);

  //print_r($tasks);
  return $tasks;
}

function calendar_createEventFromTask($task) {
  $event = array(
    "id" => $task["id"],
    "title" => $task["title"],
    "start" => $task["start"],
    "end" => $task["end"]
  );

  if ($task["importance"] == "High") {
    $event["color"] = "#ff9966";
    $event["textColor"] = "#404040";
  }

  if ($task["importance"] == "Highest") {
    $event["color"] = "#eaa0a0";
    $event["textColor"] = "#404040";
  }

  if ($task["state"] == "Finished" or
      $task["state"] == "Future" or
      $task["state"] == "Canceled") {
    $event["color"] = "#afb2b4";
  }

  if (!$task["my_task"]) {
    $event["color"] = "#dddddd";
    $event["textColor"] = "#404040";
  }

  if ($task["importance"] == "Highest" or
      $task["importance"] == "High") {
    // TODO
  }

  return $event;
}

function calendar_formatTasksToEvents($tasks) {
  $events = array();
  foreach($tasks as $task) {
    $events[] = calendar_createEventFromTask($task);
  }

  return $events;
}

function calendar_getEvents($from, $to) {
  $tasks = calendar_getTasks($from, $to, null);
  $events = calendar_formatTasksToEvents($tasks);

  return $events;
}

function calendar_getEventById($id) {
  $tasks = calendar_getTasks(null, null, $id);
  $events = calendar_formatTasksToEvents($tasks);

  return $events[0];
}

function calendar_resizeEvent($id, $end) {
  $con = db_connect();

  GetCurrentUserRecordPermissions('iris_task', $id, $permissions);
  if ($permissions['w'] == 0) {
    return array("isOk" => false);
  }

  $sql  = "update iris_task ";
  $sql .= "set finishdate = to_timestamp(:end, 'YYYY-MM-DDThh24:MI:SS') ";
  $sql .= "where id=:id";

  $cmd = $con->prepare($sql);
  $cmd->execute(array(
    ":id" => $id,
    ":end" => $end
  ));
  $code = $cmd->errorInfo();

  //print_r($code);
  return array("isOk" => $code[0] == "00000");
}

function calendar_moveEvent($id, $start) {
  $con = db_connect();

  GetCurrentUserRecordPermissions('iris_task', $id, $permissions);
  if ($permissions['w'] == 0) {
    return array("isOk" => false);
  }

  $sql  = "update iris_task ";
  $sql .= "set _field_ = _field_ + (to_timestamp(:newstartdate, 'YYYY-MM-DDThh24:MI:SS') - startdate) ";
  $sql .= "where id=:id ";
  $sql .= "and _field_ is not null";

  $fileds = array('finishdate', 'reminddate', 'startdate'); // startdate MUST be last
  foreach ($fileds as $field) {
    $tmpsql = str_replace('_field_', $field, $sql);
    $cmd = $con->prepare($tmpsql);
    $cmd->execute(array(
      ":id" => $id,
      ":newstartdate" => $start
    ));
    $code = $cmd->errorInfo();

    if ($code[0] != "00000") {
      return array("isOk" => false);
    }
  }

  return array("isOk" => true);
}

function calendar_generateEventId() {
  return array("id" => create_guid());
};


///////////////////////////////////////////////////////


if (!session_id()) {
  @session_start();
  if (!session_id()) {
    echo 'Невозможно создать сессию!';
  }
}
session_write_close();

$path = $_SESSION['INDEX_PATH'];

include $path.'/core/engine/applib.php';
include $path.'/config/common/Lib/lib.php';
include $path.'/config/common/Lib/access.php';

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
  
    case 'getEvents':
      $response = calendar_getEvents($_POST['start'], $_POST['end']);
      break;

    case 'getEventById':
      $response = calendar_getEventById($_POST['id']);
      break;

    case 'resizeEvent':
      $response = calendar_resizeEvent($_POST['id'], $_POST['end']);
      break;

    case 'moveEvent':
      $response = calendar_moveEvent($_POST['id'], $_POST['start']);
      break;

    case 'generateEventId':
      $response = calendar_generateEventId();
      break;

    
    default:
      $response = 'Неверное имя функции: '.$_POST['_func'];
  }
}

echo json_encode($response);

?>
