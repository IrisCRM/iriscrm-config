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
include_once $path.'/config/common/Sections/Task/r_task.php';


//SendRequestHeaders();

if (!isAuthorised()) {
	echo '<b>Не авторизован<b><br>';
	die;
}


$func = stripslashes($_POST['_func']);

if (strlen($func) == 0) {
	//если передаем GET
	$StartDate = stripslashes($_GET['_p_startdate']);
	$EndDate = stripslashes($_GET['_p_enddate']);
	$OwnerID = stripslashes($_GET['_p_ownerid']);
	$response = r_Task_Calendar_DrawReport($StartDate, $EndDate, $OwnerID);
	echo $response;
}
else {
	//если передаем POST
	switch ($func) {
		case 'GetDefaultValues':
			$response = r_Task_Calendar_GetDefaultValues();
			break;
		default:
			$response = 'Неверное имя функции: '.$func;
	}
	echo json_encode($response);
}


/////////////////////////


//Значения по умолчанию (при создании записи)
function r_Task_Calendar_GetDefaultValues()
{
	$con = GetConnection();

	//Дата
	$Date = GetCurrentDBDate($con);
	$result = FieldValueFormat('dts', $Date, null, $result);
	$result = FieldValueFormat('dte', $Date, null, $result);
	
	//Ответственный	
	$result = GetDefaultOwner(GetUserName(), $con, $result);
		
	return $result;	
}


