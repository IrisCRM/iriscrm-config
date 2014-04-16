<?php

//Запрос для получия перечня дел
function r_Task_GetTaskSelect()
{	
	$select_sql = "select t.name as name, t.startdate as startdate, t.finishdate as finishdate, ";
	$select_sql .= "ts.name as state, ts.code as statecode, ti.name as importance, ti.code as importancecode, tt.name as tasktype, tt.code as tasktypecode, ";
	$select_sql .= "tr.name as shortresult, t.Result as fullresult, ";
	$select_sql .= "taccount.name as account, taccount.phone1 as aphone1, taccount.phone2 as aphone2, taccount.phone3 as aphone3, taccount.fax as afax, taccount.email as aemail, taccount.web as aweb, taccount.address as aaddress, tcity.name as acity, ";
	$select_sql .= "tcontact.name as contact, tcontact.phone1 as cphone1, tcontact.phone2 as cphone2, tcontact.email as cemail, tcontact.skype as cskype, tcontact.icq as cicq, tcontact.google as cgoogle, tcontact.address as caddress, ";
	$select_sql .= "tproject.name as project, ";
	$select_sql .= "tproduct.name as product, tproduct.id as productid ";
	$select_sql .= "from iris_Task t ";
	$select_sql .= "left join iris_TaskState ts on ts.ID=t.TaskStateID ";
	$select_sql .= "left join iris_TaskImportance ti on ti.ID=t.TaskImportanceID ";
	$select_sql .= "left join iris_TaskType tt on tt.ID=t.TaskTypeID ";
	$select_sql .= "left join iris_TaskResult tr on tr.ID=t.TaskResultID ";
	$select_sql .= "left join iris_Account taccount on taccount.ID=t.AccountID ";
	$select_sql .= "left join iris_City tcity on tcity.ID=taccount.CityID ";
	$select_sql .= "left join iris_Contact tcontact on tcontact.ID=t.ContactID ";
	$select_sql .= "left join iris_Project tproject on tproject.ID=t.ProjectID ";
	$select_sql .= "left join iris_Product tproduct on tproduct.ID=t.ProductID ";
	return $select_sql;
}

?>