//********************************************************************
// Раздел "Рассылка" Закладка "Получатели"
//********************************************************************
var c_Mailing_contact_ScriptFileName = '/config/sections/Mailing/dc_contact.php';

//Инициализация таблицы
function g_mailing_contact_grid_init(p_grid_id) {
	var elem = getGridFooterTable(p_grid_id);
	var mailing_id = $(p_grid_id).getAttribute('detail_parent_record_id');
	var btn_cont = $(elem.rows[0].cells[0]);
	
	// кнопка добавить (фальшивый lookup)
	var add_button_html = '';
	var btn_title = 'Добавить контакт в рассылку. Можно добавлять только контакты, у которых указан email адрес';
	var btn_id = '_'+p_grid_id+'_file';
	var where = 'T0.id not in (select MC.contactid from iris_mailing_contact MC where MC.mailingid = \''+mailing_id+'\') and T0.email is not null';
	var add_button_html = '<form><table><tbody><tr><td style="width: 0px"><input type="text" style="display: none;" elem_type="lookup" original_value="" value="" lookup_value="" lookup_column="name" lookup_grid_source_name="Contact" lookup_grid_source_type="grid" is_lookup="Y" mandatory="no" id="'+btn_id+'" filter_where="'+where+'" /></td><td width="20"><input type="button" class="button" onclick="openlookupwindow(this);" value="Добавить контакт" title="'+btn_title+'" id="'+btn_id+'_btn"/></td></tr></tbody></table></form>';
	
	var new_button = btn_cont.insert({'bottom':add_button_html}).down('input[elem_type="lookup"]');
	new_button.observe('lookup:changed', function() {
		if (new_button.value == '')
			return;
		
		new Ajax.Request(g_path + c_Mailing_contact_ScriptFileName, {
			parameters: {'_func': 'AddContact', 'contact_id': new_button.getAttribute('lookup_value'), 'mailing_id': mailing_id}, 
			onSuccess: function(transport) {
				var result = transport.responseText.evalJSON();
				if (result.errno != 0) {
					wnd_alert(result.errm);
				} else {
					redraw_grid(p_grid_id);
				}
			}
		});			
		
		c_Common_SetElementValue(new_button, c_Common_MakeFieldValue('', ''));
	});
	
	// новая кнопка "добавить..."
	var addrep_button_html = '<input type="button" title="Добавить получателей" value="Добавить..." onclick="g_mailing_contact_addfromreport(\''+p_grid_id+'\', \''+mailing_id+'\')" class="button">';
	var addrep_button_html = btn_cont.next().insert({'bottom':addrep_button_html}).down('input[value="Добавить..."]');
	
	// новая кнопка "удалить"
	var del_button_html = '<input type="button" title="Исключить из рассылки данный контакт" value="Удалить" onclick="" style="width: 70px;" class="button">';
	var new_del_btn = btn_cont.next().insert({'bottom':del_button_html}).down('input[value="Удалить"]');
	new_del_btn.observe('click', function() {
		var contact_id = $(p_grid_id).down('tr.' + g_vars.selected_class).getAttribute('t0_contactid');
		Dialog.confirm("Исключить из рассылки данный контакт?",{onOk:function() {Dialog.closeInfo(); g_mailing_contact_removeContact(p_grid_id, contact_id, mailing_id);}, className: "iris_win", width: 300, height:null, buttonClass:"button", okLabel:"Да", cancelLabel:"Нет"});
	});

	// новая кнопка "предпросмотр"
	var preview_button_html = '<input type="button" title="Предварительный просмотр" value="Предпросмотр" onclick="" style="margin-left: 10px;" class="button">';
	var preview_button_btn = btn_cont.next().insert({'bottom':preview_button_html}).down('input[value="Предпросмотр"]');
	preview_button_btn.observe('click', function() {
		var contact_id = $(p_grid_id).down('tr.' + g_vars.selected_class).getAttribute('t0_contactid');
		//Dialog.confirm("Исключить из рассылки данный контакт?",{onOk:function() {Dialog.closeInfo(); g_mailing_contact_removeContact(p_grid_id, contact_id, mailing_id);}, className: "iris_win", width: 300, height:null, buttonClass:"button", okLabel:"Да", cancelLabel:"Нет"});
		var wnd = prepareCardWindow('12345', 'Предпросмотр письма', 600, 500);
		wnd.setURL(g_path+c_Mailing_contact_ScriptFileName+'/dc_contact.php?mode=preview&mailingid='+mailing_id+'&contactid='+contact_id);
	});
	
	// новая кнопка "создать письма"
	var email_button_html = '<input type="button" title="Создать письма всем получателям рассылки. Старые неотправленные письма заменятся новыми" value="Создать письма" onclick="" class="button">';
	var email_button_btn = btn_cont.next().insert({'bottom':email_button_html}).down('input[value="Создать письма"]');
	email_button_btn.observe('click', function() {
		Dialog.confirm("Сейчас для каждого получателя, у которого еще создано письмо будет сформировано индивидуально письмо рассылки. Продолжить?", {
			onOk: function() {
				Dialog.closeInfo(); 
				g_mailing_contact_createEmails(p_grid_id, mailing_id);
			}, className: "iris_win", width: 300, height: null, buttonClass: "button", okLabel: "Да", cancelLabel: "Нет"
		});
	});
	
	// кнопка "удалить письма"
	var delemail_button_html = '<input type="button" title="Удалить все неотправленные письма рассылки" value="Удалить письма" onclick="" class="button">';
	var delemail_button_btn = btn_cont.next().insert({'bottom':delemail_button_html}).down('input[value="Удалить письма"]');
	delemail_button_btn.observe('click', function() {
		Dialog.confirm("Сейчас будут удалены все неотправленные письма данной рассылки. Продолжить?", {
			onOk: function() {
				Dialog.closeInfo(); 
				g_mailing_contact_deleteEmails(p_grid_id, mailing_id);
			}, className: "iris_win", width: 300, height: null, buttonClass: "button", okLabel: "Удалить", cancelLabel: "Отмена"
		});
	});	
	
	// если в гриде нет записей, то сделаем кнопки неактивными
	if ($(p_grid_id).getAttribute('selectedrow') == -1) {
		new_del_btn.setAttribute('disabled', 'disabled')
		preview_button_btn.setAttribute('disabled', 'disabled')
		email_button_btn.setAttribute('disabled', 'disabled')
	}

	
	//Пройдем по всем строчкам таблицы и заполним поля-ссылки
	var grid = $(p_grid_id);
	for (var i=1; i < grid.rows.length; i++) {
		if ($(grid.rows[i]).hasClassName('grid_void'))
			break;
		
		// сделаем поле "Контакт" ссылкой, при нажатии на которую открывается карточка контакта
		var span = $(grid.rows[i].down('td[alias="contact"]')).down('span');
		var new_value = '<span style="color: #3E569C; cursor: pointer; text-decoration: underline" onclick="openCard(\'grid\', \'Contact\', \''+grid.rows[i].getAttribute('t0_contactid')+'\')">'+span.innerHTML+'</span>';
		span.update(new_value);
		
		// сделаем поле email ссылкой
		if (grid.rows[i].getAttribute('t0_emailid') != '') {
			var email_value = '<span style="color: #3E569C; cursor: pointer; text-decoration: underline" onclick="openCard(\'grid\', \'Email\', \''+grid.rows[i].getAttribute('t0_emailid')+'\')">открыть письмо...</span>';
			var span = $(grid.rows[i].down('td[alias="email"]')).down('span').update(email_value);
		}
	}	
}