function r_Task_Calendar_DrawReport($p_StartDate, $p_EndDate, $p_OwnerID)
{
	$con = GetConnection();
	
	//Сформируем отчет
	$result = LoadTemplate('r_task_calendar.html');
	$result = iris_str_replace('#charset#', GetDefaultEncoding(), $result);
	
	$data_begin_pos = iris_strpos($result, '<!--data_begin-->');
	$data_end_pos = iris_strpos($result, '<!--data_end-->');
	$result_beg = iris_substr($result, 0, $data_begin_pos);
	$result_end = iris_substr($result, $data_end_pos);
	$result_data = iris_substr($result, $data_begin_pos + strlen('<!--data_begin-->'), $data_end_pos - $data_begin_pos - strlen('<!--data_end-->') - 2);

	$result = $result_beg;

	list($OwnerName) = GetFieldValuesByID('Contact', $p_OwnerID, array('Name'), $con);

	$result = iris_str_replace('[Дата_с]', $p_StartDate, $result);
	$result = iris_str_replace('[Дата_по]', $p_EndDate, $result);
	$result = iris_str_replace('[Ответственный]', $OwnerName, $result);

	$Date = GetCurrentDBDateTime($con);
	$result = iris_str_replace('[Дата]', $Date, $result);
	
	$diff = strtotime('02.01.2000') - strtotime('01.01.2000');
	$p_EndDate = date('d.m.Y', strtotime($p_EndDate)+$diff); 
	
	//Получим перечень дел
	$select_sql = r_Task_GetTaskSelect()
			. "where t.OwnerID = :p_OwnerID " 
			. "and t.StartDate >= :p_start and (t.FinishDate < :p_end or t.FinishDate is null) "
			. "order by t.StartDate asc";
	$statement = $con->prepare($select_sql);
	$statement->execute(array(
		':p_OwnerID' => $p_OwnerID,
		':p_start' => $p_StartDate,
		':p_end' => $p_EndDate
	));
	$res = $statement->fetchAll();

	$result .= '<table width="100%"><tbody>';
	
	$PrewDate = date('d.m.Y', strtotime($Date));
	$TodayDate = strtotime(date('d.m.Y', strtotime($Date)));
	$i = 0;
	foreach ($res as $row) {
		$CurrentDate = date('d.m.Y', strtotime($row['startdate']));
		if ($CurrentDate != $PrewDate) {
			$result .= '<tr class="datehead">';
			$result .= '<td class="datehead" colspan="3">';
			if ($TodayDate == strtotime($CurrentDate)) {
				$result .= 'Сегодня';
			}
			else
			if ($TodayDate-$diff == strtotime($CurrentDate)) {
				$result .= 'Вчера';
			}
			else
			if ($TodayDate+$diff == strtotime($CurrentDate)) {
				$result .= 'Завтра';
			}
			else {
				$result .= $CurrentDate;
			}
			$result .= '</td>'; 
			$result .= '</tr>';
		}
		$PrewDate = $CurrentDate;		
		
		
		$row_class = 'normal';		
		if ($row['importancecode'] == 'Highest') {
			$row_class = 'red';		
		}
		if ($row['importancecode'] == 'High') {
			$row_class = 'softred';		
		}

		$col_class = 'normal';
		if ($row['statecode'] == 'Finished') {
			$col_class = 'completed';		
			$row_class = 'completed';		
		}
		
		$result .= '<tr class="'.$row_class.'" style="border-bottom: solid 1px; border-top: solid 1px;">'; 
		$result .= '<td class="'.$col_class.'" width=100>'.date('H:i', strtotime($row['startdate'])).' - '.date('H:i', strtotime($row['finishdate'])).'</td>'; 
		$result .= '<td class="'.$col_class.'"><b>'.$row['name'].'</b><br/>'; 
		if ($row['account'] != '') {
			$result .= '<br/>Компания: '.$row['account'];

			$result .= ' (';			
			$result .= ($row['aphone1'] != '') || ($row['aphone2'] != '') || ($row['aphone3'] != '') ? 'тел.:' : '';
			$result .= $row['aphone1'] != '' ? ' '.$row['aphone1']: '';  
			$result .= ($row['aphone1'] != '') && (($row['aphone2'] != '') || ($row['aphone3'] != '')) ? ',' : '';
			$result .= $row['aphone2'] != '' ? ' '.$row['aphone2']: '';
			$result .= ($row['aphone3'] != '') && (($row['aphone1'] != '') || ($row['aphone2'] != '')) ? ', ' : '';
			$result .= $row['aphone3'] != '' ? ', '.$row['aphone3'] : '';
			$result .= ($row['aphone1'] != '') || ($row['aphone2'] != '') || ($row['aphone3'] != '') ? '; ' : '';

			$result .= $row['afax'] != '' ? 'факс: '.$row['afax'].'; ': '';  
			$result .= $row['aweb'] != ''? $row['aweb'].'; ': '';  
			$result .= $row['aemail'] != ''? $row['aemail'].'; ': '';  
			$result .= $row['acity'] != ''? $row['acity'].', ': '';  
			$result .= $row['aaddress'] != ''? $row['aaddress']: '';  
			$result .= ')'; 
			
			
		}
		if ($row['contact'] != '') {
			$result .= '<br/><br/>Контакт: '.$row['contact']; 
			
			$result .= ' (';			
			$result .= ($row['cphone1'] != '') || ($row['cphone2'] != '') ? 'тел.:' : '';
			$result .= $row['cphone1'] != '' ? ' '.$row['cphone1']: '';  
			$result .= ($row['cphone1'] != '') && ($row['cphone2'] != '') ? ', ' : '';
			$result .= $row['cphone2'] != '' ? $row['cphone2'] : '';
			$result .= ($row['cphone1'] != '') || ($row['cphone2'] != '') ? '; ' : '';
			
			$result .= $row['cemail'] != '' ? $row['cemail'].';' : '';  
			$result .= $row['cskype'] != '' ? 'skype:'.$row['cskype'].';' : '';  
			$result .= $row['cicq'] != '' ? 'icq:'.$row['cicq'].';' : '';  
			$result .= $row['cgoogle'] != '' ? 'google:'.$row['cgoogle'].';' : '';  
			$result .= $row['caddress'] != '' ? $row['caddress'] : '';  
			$result .= ')'; 
		}		
		
		if ($row['project'] != '') {
			$result .= '<br/><br/>Проект: '.$row['project']; 
		}
		if ($row['product'] != '') {
			$result .= '<br/><br/>Продукт: '.$row['product'];
			//Преимущества продукта
			$result .= '<br/><br/>Преимущества продукта:';
			$product_sql = "select m.name as name, m.value as value, u.name as unit ";
			$product_sql .= "from iris_Product_Advantage m ";
			$product_sql .= "left join iris_AdvantageUnit u on u.ID=m.AdvantageUnitID ";
			$product_sql .= "where m.ProductID=:p_ProductID ";
			$product_sql .= "order by m.value desc";
			$product_statement = $con->prepare($product_sql);
			$product_statement->bindParam(':p_ProductID', $row['productid']);
			$product_statement->execute();
			$product_res = $product_statement->fetchAll();
			if ($product_res) {
				$result .= '<ul>';
				foreach ($product_res as $product_row) {
					$result .= '<li>';
					$result .= $product_row['name'].' ('.$product_row['value'].' '.$product_row['unit'].')';
					$result .= '</li>';
				}
				$result .= '</ul>';
			}
			else {
				$result .= '<br/><br/>';
			}

			//Возражения
			$result .= 'Возражения:';
			$disagree_sql = "select m.disagree as disagree, m.answer as answer ";
			$disagree_sql .= "from iris_Product_Disagree m ";
			$disagree_sql .= "where m.ProductID=:p_ProductID ";
			$disagree_sql .= "order by m.disagree asc";
			$disagree_statement = $con->prepare($disagree_sql);
			$disagree_statement->bindParam(':p_ProductID', $row['productid']);
			$disagree_statement->execute();
			$disagree_res = $disagree_statement->fetchAll();
			if ($disagree_res) {
				$result .= '<ul>';
				foreach ($disagree_res as $disagree_row) {
					$result .= '<li>';
					$result .= 'Возражение: '.$disagree_row['disagree'];
					$result .= '<br/>Ответ: '.$disagree_row['answer'];
					$result .= '</li>';
				}
				$result .= '</ul>';
			}
		}
		$result .= ''; 
		$result .= '</td>';
		
		$result .= '<td class="'.$col_class.'" width=80>';
		$result .= '<table width="100%"><tbody><tr>'; 
		$result .= '<td class="'.$col_class.'">Важность:</td>'; 
		$result .= '<td class="'.$col_class.'">'.$row['importance'].'</td>'; 
		$result .= '</tr>'; 
		$result .= '<tr>'; 
		$result .= '<td class="'.$col_class.'">Состояние:</td>'; 
		$result .= '<td class="'.$col_class.'">'.$row['state'].'</td>'; 
		$result .= '</tr>'; 
		$result .= '<tr>'; 
		$result .= '<td class="'.$col_class.'">Тип:</td>';
		$result .= '<td class="'.$col_class.'">'.$row['tasktype'].'</td>';
		$result .= '</tr>';
/*
		if ($row['tasktypecode'] == 'Meet') {
			$result .= '<tr>'; 
			$result .= '<td class="'.$col_class.'" colspan="2">'.$row['city'].', '.$row['address'].'</td>'; 
			$result .= '</tr>'; 
		}
		if (($row['tasktypecode'] == 'Meet')||($row['tasktypecode'] == 'Call')) {
			$result .= '<tr>'; 
			$result .= '<td class="'.$col_class.'" colspan="2">'.$row['aphone1'].'; '.$row['aphone2'].'; '.$row['aphone3'].'</td>'; 
			$result .= '</tr>'; 
		}
*/
		$result .= '</tbody></table>'; 
		$result .= '</tr>'; 
		
		
		$i++;
	}
	$result .= '</tbody></table>'; 
	
	
	$result .= $result_end;

	return $result;
}

?>