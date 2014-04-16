//********************************************************************
// Раздел "Таблицы". Карточка.
//********************************************************************

var c_Table_ScriptFileName = '/config/sections/Table/c_table.php';

function c_Table_Init(p_wnd_id) 
{
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

	// при нажатии на название поля "Справочник (код)" откроем справочник
	var dict_cap_elem = $(card_form.Dictionary).up('td.form_table').previous().down();
	dict_cap_elem.setAttribute('title', 'Открыть справочник');
	dict_cap_elem.setStyle({"cursor": 'pointer', "color": "#3E569C"}).observe('click', function() {
		if (card_form.Dictionary.value != '') {
			opengridwindow("", "", "dict", card_form.Dictionary.value);
		}
	});
	
	// miv 30.08.2010: фильтрация для поля "Отображать колонку"
	if (card_form._mode.value == 'insert') {
		card_form.ShowColumnID.setAttribute('disabled', 'disabled');
		card_form.ShowColumnID_btn.setAttribute('disabled', 'disabled');
	}
	if (card_form._mode.value == 'update') {
		card_form.ShowColumnID.setAttribute('filter_where', "T0.tableid = '"+card_form._id.value+"'");
		
		addCardHeaderButton(p_wnd_id, 'top', 'Создать справочник', 'table_create_dict_xml_ask(\''+p_wnd_id+'\')', 'Если таблица является справочником (заполнено поле "Справочник (код)"), то будет создано xml описание справочника');
		addCardHeaderButton(p_wnd_id, 'top', 'Скопировать права', 'table_copyaccess_ask(\''+p_wnd_id+'\')', 'Скопировать права по умолчанию от этой таблицы во все остальные таблицы, у которых учитывается доступ по записям. Старые права по умолчанию при этом будут удалены');
	}
}


//После сохранения
function c_Table_onAfterSave(p_rec_id, p_mode, p_values) {
    if (p_mode == 'insert') {

		//После создания колонки вставим ее в базу
		new Ajax.Request(g_path+c_Table_ScriptFileName, {
			parameters: {
				'_func': 'onAfterInsert', 
				'_record_id': p_rec_id
			},
			onSuccess: function(transport) {
				var res = transport.responseText.evalJSON();
				//Если неуспешно, то выведем ошибку
				if (undefined != res.Error) {
					wnd_alert(res.Error, 300, 100);
				}
			}
		}); 
	} 
	else {
		// после изменения колонки применим изменения в базе
		new Ajax.Request(g_path+c_Table_ScriptFileName, {
			parameters: {
				'_func': 'onAfterUpdate', 
				'_record_id': p_rec_id,
				'_values': p_values
			},
			onSuccess: function(transport) {
				var res = transport.responseText.evalJSON();
				//Если неуспешно, то выведем ошибку
				if (undefined != res.Error) {
					wnd_alert(res.Error, 300, 100);
				}
			}
		}); 
	}
}


//После удаления
function g_Table_onAfterDelete(p_values)
{
	new Ajax.Request(g_path+c_Table_ScriptFileName, {
		parameters: {
			'_func': 'onAfterDelete', 
			'_values': p_values
		}
	}); 
}

function table_copyaccess_ask(p_wnd_id) {
	if (g_session_values.userrolecode != 'admin') {
		wnd_alert('Данная функция доступна только администраторам');
		return;
	}
	
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];
	var div_id = (Math.random()+"").slice(3);
	Dialog.confirm("Права доступа по умолчанию от таблицы <br><b>"+card_form.Code.value+"</b><br> будут установлены для <b><span id='"+div_id+"'>**</span></b> таблиц,<br> у которых включен доступ по записям. Продолжить?", {
		onOk: function() {Dialog.closeInfo(); table_copyaccess(card_form._id.value);},
		className: "iris_win", width: 300, buttonClass: "button", okLabel: "Да", cancelLabel: "Нет"
	});

	new Ajax.Request(g_path + c_Table_ScriptFileName, {
		parameters: {'_func': 'GetTableCount'}, 
		onComplete: function(transport) {
			try {
				var result = transport.responseText.evalJSON();
				var count = result.message;
			} catch (e) {
				var count = '';
			}
			if ($(div_id) != undefined)
				$(div_id).update(count);
		}
	});

}

function table_copyaccess(p_table_id) {
	Dialog.info('Идет копирование...', {width:250, height:60, className: "iris_win", showProgress: true});
	new Ajax.Request(g_path + c_Table_ScriptFileName, {
		parameters: {'_func': 'CopyAccessDefault', 'table_id': p_table_id}, 
		onComplete: function(transport) {
			Dialog.closeInfo();
			try {
				var result = transport.responseText.evalJSON();
				wnd_alert(result.message, 350);
			} catch (e) {
				wnd_alert('Не удалось скопировать права по умолчанию', 350);
			}
		}
	});	
}

function table_create_dict_xml_ask(p_wnd_id, p_exec_flag) {
	if (g_session_values.userrolecode != 'admin') {
		wnd_alert('Данная функция доступна только администраторам');
		return;
	}
	
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];
	var table_code = card_form.Code.value;
	var table_name = card_form.Name.value;
	var dict_code = card_form.Dictionary.value;
	if (dict_code == '') {
		wnd_alert('Не заполнено поле "Справочник (код)"');
		return;
	}

	new Ajax.Request(g_path + c_Table_ScriptFileName, {
		parameters: {'_func': 'GetDictStatus', 'dict_code': dict_code}, 
		onComplete: function(transport) {
			var result = transport.responseText.evalJSON();
			if (result.success == '0') {
				wnd_alert(result.errm);
				return;
			}
			
			Dialog.confirm("Будет создан справочник <b>"+dict_code+"</b> для таблицы <b>"+table_code+"</b>", {
				onOk: function() {Dialog.closeInfo(); table_create_dict_xml(table_code, table_name, dict_code)},
				className: "iris_win", width: 300, buttonClass: "button", okLabel: "Продолжить", cancelLabel: "Отмена"
			});
			
		}
	});	
	
}

function table_create_dict_xml(p_table_code, p_table_name, p_dict_code) {
	new Ajax.Request(g_path + c_Table_ScriptFileName, {
		parameters: {'_func': 'CreateNewDict', 'table_code': p_table_code, 'table_name': p_table_name, 'dict_code': p_dict_code}, 
		onComplete: function(transport) {
			var result = transport.responseText.evalJSON();
			if (result.success == '0') {
				wnd_alert(result.errm);
				return;
			}
			wnd_alert(result.message);
		}
	});	
	
}