//********************************************************************
// Скрипт раздела "Обновление"
//********************************************************************

var u_sql_Script = '/config/sections/sql/u_sql.php';

function u_sql_draw() 
{
	new Ajax.Request(g_path + u_sql_Script, {
		parameters: {
			'_func': 'GetSQLForm'
		},
		onComplete: function(transport) {
			var text = transport.responseText.evalJSON();
			var text_txt = '';
			if (text['error']) {
				text_txt += text['error']+'<br>';
			}
			if (text['html']) {
				text_txt += text['html'];
			}
			g_Prepare_Custom_Section(text_txt);
		}
	});	
}



function u_sql_runsql() 
{
	//Отправка значений на сервер
	var form = document.getElementById('sql_section').getElementsByTagName("form")[0];
	var outset = document.getElementById('u_sql_sqlresult');
	
	new Ajax.Request(g_path + u_sql_Script, {
		parameters: {
			'_func': 'RunSQL',
			'sql': form.sql.value
		},
		onComplete: function(transport) {
			var text = transport.responseText.evalJSON();
			var out = document.getElementById('u_sql_sqlresult');
			out.update(text.error+'<br>'+text.html);
		},
		onCreate:  function(transport) {
			var out1 = document.getElementById('u_sql_sqlresult');
			out1.update('Выполнение скрипта...');
		},
		onException:  function(transport, ex) {
			var out2 = document.getElementById('u_sql_sqlresult');
			out2.update('Возникла ошибка при выполнении скрипта');
		}
	});
}

