<?php
//********************************************************************
// Раздел "Обновление"
//********************************************************************


function GetSQLForm()
{
	//Проверим, имеет ли текущий пользователь админские права
	if (!IsUserInAdminGroup()) {
		$result['error'] = json_encode_str('Вы должны иметь права админстратора для доступа к этой функции.');
		return $result;
	}
	
//	$result .= '<div id="sql_section" style="position: absolute;">';
	$result = <<<EOD
<table id="sql_section">
<tbody>
<tr><td>
	
<form name="u_sql_send" method="POST" onsubmit="return false;">
	
<table width="100%" border="0" align="left" valign="top">
<tbody>

<tr class="info">
<td class="info" colspan=2>
Будьте аккуратны, выполняя SQL запросы!
</td>
</tr>
<tr class="info">
<td class="info" colspan=2>
<strong>Не выполняйте SQL скрипты дважды, если этого не требуется.</strong>
</td>
</tr>
<tr class="info">
</td>
</tr>
	
<tr>
<td colspan="2">
<h2 style="margin: 20px 0px 10px 0px;">Выполнение запроса</h2>
</td>
</tr>
<tr>
<td colspan="2"><textarea name="sql" value="Введите текст SQL запроса" style="width: 90%; height: 300px;" class="edtText"></textarea></td>
</tr>
<tr>
<tr>
<td colspan="2">
<input name="setuptables" type="button" onclick="u_sql_runsql();" value="Выполнить" class="button" title="Нажмите, чтобы выполнить скрипт.">
</td>
</tr>
<tr>
<td colspan="2">
<div id="u_sql_sqlresult">
</div>
</td>
</tr>
</tbody>
</table>

</form>

</td>
</tr>
</tbody>
</table>
EOD;
//	$result .= '</div>';
	return array('html' => json_encode_str($result));
}



//Обновление - центральная функция раздела
function RunSQL($p_sql)
{
  //Соединение со старой базой
  $con = GetConnection();

  $sql = json_decode_str($p_sql);
  
	$con->exec($sql);
  $errorCode = $con->errorInfo();

  $result['error'] = '';
  $result['html'] = '';
  
  if (strtolower(substr(trim($sql), 0, 7)) == 'select ') {
    $statement = $con->prepare($sql);
    $statement->execute();
    $res = $statement->fetchAll(PDO::FETCH_ASSOC);
    $result['html'] .= '<table id="grid" class="grid">';
    $result['html'] .= '<thead>';
    $result['html'] .= '<tr>';
    foreach ($res[0] as $key => $val) {
      $result['html'] .= '<th class="grid">'.json_encode_str($key).'</th>';
    }
    $result['html'] .= '</tr>';
    $result['html'] .= '</thead>';
    
    $result['html'] .= '<tbody>';
    $class = 'grid_even';
    foreach ($res as $row) {
      $result['html'] .= "<tr class=\"$class\">";
      $class = $class == 'grid_even' ? 'grid_odd' : 'grid_even';
      foreach ($row as $key => $val) {
        $result['html'] .= '<td class="grid_row_string">'.json_encode_str($row[$key]).'</td>';
      }
      $result['html'] .= '</tr>';
    }
    $result['html'] .= '</tbody>';
    $result['html'] .= '</table>';
  }

  if ('00000' != $errorCode[0]) {
    $result['error'] .= $errorCode[0].': '.json_encode_str($errorCode[2]);
  }
  else {
    $result['html'] = json_encode_str("<p>Скрипт выполнен успешно.</p>").$result['html'];
  }

	return $result;
}


///////////////////////////////////////////////////////

//include_once realpath('./../../..').'/core/engine/applib.php';
include_once realpath('./../..').'/common/Lib/lib.php';

SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}

//Проверим, имеет ли текущий пользователь админские права
if (!IsUserInAdminGroup()) {
	echo json_encode(array('html' => json_encode_str('Вы должны иметь права админстратора для доступа к этой функции.')));
	return;
}

$func = $_POST['_func'];

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {

	switch ($func) {
    case 'GetSQLForm':
      $response = GetSQLForm();
      break;
      
    case 'RunSQL':
      $response = RunSQL(stripslashes($_POST['sql']));
      break;
      
    default:
      $response = 'Неверное имя функции: '.$_POST['_func'];
	}
  
}

echo json_encode($response);

?>