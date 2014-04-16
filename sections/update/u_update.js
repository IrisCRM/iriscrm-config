//********************************************************************
// Скрипт раздела "Обновление"
//********************************************************************

function u_update_draw() {
	new Ajax.Request(g_path+'/config/sections/update/u_update.php', {
		parameters: {
			'_func': 'GetUpdateForm'
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

function u_update_start() {
	//Отправка значений на сервер
	var form = document.getElementById('update_section').getElementsByTagName("form")[0];
	var outset = document.getElementById('u_update_output');
	//Получаем список таблиц для обновления
	var form_tables = document.getElementById('u_update_tables').getElementsByTagName("form")[0];
	var tables = form.getElements(form_tables);
	var tables_array = new Array(tables.length-1);
	for (var i=0; i<tables.length-1; i++) {
		tables_array[i] = new Object();
		tables_array[i].name = tables[i].name;
		tables_array[i].value = tables[i].value;
	}
	
	new Ajax.Request(g_path+'/config/sections/update/u_update.php', {
		parameters: {
			'_func': 'StartUpdate',
			'dbtype': form.dbtype.value,
			'dbhost': form.dbhost.value,
			'dbport': form.dbport.value,
			'dbuser': form.dbuser.value,
			'dbpassword': form.dbpassword.value,
			'dbname': form.dbname.value,
			'extime': form.extime.value,
			'freshrecords': form.freshrecords.value,
			'freshtables': form.freshtables.value,
			'updatedirection': form.updatedirection.value,
			'tables': Object.toJSON(tables_array)
		},
		onComplete: function(transport) {
			var text = transport.responseText.evalJSON();
			var out = document.getElementById('u_update_output');
			out.update(text.error+'<br>'+text.html);
		},
		onCreate:  function(transport) {
			var out1 = document.getElementById('u_update_output');
			out1.update('Подготовка скриптов...');
		},
		onException:  function(transport, ex) {
			var out2 = document.getElementById('u_update_output');
			out2.update('Возникла ошибка при подготовке скриптов обновления');
		}
	});	
}

function u_update_setuptables() {
	//Отправка значений на сервер
	var form = document.getElementById('update_section').getElementsByTagName("form")[0];
	new Ajax.Request(g_path+'/config/sections/update/u_update.php', {
		parameters: {
			'_func': 'SetupUpdateTables',
			'dbtype': form.dbtype.value,
			'dbhost': form.dbhost.value,
			'dbport': form.dbport.value,
			'dbuser': form.dbuser.value,
			'dbpassword': form.dbpassword.value,
			'dbname': form.dbname.value,
			'extime': form.extime.value,
			'freshrecords': form.freshrecords.value,
			'freshtables': form.freshtables.value,
			'nowdb': form.nowdb.value,
			'updatedirection': form.updatedirection.value
		},
		onComplete: function(transport) {
			var text = transport.responseText.evalJSON();
			var out = document.getElementById('u_update_tables');
			out.update(text.error+'<br>'+text.html);
		},
		onCreate:  function(transport) {
			var out1 = document.getElementById('u_update_tables');
			out1.update('Установка соединения...');
		},
		onException:  function(transport, ex) {
			var out2 = document.getElementById('u_update_tables');
			out2.update('Возникла ошибка при попытке соединения с базой или чтения данных.');
		}
	});	
}

function u_update_download() {
	//Отправка значений на сервер
	new Ajax.Request(g_path+'/config/sections/update/u_update.php', {
		parameters: {
			'_func': 'DownloadUpdateScript'
		}
	});	
}
