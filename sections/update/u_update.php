<?php
//********************************************************************
// Раздел "Обновление"
//********************************************************************

//TODO: реализовать добавление default value

function GetUpdateForm()
{
	//Проверим, имеет ли текущий пользователь админские права
	if (!IsUserInAdminGroup()) {
		$result['error'] = json_encode_str('Вы должны иметь права админстратора для доступа к этой функции.');
		return $result;
	}
	
	//Текущая версия ядра
	
	//Текущая версия конфигурации
	$result = '';
	
//	$result .= '<div id="update_section" style="position: absolute;">';
	$result .= '<table id="update_section">';
	$result .= '<tbody>';
	$result .= '<tr><td>';
	
	$result .= '<form name="u_update_send" method="POST" onsubmit="return false;">';
	
	$result .= '<table width="100%" border="0" align="left" valign="top">';
	$result .= '<tbody>';

	$result .= '<tr class="info">';
	$result .= '<td class="info" colspan=2>';
	$result .= 'Будьте аккуратны, выполняя обновление!';
	$result .= '</td>';
	$result .= '</tr>';
	$result .= '<tr class="info">';
	$result .= '<td class="info" colspan=2>';
	$result .= '<strong>Убедитесь, что у Вас имеются свежие резервные копии <br>базы данных и файлов системы.</strong>';
	$result .= '</td>';
	$result .= '</tr>';
	$result .= '<tr class="info">';
	$result .= '<td class="info" colspan=2>';
	$result .= 'При обновлении данные из указанной базы переносятся в текущую базу.<br>';
//	$result .= 'С помощью этого механизма Вы можете выполнять и репликацию данных.';
	$result .= '</td>';
	$result .= '</tr>';
	
/*	$result .= '<tr>';
	$result .= '<td colspan="2">';
	$result .= '<h2 style="margin: 20px 0px 10px 0px;">Версия</h2>';
	$result .= '</td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Версия ядра</td>';
	$result .= '<td></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Версия конфигурации</td>';
	$result .= '<td></td>';
	$result .= '</tr>';*/
	$result .= '<tr>';
	$result .= '<td colspan="2">';
	$result .= '<h2 style="margin: 20px 0px 10px 0px;">Соединение с второй базой</h2>';
	$result .= '</td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Тип соединения</td>';
	$result .= '<td><input name="dbtype" type="text" value="pgsql" class="edtText" title="Тип соединения с БД"></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Хост</td>';
	$result .= '<td><input name="dbhost" type="text" value="localhost" class="edtText" title="Сервер, на котором расположена СУБД"></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Порт</td>';
	$result .= '<td><input name="dbport" type="text" value="5432" class="edtText" title="Порт, который использует СУБД"></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Пользователь</td>';
	$result .= '<td><input name="dbuser" type="text" value="postgres" class="edtText" title="Пользователь СУБД"></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Пароль</td>';
	$result .= '<td><input name="dbpassword" type="password" value="postgres" class="edtText" title="Пароль пользователя СУБД"></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>База данных</td>';
	$result .= '<td><input name="dbname" type="text" value="iriscrm" class="edtText" title="Название базы данных, с которой Вы работали до обновления"></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td colspan="2">';
	$result .= '<h2 style="margin: 20px 0px 10px 0px;">Настройки</h2>';
	$result .= '</td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Время выполнения скрипта, секунд</td>';
	$result .= '<td><input name="extime" type="text" value="300" class="edtText" title="Увеличте этот параметр, если скрипт не успевает выполниться"></td>';
	$result .= '</tr>';
	$result .= '<tr style="display: none;">';
	$result .= '<td>Более свежие записи считать актуальными</td>';
	$result .= '<td><input name="freshrecords" type="checkbox" title="Обычно эту галочку нужно отмечать при репликации. При обновлении наоборот, лучше эту галочку снять, тогда данные из Вашей старой базы будут считаться актуальными, и перезапишут данные в новой базе."></td>';
	$result .= '</tr>';
	$result .= '<tr style="display: none;">';
	$result .= '<td>Обновлять данные о таблицах</td>';
	$result .= '<td><input name="freshtables" type="checkbox" title="Обычно эту галочку не надо устанавливать."></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>В какой базе вы находитесь сейчас</td>';
	$result .= '<td><select name="nowdb"/>'.
		'<option value="new" selected>В новой (пустой)</option>'.
		'<option value="old">В рабочей (старой)</option>'.
		'</select>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td>Вариант (направление) обновления</td>';
	$result .= '<td><select name="updatedirection"/>'.
		'<option value="fromnew" selected>Из новой (путой) базы в рабочую</option>'.
		'<option value="fromold">Из рабочей (старой) базы в новую</option>'.
		'</select>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td colspan="2"><input name="setuptables" type="button" onclick="u_update_setuptables()" value="Далее..." class="button" title="Нажмите, чтобы установить соединение с БД и указать дополнительные настройки."></td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td colspan="2">';
	$result .= '<div id="u_update_tables">';
	$result .= '</div>';
	$result .= '</td>';
	$result .= '</tr>';
	$result .= '<tr>';
	$result .= '<td colspan="2">';
	$result .= '<div id="u_update_output">';
	$result .= '</div>';
	$result .= '</td>';
	$result .= '</tr>';
	$result .= '</tbody>';
	$result .= '</table>';

	$result .= '</form>';

	$result .= '</td>';
	$result .= '</tr>';
	$result .= '</tbody>';
	$result .= '</table>';
	
//	$result .= '</div>';
	return array('html' => json_encode_str($result));
}