function g_mailing_contact_removeContact(p_grid_id, p_contact_id, p_mailing_id) {
	new Ajax.Request(g_path + c_Mailing_contact_ScriptFileName, {
		parameters: {'_func': 'RemoveContact', 'contact_id': p_contact_id, 'mailing_id': p_mailing_id}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			if (result.errno != 0) {
				wnd_alert(result.errm);
			} else {
				redraw_grid(p_grid_id);
			}
		}
	});	
}

function g_mailing_contact_createEmails(p_grid_id, p_mailing_id, p_leftcount) {
	if (p_leftcount == null) {
		Dialog.info('Создаются письма рассылки...', {width:250, height:60, className: "iris_win", showProgress: true});
	} else {
		Dialog.setInfoMessage('Создаются письма рассылки...' + '<br>осталось '+p_leftcount+' '+getNumberCaption(parseInt(p_leftcount, 10), ['письмо', 'письма', 'писем']));
	}
	
	new Ajax.Request(g_path + c_Mailing_contact_ScriptFileName, {
		parameters: {'_func': 'CreateEmails', 'mailing_id': p_mailing_id}, 
		onComplete: function(transport) {
			try {
				if (transport.responseText == '')
					transport.responseText = '{"success": "0", "message": "Ошибка"}';
				
				var result = transport.responseText.evalJSON();
			} catch (e) {
				var result = {"success": 0, "message": "Ошибка"};
			}
			if ((result.success == 1) && (result.leftcount > 0)) {
				g_mailing_contact_createEmails(p_grid_id, p_mailing_id, result.leftcount);
				return;
			}
			
			Dialog.closeInfo();
			
			if (result.message != '') {
				wnd_alert(result.message);
			}
			if (result.success == 1) {
				redraw_grid(p_grid_id);
			}
		}
	});
}

function g_mailing_contact_addfromreport(p_grid_id, p_mailing_id) {
	var form = showParamsWindow({
		reportcode: 'mailing_contact', 
		okLabel: "Добавить", 
		onOk: function(p_form) { AddContactFromReport(p_form, p_grid_id, p_mailing_id); } 
	}); // common/Lib/reportlib.js
}

function AddContactFromReport(p_form, p_grid_id, p_mailing_id) {
	new Ajax.Request(g_path+c_Mailing_contact_ScriptFileName, {
		parameters: {
			'_func': 'AddContactFromReport', 
			'_reportcode': 'mailing_contact',
			'_mailingid': p_mailing_id,
			'_filters': Object.toJSON(getReportFilters(p_form))
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			if (result.errno != 0) {
				wnd_alert(result.errm);
			} else {
				Windows.close(get_window_id(p_form));
				redraw_grid(p_grid_id);
			}
		}
	});
}

function g_mailing_contact_deleteEmails(p_grid_id, p_mailing_id) {
	Dialog.info('Удаляются письма рассылки...', {width:250, height:60, className: "iris_win", showProgress: true});
	new Ajax.Request(g_path + c_Mailing_contact_ScriptFileName, {
		parameters: {'_func': 'DeleteEmails', 'mailing_id': p_mailing_id}, 
		onComplete: function(transport) {
			Dialog.closeInfo();
			if (transport.responseText == '')
				transport.responseText = '{"success": "0", "message": "Ошибка"}';
			
			var result = transport.responseText.evalJSON();
			if (result.message != '') {
				wnd_alert(result.message);
			}
			if (result.success == 1) {
				redraw_grid(p_grid_id);
			}
		}
	});
}