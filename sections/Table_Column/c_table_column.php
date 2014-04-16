<?php
//********************************************************************
// Вкладка "Колонки таблицы". Карточка.
//********************************************************************

function Table_Column_onAfterInsert($p_record_id)
{
	$con = GetConnection();

	//Данные о колонке
	list($table_id, $col_name, $col_code, $col_type_id, $notnull, $def, 
		$key_code, $key_table_id, $key_delete_id, $key_update_id, $pk_code, $idx_code) = 
		GetFieldValuesByID('Table_Column', $p_record_id, 
		array('TableID', 'Name', 'Code', 'ColumnTypeID', 'isNotNull', 'DefaultValue',
		'fkname', 'fktableid', 'ondeleteid', 'onupdateid', 'pkname', 'indexname'), 
		$con);
	//Название таблицы
	$table_code = GetFieldValueByID('Table', $table_id, 'Code', $con);
	//Код колонки
	$col_type = GetFieldValueByID('ColumnType', $col_type_id, 'Code', $con);

	//Название таблицы ключа
	$key_table_code = GetFieldValueByID('Table', $key_table_id, 'Code', $con);
	//Действие при удалении
	$key_delete_code = GetFieldValueByID('ConstraintAction', $key_delete_id, 'Code', $con);
	//Действие при обновлении
	$key_update_code = GetFieldValueByID('ConstraintAction', $key_update_id, 'Code', $con);
	

	//Составим запрос добавления колонки
	$sql = 'alter table "'.$table_code.'" add "'.$col_code.'" '.$col_type.';';
	$statement = $con->prepare($sql);
	$statement->execute();
	log_sql($sql, '', 'write');
	
	//Комментарий
	$sql = 'comment on column "'.$table_code.'"."'.$col_code.'" is \''.$col_name.'\';';
	$statement = $con->prepare($sql);
	$statement->execute();
	log_sql($sql, '', 'write');
	
	//Обязательная
	if ($notnull) {
		$sql = 'alter table "'.$table_code.'" alter column "'.$col_code.'" set not null;';
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql, '', 'write');
	}

	//По умолчанию
	if (!IsEmptyValue($def)) {
		if ((substr_count($col_type, "numb") > 0) || (substr_count($col_type, "int") > 0)) {
			$sql = 'alter table "'.$table_code.'" alter column "'.$col_code.'" set default '.$def;
		}
		else {
			$sql = 'alter table "'.$table_code.'" alter column "'.$col_code.'" set default \''.$def.'\'';
		}
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
	}

	//Внешний ключ
	if ((!IsEmptyValue($key_table_code)) && (!IsEmptyValue($key_code)) && 
	(!IsEmptyValue($key_update_code)) && (!IsEmptyValue($key_delete_code))) {
		$sql = 'alter table "'.$table_code.'" add constraint '.$key_code.' foreign key ('.$col_code.') ';
		$sql .= 'references '.$key_table_code.'(id) match simple ';
		$sql .= 'on update '.$key_update_code.' on delete '.$key_delete_code;
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str('Ошибка при создании внешнего ключа в физической структуре БД<br/>');
		}
	}

	//Первичный ключ
	if (!IsEmptyValue($pk_code)) {
		$sql = 'alter table "'.$table_code.'" add constraint '.$pk_code.' primary key ('.$col_code.') ';
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str('Ошибка при создании первичного ключа в физической структуре БД<br/>');
		}
	}

	//Индекс
	if (!IsEmptyValue($idx_code)) {
		$sql = 'create index "'.$idx_code.'" on "'.$table_code.'" using btree ('.$col_code.')';
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str('Ошибка при создании индекса в физической структуре БД<br/>');
		}
	}
	
	

	if ($statement->errorCode() != '00000') {
		$result['Error'] .= json_encode_str('Ошибка при добавлении колонки в физическую структуру БД');
	}
	else {
		$result['Result'] = 'ok';
	}
	
	return $result;
}