//Обновление - центральная функция раздела
function SetupUpdateTables($dbtype, $dbhost, $dbport, $dbuser, $dbpassword, $dbname, $extime=300, $freshrecords=false, $freshtables=false, $nowdb='new', $source='fromnew')
{
	$result = array('html' => '', 'error' => '');
	
	//Соединение со старой базой
	$con_new = GetConnection();
	
	//Проверим, имеет ли текущий пользователь админские права
	if (!IsUserInAdminGroup($con_new)) {
		$result['error'] .= json_encode_str('Вы должны иметь права админстратора для доступа к этой функции.');
		return $result;
	}
	
	try {
		$con_old = new PDO($dbtype.':host='.$dbhost.' dbname='.chr(39).$dbname.chr(39).((int)$dbport > 0 ? ' port='.$dbport : ''), $dbuser, $dbpassword, array(PDO::ATTR_PERSISTENT => true));
	}
	catch (PDOException $e) {
		$result['error'] .= "База ".$dbname.": ".json_encode_str('Не удается соединиться с базой данных.<br>'.$e->getMessage().'<br>'.chr(10));
		return $result;
	}

	$defaultnotchecked['fromold'] = array(
		'',
		'iris_accessdefault',
		'iris_accessrecord',
	);

	$defaultnotchecked['fromnew'] = array('');

	$defaultchecked['fromnew'] = array('');

	$defaultaddnew = array(
		'',
		'iris_aggregatefunction',
		'iris_columntype',
		'iris_constraintaction',
		'iris_emailtype',
		'iris_importtype',
		'iris_printform',
		'iris_report',
		'iris_report_access',
		'iris_report_column',
		'iris_report_filter',
		'iris_report_parameter',
		'iris_report_table',
		'iris_section',
		'iris_systemvariable',
		'iris_table',
		'iris_table_column',
		'iris_tablegroup',
		'iris_table_tablegroup',
		'iris_variabletype'
	);
	
	if ('fromnew' == $source) {
		$con = $con_old;
		$con_old = $con_new;
		$con_new = $con;
	}
	
	/////////Синхронизируем структуру БД/////////
	//Получим все таблицы старой базы
	$tables_array_old = $con_old->query("select code, name from iris_table order by code")->fetchAll(PDO::FETCH_ASSOC);
		
	$result['html'] .= '<form name="u_update_tables">';
	$result['html'] .= '<p>Отметьте таблицы, данные из которых необходимо перенести из текущей базы во вторую.<br>';
	$result['html'] .= 'Если Вы не знаете, какие таблицы надо перенести, то оставьте список таблиц без изменений.</p>';
	$result['html'] .= '<table><thead>';
	$result['html'] .= '<tr>';
	$result['html'] .= '<th align="left">Добавить<br>новые<br>записи</th>';
	$result['html'] .= '<th align="left">Обновить<br>старые<br>записи</th>';
	$result['html'] .= '<th align="left">Таблица</th>';
	$result['html'] .= '<th align="left">Название</th>';
	$result['html'] .= '</tr>';
	$result['html'] .= '</thead>';
	$result['html'] .= '<tbody>';
	$result['html'] .= '<tr>';
	$result['html'] .= '<td><input name="selectallinsert" type="checkbox" title="Выделить все/снять выделение" onclick="'.
		'var form_tables = document.getElementById(\'u_update_tables\').getElementsByTagName(\'form\')[0]; '.
		'var tables = Form.getElements(form_tables); '.
		'for (var i=0; i<tables.length-1; i++) { '.
		'  if (tables[i].name.substr(tables[i].name.length-4) == \'_ins\') { '.
		'    if (form_tables.selectallinsert.value == \'on\') { '.
		'      tables[i].setAttribute(\'checked\', \'\'); '.
		'    } '.
		'    else { '.
		'      tables[i].removeAttribute(\'checked\'); '.
		'    } '.
		'  } '.
		'} '.
		'"></td>';
	$result['html'] .= '<td><input name="selectallupdate" type="checkbox" title="Выделить все/снять выделение" onclick="'.
		'var form_tables = document.getElementById(\'u_update_tables\').getElementsByTagName(\'form\')[0]; '.
		'var tables = Form.getElements(form_tables); '.
		'for (var i=0; i<tables.length-1; i++) { '.
		'  if (tables[i].name.substr(tables[i].name.length-4) == \'_upd\') { '.
		'    if (form_tables.selectallupdate.value == \'on\') { '.
		'      tables[i].setAttribute(\'checked\', \'\'); '.
		'    } '.
		'    else { '.
		'      tables[i].removeAttribute(\'checked\'); '.
		'    } '.
		'  } '.
		'} '.
		'"></td>';
	$result['html'] .= '<td align="left"></td>';
	$result['html'] .= '<td align="left"></td>';
	$result['html'] .= '</tr>';
	foreach ($tables_array_old as $table) {
		$defaultnotchecked['fromnew'][] = array_search($table['code'], $defaultchecked['fromnew']) ? '' : $table['code'];
		$result['html'] .= '<tr>';
		$result['html'] .= '<td><input name="'.$table['code'].'_ins" type="checkbox" '.(array_search($table['code'], $defaultaddnew) || !array_search($table['code'], $defaultnotchecked[$source]) ? 'checked' : '').' title="Синхронизировать данные"></td>';
		$result['html'] .= '<td><input name="'.$table['code'].'_upd" type="checkbox" '.(!array_search($table['code'], $defaultnotchecked[$source]) ? 'checked' : '').' title="Синхронизировать данные"></td>';
		$result['html'] .= '<td>'.$table['code'].'</td>';
		$result['html'] .= '<td>'.$table['name'].'</td>';
		$result['html'] .= '</tr>';
	}
	$result['html'] .= '<tr>';
	$result['html'] .= '<td colspan="4"><input name="send" type="button" onclick="u_update_start()" value="Выполнить обновление" class="button" title="Обновление невозможно будет откаить назад. Убедитесь, что у Вас имеется резервная копия."></td>';
	$result['html'] .= '</tr>';
	$result['html'] .= '</tbody></table>';
	$result['html'] .= '</form>';
	$result['html'] = json_encode_str($result['html']);
	return $result;
}


