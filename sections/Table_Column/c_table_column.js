//********************************************************************
// Вкладка "Колонки таблицы". Карточка.
//********************************************************************

var c_Table_Column_ScriptFileName = '/config/sections/Table_Column/c_table_column.php';

function c_Table_Column_Init(p_wnd_id) 
{
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

	// обработчик поля "Код (название в БД)"
	$(card_form.Code).observe('blur', function() {
		var newvalue = card_form.Code.value.toLowerCase().gsub(/([а-я]|\s|\W)/, '');
		if (card_form.Code.value != newvalue) {
			card_form.Code.value = newvalue;
			showNotify('В поле "Код (название в БД)" можно использовать только незаглавные латинские символы и цифры');
		}
	});
	
	if (card_form._mode.value == 'update') {
	}
	
	// miv 30.04.2010: автозаполнение ключей и индексов
	$(card_form.fkName).up('td.form_table').previous().down().setStyle({"cursor": 'pointer', "color": "#3E569C"}).observe('click', function() {
		if (card_form.fkName.value == '') {
			card_form.fkName.value = 'fk_' + card_form.TableID.value + '_' + card_form.Code.value;
			SetSelectValueByID(card_form.OnDeleteID, '9f8bccc8-923a-3e15-6484-f7f4168294b2');
			SetSelectValueByID(card_form.OnUpdateID, '9f8bccc8-923a-3e15-6484-f7f4168294b2');
		}
	});
	$(card_form.pkName).up('td.form_table').previous().down().setStyle({"cursor": 'pointer', "color": "#3E569C"}).observe('click', function() {
		if (card_form.pkName.value == '') {
			card_form.pkName.value = 'pk_' + card_form.TableID.value + '_' + card_form.Code.value;
		}
	});
	$(card_form.IndexName).up('td.form_table').previous().down().setStyle({"cursor": 'pointer', "color": "#3E569C"}).observe('click', function() {
		if (card_form.IndexName.value == '') {
			card_form.IndexName.value = card_form.TableID.value + '_' + card_form.Code.value + '_i';
		}
	});
}


//После сохранения
function c_Table_Column_onAfterSave(p_rec_id, p_mode, p_values) {
    if (p_mode == 'insert') {
		//После создания колонки вставим ее в базу
		new Ajax.Request(g_path+c_Table_Column_ScriptFileName, {
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
		//После изменения колонки применим изменения в базе
		new Ajax.Request(g_path+c_Table_Column_ScriptFileName, {
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