function Table_Column_onAfterUpdate($p_record_id, $p_values)
{
	$con = GetConnection();

	//Javascript передает сюда уже не ассоциативные массивы, а объекты, преобразуем их в ассоциативные массивы
	$old_values = FiledValuesToAssoc(json_decode($p_values));

	//Получим текущие значения полей
	$new_values = GetFormatedFieldValuesByID('Table_Column', $p_record_id, array(
		'tableid', 
		'name', 
		'code', 
		'description',
		'columntypeid',
		'isnotnull', 
		'defaultvalue', 
		'fkname', 
		'fktableid', 
		'ondeleteid', 
		'onupdateid', 
		'pkname', 
		'indexname'
	),	$con);
	$new_values = $new_values['FieldValues'];

	list($table_name) = GetFieldValuesByID('Table', GetArrayValueByName($new_values, 'tableid'), array('code'), $con);
	$new_column_name = GetArrayValueByName($new_values, 'code');
	$old_column_name = GetArrayValueByName($old_values, 'code');
	$new_column_comment = GetArrayValueByName($new_values, 'name');
	$old_column_comment = GetArrayValueByName($old_values, 'name');
	$new_column_type = GetArrayValueByName($new_values, 'columntypeid');
	$old_column_type = GetArrayValueByName($old_values, 'columntypeid');
	$new_column_null = GetArrayValueByName($new_values, 'isnotnull');
	$old_column_null = GetArrayValueByName($old_values, 'isnotnull');
	$new_default = GetArrayValueByName($new_values, 'defaultvalue');
	$old_default = GetArrayValueByName($old_values, 'defaultvalue');

	list($new_column_type_name) = GetFieldValuesByID('ColumnType', $new_column_type, array('code'), $con);
	list($new_fktable_name) = GetFieldValuesByID('Table', GetArrayValueByName($new_values, 'fktableid'), array('code'), $con);
	list($new_ondelete_code) = GetFieldValuesByID('ConstraintAction', GetArrayValueByName($new_values, 'ondeleteid'), array('code'), $con);
	list($new_onupdate_code) = GetFieldValuesByID('ConstraintAction', GetArrayValueByName($new_values, 'onupdateid'), array('code'), $con);
	
	//Если изменили код (название в БД)
	if ($old_column_name != $new_column_name) {
		$sql = "alter table ".$table_name." rename column ".$old_column_name." TO ".$new_column_name;
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql, '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str('Ошибка при изменении названия колонки в БД. '.$sql);
		}
	}

	//Если изменили название (комментарий)
	if ($new_column_comment != $old_column_comment) {
		$sql = "COMMENT ON COLUMN ".$table_name.".".$new_column_name." IS '".
			json_decode_str($new_column_comment)."';";
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str('Ошибка при изменении комментария колонки в БД. ');
		}
	}

	//Если изменили тип
	if ($new_column_type != $old_column_type) {
		$sql = "alter table ".$table_name." alter column ".$new_column_name." type ".$new_column_type_name.";";
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql, '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str("Ошибка при изменении типа колонки в БД. ");
		}
	}

	//Если изменили not null
	if ($new_column_null != $old_column_null) {
		$sql = "alter table ".$table_name." alter column ".$new_column_name;
		$sql .= 1 == $new_column_null ? " set " : " drop ";
		$sql .= "not null;";
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str("Ошибка при изменении свойства NOT NULL колонки в БД. ");
		}
	}

	//Если изменили defaultvalue
	if ($new_default != $old_default) {
		//TODO: сделать анализ на тип и делать кавычки или нет в зависимости от типа
		$sql = "alter table ".$table_name." alter column ".$new_column_name;
		$sql .= '' != $new_default ? " set default '".$new_default."';" : " drop default;";
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql, '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str("Ошибка при изменении свойства DEFAULT колонки в БД. ");
		}
	}

	//Если изменили внешний ключ, то удалим его и создадим снова
	if ((GetArrayValueByName($new_values, 'fkname') != GetArrayValueByName($old_values, 'fkname'))
	|| (GetArrayValueByName($new_values, 'fktableid') != GetArrayValueByName($old_values, 'fktableid'))
	|| (GetArrayValueByName($new_values, 'ondeleteid') != GetArrayValueByName($old_values, 'ondeleteid'))
	|| (GetArrayValueByName($new_values, 'onupdateid') != GetArrayValueByName($old_values, 'onupdateid'))) {
		$sql = "alter table ".$table_name." drop constraint ".GetArrayValueByName($old_values, 'fkname');
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str("Ошибка при удалении внешнего ключа в БД. ");
		}

		if ('' != GetArrayValueByName($new_values, 'fkname')) {
			$sql = "alter table ".$table_name." add constraint ".GetArrayValueByName($new_values, 'fkname').
				" foreign key (".$new_column_name.") ".
				"references ".$new_fktable_name."(id) match simple ".
				"on update ".$new_onupdate_code." on delete ".$new_ondelete_code.";" ;
			$statement = $con->prepare($sql);
			$statement->execute();
			log_sql($sql, '', 'write');
			//Если ошибка
			if ($statement->errorCode() != '00000') {
				$result['Error'] .= json_encode_str("Ошибка при добавлении внешнего ключа в БД. ");
			}
		}
	}

	//Если изменили первичный ключ, то удалим его и создадим снова
	if (GetArrayValueByName($new_values, 'pkname') != GetArrayValueByName($old_values, 'pkname')) {
		$sql = "alter table ".$table_name." drop constraint ".GetArrayValueByName($old_values, 'pkname').";";
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql, '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str("Ошибка при удалении первичного ключа в БД. ");
		}

		if ('' != GetArrayValueByName($new_values, 'pkname')) {
			$sql = "alter table ".$table_name." add constraint ".GetArrayValueByName($new_values, 'pkname').
				" primary key (".$new_column_name.");";
			$statement = $con->prepare($sql);
			$statement->execute();
			log_sql($sql, '', 'write');
			//Если ошибка
			if ($statement->errorCode() != '00000') {
				$result['Error'] .= json_encode_str("Ошибка при добавлении первичного ключа в БД. ");
			}
		}
	}

	//Если изменили индекс, то удалим его и создадим снова
	if (GetArrayValueByName($new_values, 'indexname') != GetArrayValueByName($old_values, 'indexname')) {
		$sql = "drop index ".GetArrayValueByName($old_values, 'indexname');
		$statement = $con->prepare($sql);
		$statement->execute();
		log_sql($sql.';', '', 'write');
		//Если ошибка
		if ($statement->errorCode() != '00000') {
			$result['Error'] .= json_encode_str("Ошибка при удалении индекса в БД. ");
		}

		if ('' != GetArrayValueByName($new_values, 'indexname')) {
			$sql = "create index ".GetArrayValueByName($new_values, 'indexname').
				" on ".$table_name." using btree (".$new_column_name.");";
			$statement = $con->prepare($sql);
			$statement->execute();
			log_sql($sql, '', 'write');
			//Если ошибка
			if ($statement->errorCode() != '00000') {
				$result['Error'] .= json_encode_str("Ошибка при добавлении индекса в БД. ");
			}
		}
	}
	
	//TODO: добавить обработчики изменения других свойств колонки
	$result['Result'] = "OK";
	return $result;
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


$func = stripslashes($_POST['_func']);

if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
	switch ($func) {
		case 'onAfterInsert':
			$response = Table_Column_onAfterInsert(stripslashes($_POST['_record_id']));
			break;
		case 'onAfterUpdate':
			$response = Table_Column_onAfterUpdate(stripslashes($_POST['_record_id']), $_POST['_values']);
			break;
			
		default:
			$response = 'Неверное имя функции: '.$func;
	}
}

echo json_encode($response);

?>