//Обновление - центральная функция раздела
function StartUpdate($dbtype, $dbhost, $dbport, $dbuser, $dbpassword, $dbname, $extime=300, $freshrecords=false, $freshtables=false, $source='fromnew', $tables=array())
{
	$filename = Getpath().'/temp/update.sql';
	
	//Сформируем список таблиц для обновления
	$tables_array = json_decode($tables);
	$update_tables = array();
	$insert_tables = array();
	foreach ($tables_array as $elem) {
		$name = iris_substr($elem->name, 0, iris_strlen($elem->name)-4);
		$type = iris_substr($elem->name, iris_strlen($elem->name)-3, iris_strlen($elem->name));
		if ('upd' == $type) {
			$update_tables[$name] = $elem->value;
		}
		else
		if ('ins' == $type) {
			$insert_tables[$name] = $elem->value;
		}
	}
	
	$result = array('html' => '', 'error' => '');
	
	//Проверим, имеет ли текущий пользователь админские права
	if (!IsUserInAdminGroup()) {
		$result['error'] = json_encode_str('Вы должны иметь права админстратора для доступа к этой функции.');
		return $result;
	}
	
	//Соединение со старой базой
	$con_new = GetConnection();

	try {
		$con_old = new PDO($dbtype.':host='.$dbhost.' dbname='.chr(39).$dbname.chr(39).((int)$dbport > 0 ? ' port='.$dbport : ''), $dbuser, $dbpassword, array(PDO::ATTR_PERSISTENT => true));
	}
	catch (PDOException $e) {
		$result['error'] .= "База ".$dbname.": ".json_encode_str('Не удается соединиться с базой данных.<br>'.$e->getMessage().'<br>'.chr(10));
		return $result;
	}

	if ('fromnew' == $source) {
		$con = $con_old;
		$con_old = $con_new;
		$con_new = $con;
	}
	
	
	$sql1_1 = $sql1_2 = $sql1_3 = $sql1 = $sql2 = $sql3 = $sql4 = $sql5 = " ";

	ini_set('max_execution_time', $extime);
	set_time_limit($extime);
	
	/////////Синхронизируем структуру БД/////////
	//Получим все таблицы старой базы
	$tables_array_old = $con_old->query("select code, name from iris_table order by dictionary, sectionid")->fetchAll(PDO::FETCH_ASSOC);
	//Получим все таблицы новой базы
	$tables_array_new = $con_new->query("select code from iris_table")->fetchAll(PDO::FETCH_ASSOC);
	//Найдем таблицы старой базы, которых нет в новой базе и создадим их
	$select_columns_query = "select ".
		"t.code as tablecode, ".
		"tc.code as code, ".
		"tc.name as name, ".
		"ct.code as columntype, ".
//		"tc.description as description, ".
		"tc.fkname as fkname, ".
		"fktab.code as fktablecode, ".
//		"tc.isprimary as isprimary, ".
		"tc.pkname as pkname, ".
		"ca1.code as delconstrcode, ".
		"ca2.code as updconstrcode, ".
		"tc.isnotnull as isnotnull, ".
		"tc.defaultvalue as defaultvalue, ".
		"tc.indexname as indexname ".
		"from iris_table_column tc ".
		"left join iris_table t on tc.tableid = t.id ".
		"left join iris_columntype ct on tc.columntypeid = ct.id ".
		"left join iris_table fktab on fktab.id = tc.fktableid ".
		"left join iris_constraintaction ca1 on ca1.id = tc.ondeleteid ".
		"left join iris_constraintaction ca2 on ca2.id = tc.onupdateid ";
	foreach ($tables_array_old as $table_old) {
		$exist = false;
		foreach ($tables_array_new as $table_new) {
			if ($table_old['code'] == $table_new['code']) {
				$exist = true;
				break;
			}
		}
		//Список колонок с описанием всех свойств
		$table_columns_query = $con_old->query($select_columns_query.
			"where t.code = '".$table_old['code']."'");
		$errorCode = $con_old->errorInfo();
		if ('00000' != $errorCode[0]) {
			$result['error'] .= json_encode_str("База ".$dbname.": ".$errorCode[2].'<br>'.chr(10));
			return $result;
		}
		$table_columns = $table_columns_query->fetchAll(PDO::FETCH_ASSOC);
		//Если таблицы в новой базе нет, то создадим ее
		if (!$exist) {
			$sql_column = "";
			$sql_pk = "";
			$sql_fk = "";
			$sql_comment = "";
			$sql_index = "";
			$sql_comment .= "COMMENT ON TABLE ".$table_old['code']." IS '".$table_old['name']."';".chr(10);
			foreach ($table_columns as $col) {
				if ('id' == $col['code']) {
					$sql_column .= "\"".$col['code']."\" ".
						$col['columntype'].
						($col['isnotnull'] ? " NOT NULL" : "").
						('' != $col['defaultvalue'] ? " DEFAULT ".$col['defaultvalue'] : "").
						", ".chr(10);
					$sql_pk .= $col['pkname'] ? "CONSTRAINT ".$col['pkname']." PRIMARY KEY (\"".$col['code']."\"),".chr(10) : "";
					$sql_fk .= $col['fkname'] ? "CONSTRAINT ".$col['fkname']." FOREIGN KEY (\"".$col['code']."\") REFERENCES ".$col['fktablecode']."(id) MATCH SIMPLE ON UPDATE ".$col['updconstrcode']." ON DELETE ".$col['delconstrcode'].",".chr(10) : "";
					$sql_comment .= "COMMENT ON COLUMN ".$table_old['code'].".\"".$col['code']."\" IS '".$col['name']."';".chr(10);
					$sql_index .= $col['indexname'] ? "CREATE INDEX ".$col['indexname']." ON ".$table_old['code']." USING btree (\"".$col['code']."\");".chr(10) : "";
				}
				else {
					$sql1_3 .= "ALTER TABLE ".$col['tablecode']." ADD COLUMN \"".$col['code']."\" ".$col['columntype'].";".chr(10);
					$sql1_3 .= $col['defaultvalue'] ? "ALTER TABLE ".$col['tablecode']." ALTER COLUMN \"".$col['code']."\" SET DEFAULT ".$col['defaultvalue'].";".chr(10) : "";
					$sql1_3 .= $col['pkname'] ? "ALTER TABLE ".$col['tablecode']." ADD CONSTRAINT ".$col['pkname']." PRIMARY KEY (\"".$col['code']."\");".chr(10) : "";
					$sql1_3 .= $col['fkname'] ? "ALTER TABLE ".$col['tablecode']." ADD CONSTRAINT ".$col['fkname']." FOREIGN KEY (\"".$col['code']."\") REFERENCES ".$col['fktablecode']."(id) MATCH SIMPLE ON UPDATE ".$col['updconstrcode']." ON DELETE ".$col['delconstrcode'].";".chr(10) : "";
					$sql1_3 .= $col['indexname'] ? "CREATE INDEX ".$col['indexname']." ON ".$col['tablecode']." USING btree (\"".$col['code']."\");".chr(10) : "";
					$sql1_3 .= "COMMENT ON COLUMN ".$col['tablecode'].".\"".$col['code']."\" IS '".$col['name']."';".chr(10);
					//Внимание, sql5!
					$sql5 .= $col['isnotnull'] ? "ALTER TABLE ".$col['tablecode']." ALTER COLUMN \"".$col['code']."\" SET NOT NULL;".chr(10) : "";
				}
			}
			$sql1_1 .= "CREATE TABLE ".$table_old['code']." ( ".chr(10);
			$str = $sql_column.$sql_pk.$sql_fk;
			$sql1_1 .= iris_substr($str, 0, iris_strlen($str)-2).chr(10);
			$sql1_1 .= " );".chr(10);
			$sql1_1 .= $sql_comment.$sql_index;
		}
		//А если таблица уже существует, то проверим, есть ли в ней все колонки. Если нет, то добавим
		else {
			//Найдем колонки, которых еще нет в новой базе и создадим их
			//Т.к. в новой базе инфы о доп таблицах еще нет (и мы их уже синхронизировали, они нам уже не интересны), 
			//то проходимся только по имеющимся таблицам, состав колонок которых различен
			//Будем только добавлять новые колонки. Изменения существующих отслеживать не будем!
			$table_columns_new_query = $con_new->query($select_columns_query.
				"where t.code = '".$table_old['code']."'");
			$errorCode = $con_new->errorInfo();
			if ('00000' != $errorCode[0]) {
				$result['error'] .= json_encode_str("Новая база: ".$errorCode[2].'<br>'.chr(10));
				return $result;
			}
			$table_columns_new = $table_columns_new_query->fetchAll(PDO::FETCH_ASSOC);
			foreach ($table_columns as $column_old) {
				$exist = false;
				foreach ($table_columns_new as $column_new) {
					if ($column_old['code'] == $column_new['code']) {
						$exist = true;
						break;
					}
				}
				//Если колонки в новой базе нет, то создадим ее
				if (!$exist) {
					$sql1_2 .= "ALTER TABLE ".$column_old['tablecode']." ADD COLUMN \"".$column_old['code']."\" ".$column_old['columntype'].";".chr(10);
					$sql1_2 .= $column_old['defaultvalue'] ? "ALTER TABLE ".$column_old['tablecode']." ALTER COLUMN \"".$column_old['code']."\" SET DEFAULT ".$column_old['defaultvalue'].";".chr(10) : "";
					$sql1_2 .= $column_old['pkname'] ? "ALTER TABLE ".$column_old['tablecode']." ADD CONSTRAINT ".$column_old['pkname']." PRIMARY KEY (\"".$column_old['code']."\");".chr(10) : "";
					$sql1_2 .= $column_old['fkname'] ? "ALTER TABLE ".$column_old['tablecode']." ADD CONSTRAINT ".$column_old['fkname']." FOREIGN KEY (\"".$column_old['code']."\") REFERENCES ".$column_old['fktablecode']."(id) MATCH SIMPLE ON UPDATE ".$column_old['updconstrcode']." ON DELETE ".$column_old['delconstrcode'].";".chr(10) : "";
					$sql1_2 .= $column_old['indexname'] ? "CREATE INDEX ".$column_old['indexname']." ON ".$column_old['tablecode']." USING btree (\"".$column_old['code']."\");".chr(10) : "";
					$sql1_2 .= "COMMENT ON COLUMN ".$column_old['tablecode'].".\"".$column_old['code']."\" IS '".$column_old['name']."';".chr(10);
					//Внимание, sql5!
					$sql5 .= $column_old['isnotnull'] ? "ALTER TABLE ".$column_old['tablecode']." ALTER COLUMN \"".$column_old['code']."\" SET NOT NULL;".chr(10) : "";
				}
			}
			
		}
	}
	$sql1 = $sql1_1.$sql1_2.$sql1_3;
	//TODO: Сделать также отслеживание изменений по ходу изменения iris_table_column и вставлять эти скрипты в этот скрипт
	//Пишем скрипт в файл
	if (!file_put_contents($filename, $sql1)) {
		$result['error'] .= json_encode_str('Ошибка записи в файл '.$filename.' (1)<br>'.chr(10));
	}

	/////////Синхронизируем данные/////////	
	//Получим not null колонки, запомним их
	$not_null_sql  = "select a.attname as field, c.relname as table, CASE WHEN a.attnotnull THEN 1 ELSE 0 END AS not_null ";
	$not_null_sql .= "FROM pg_class c ";
	$not_null_sql .= "LEFT OUTER JOIN pg_namespace n ON c.relnamespace=n.oid, ";
	$not_null_sql .= "pg_attribute a ";
	$not_null_sql .= "WHERE (n.nspname = 'public' OR n.oid IS NULL) ";
	$not_null_sql .= "AND a.attrelid = c.oid AND c.relname in (select code from iris_table) ";
	$not_null_sql .= "AND a.attnum > 0 ";
	$not_null_sql .= "AND not a.attname like '%pg.dropped.%' ";
	$not_null_sql .= "and a.attname <> 'id' ";
	$not_null_sql .= "and a.attnotnull = true ";
	$not_null_query = $con_new->query($not_null_sql);
	$errorCode = $con_new->errorInfo();
	if ('00000' != $errorCode[0]) {
		$result['error'] .= json_encode_str("Новая база: ".$errorCode[2].'<br>'.chr(10));
		return $result;
	}
	$not_null_columns = $not_null_query->fetchAll(PDO::FETCH_ASSOC);
	foreach ($not_null_columns as $row) {
		$sql2 .= 'ALTER TABLE '.$row['table'].' ALTER COLUMN '.$row['field'].' DROP NOT NULL;'.chr(10);
		$sql5 .= 'ALTER TABLE '.$row['table'].' ALTER COLUMN '.$row['field'].' SET NOT NULL;'.chr(10);
	}
	if (!file_put_contents($filename, $sql2, FILE_APPEND)) {
		$result['error'] .= json_encode_str('Ошибка записи в файл '.$filename.' (2)<br>'.chr(10));
	}
	
	//Вставим новые id
	$tables_array = $con_old->query("select code from iris_table")->fetchAll(PDO::FETCH_ASSOC);
	foreach ($tables_array as $table) {		
		$table_ids_query = $con_old->query("select id from ".$table['code']);
		$errorCode = $con_old->errorInfo();
		if ('00000' != $errorCode[0]) {
			$result['error'] .= json_encode_str("База ".$dbname.": ".$errorCode[2].'<br>'.chr(10));
			return $result;
		}
		$table_ids_array = $table_ids_query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($table_ids_array as $old_id) {
			$exists_id = array();
			$exists_id_query = $con_new->query("select exists (select 1 from ".$table['code']." where id='".$old_id['id']."') as flag");
			$errorCode = $con_new->errorInfo();
			if ('00000' != $errorCode[0]) {
				//Просто проигнорируем, т.к. таблица еще не создана
			}
			else {
				$exists_id = current($exists_id_query->fetchAll(PDO::FETCH_ASSOC));
			}
			if ($exists_id['flag'] != 1) {
				//Запоминаем новые id
				$new_guids[$old_id['id']] = 1;
				
				//Если таблица отсутствует в списке, то пропустим ее
				if ('on' != $insert_tables[$table['code']]) {
					continue;
				}
				
				$sql = "insert into ".$table['code']." (id) values ('".$old_id['id']."');".chr(10);
//				$sql3 .= "insert into ".$table['code']." (id) values ('".$old_id['id']."');".chr(10);
				if (!file_put_contents($filename, $sql, FILE_APPEND)) {
					$result['error'] .= json_encode_str('Ошибка записи в файл '.$filename.' (3)<br>'.chr(10));
				}
			}
		}
	}
	
	//Скопируем данные
	foreach ($tables_array as $table) {
		//Если таблица отсутствует в списках, то пропустим ее
		if (('on' != $update_tables[$table['code']])
		&& ('on' != $insert_tables[$table['code']])) {
			continue;
		}
		//Получим список колонок данной таблицы
		$table_columns_query = $con_old->query("select T0.code as code from iris_table_column T0 left join iris_table T1 on T0.tableid = T1.id where T1.code = '".$table['code']."'");
		$errorCode = $con_old->errorInfo();
		if ('00000' != $errorCode[0]) {
			$result['error'] .= json_encode_str("База ".$dbname.": ".$errorCode[2].'<br>'.chr(10));
			return $result;
			continue;
		}
		$table_columns_array = $table_columns_query->fetchAll(PDO::FETCH_COLUMN);
		//Каждую колонку оборачиваем еще в ""
		for ($i=0; $i<count($table_columns_array); $i++) {
			$table_columns_array[$i] = '"'.$table_columns_array[$i].'"';
		}
		$columns_str = implode(', ', $table_columns_array);
		//Если в БД нет такой таблицы, то продолжим
		if ($columns_str == '') {
			continue;
		}
		//Запрос возвращает все значения таблицы
		$values_array_query = $con_old->query("select ".$columns_str." from ".$table['code']);
		$errorCode = $con_old->errorInfo();
		if ('00000' != $errorCode[0]) {
			$result['error'] .= json_encode_str("База ".$dbname.": ".$errorCode[2].'<br>'.chr(10));
			return $result;
			continue;
		}
		$values_array = $values_array_query->fetchAll(PDO::FETCH_NUM);
		$len = count($table_columns_array);
		foreach ($values_array as $value) {			
			$update_cmd_arr = array();
			$update_cmd = '';
			for ($i = 0; $i < $len; $i++) {
				if ($table_columns_array[$i] == '"id"') {
					$id_index = $i;
					continue;
				}
				$update_cmd_arr[] = $table_columns_array[$i]." = ".($value[$i] == '' ? "null" : "$"."quote$".$value[$i]."$"."quote$");
			}
			
			//Если не вставляли новые данные, то будем аккуратны
			//TODO: Условие излишнее
			if ('on' != $insert_tables[$table['code']]) {
				if (1 == $new_guids[$value[$id_index]]) {
					continue;
				}
			}
			
			//Если данные только вставляем, то обновим только новые id
			if (('on' == $insert_tables[$table['code']]) 
			&& ('on' != $update_tables[$table['code']])) {
				if (1 != $new_guids[$value[$id_index]]) {
					continue;
				}
			}
			
			$update_cmd = "update ".$table['code']." set ".implode(', ', $update_cmd_arr)." where id='".$value[$id_index]."';";
//			$sql4 .= $update_cmd.chr(10);
			$sql = $update_cmd.chr(10);
			if (!file_put_contents($filename, $sql, FILE_APPEND)) {
				$result['error'] .= json_encode_str('Ошибка записи в файл '.$filename.' (4)<br>'.chr(10));
			}
		}
	}
	
	if (!file_put_contents($filename, $sql5, FILE_APPEND)) {
		$result['error'] .= json_encode_str('Ошибка записи в файл '.$filename.' (5)<br>'.chr(10));
	}	
	
	$result['html'] = json_encode_str('Файл со скриптом обновления базы подготовлен.<br>'.
		'<h2><a target="_blank" href="config/sections/update/u_update.php?_func=DownloadUpdateScript">Скачать файл</a></h2>'.
		'<h3>Дальнейшие инструкции по обновлению</h3>'.
		'<ol>'.
		'<li>Проверьте, что у Вас имеются резервные копии базы данных и файлов системы.</li>'.
		'<li>Выполните данный скрипт на базе, в которую Вы переносите данные.</li>'.
		'<li>Если Вы делали настройку конфигурации системы для себя, то скопируйте изменения из файлов, которые Вы меняли.</li>'.
		'<li>Убедитесь, что перенесенные изменения работают корректно.</li>'.
		'</ol>'
	);
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
if ($_GET['_func'] == 'DownloadUpdateScript') {
	$func = $_GET['_func']; 
}


if (strlen($func) == 0) {
	$response = PrintError('Имя функции не задано');
}
else {
	
	switch ($func) {

	case 'GetUpdateForm':
		$response = GetUpdateForm();
		break;

	case 'StartUpdate':
		$response = StartUpdate(
			stripslashes($_POST['dbtype']),
			stripslashes($_POST['dbhost']),
			stripslashes($_POST['dbport']),
			stripslashes($_POST['dbuser']),
			stripslashes($_POST['dbpassword']),
			stripslashes($_POST['dbname']),
			stripslashes($_POST['extime']),
			stripslashes($_POST['freshrecords']),
			stripslashes($_POST['freshtables']),
			stripslashes($_POST['updatedirection']),
			stripslashes($_POST['tables'])
			);
		break;

	case 'SetupUpdateTables':
		$response = SetupUpdateTables(
			stripslashes($_POST['dbtype']),
			stripslashes($_POST['dbhost']),
			stripslashes($_POST['dbport']),
			stripslashes($_POST['dbuser']),
			stripslashes($_POST['dbpassword']),
			stripslashes($_POST['dbname']),
			stripslashes($_POST['extime']),
			stripslashes($_POST['freshrecords']),
			stripslashes($_POST['freshtables']),
			stripslashes($_POST['nowdb']),
			stripslashes($_POST['updatedirection'])
			);
		break;

	case 'DownloadUpdateScript':
		//Проверим, имеет ли текущий пользователь админские права
		if (!IsUserInAdminGroup()) {
			$response['html'] = json_encode_str('!Вы должны иметь права админстратора для доступа к этой функции.');
			echo json_encode($response);
		}
		
		$file_name = 'update.sql';
		$file_cap = 'update.sql';
		//Получаем расширение файла
		$file_cap_arr = explode('.', $file_cap);
		if (count($file_cap_arr) > 1) {
			$file_ext = $file_cap_arr[count($file_cap_arr)-1];
		}
		$download_dir = str_replace(chr(92), chr(47), Getpath().'/temp/'); // заменяем \ на /
		$target = $download_dir.basename($file_name);
		if (!file_exists($target)) {
			//header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			echo 'Запрашиваемый файл не найден';
			return;
		}
		header("Content-Type: application/download");
		if ($file_ext != '') {
			header("Content-Type: application/".$file_ext);
		}
		header("content-disposition: attachment; filename=".chr(34).$file_cap.chr(34));
		header("Content-Length: ".filesize($target));
	
		$file = fopen($target, "rb");
		$x = fread($file, filesize($target));  
		echo $x;
		return;
		break;
		
	default:
		$response = 'Неверное имя функции: '.$_POST['_func'];
	}
}

echo json_encode($response);

?>