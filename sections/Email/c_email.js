//********************************************************************
// Раздел "E-mail". Карточка.
//********************************************************************

var c_Email_ScriptFileName = '/config/sections/Email/c_email.php';


//Инициализация карточки
function c_email_card_init(p_wnd_id)
{
	//Форма карточки
	var l_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

	try {
		var parent = l_form._parent_id.value;
		var parenttable = parent.split('#;')[0];
		var parentid = parent.split('#;')[1];
		var parentname = parent.split('#;')[2];
		var emailaddress = parent.split('#;')[3];
//		alert(parent);
		if ('iris_Contact' == parenttable) {
			l_form.ContactID.value = parentname;
			l_form.ContactID.setAttribute('lookup_value', parentid);
			l_form.ContactID.setAttribute('original_value', parentname);
			$(l_form.e_to).value = emailaddress;
		}
		if ('iris_Account' == parenttable) {
			l_form.AccountID.value = parentname;
			l_form.AccountID.setAttribute('lookup_value', parentid);
			l_form.AccountID.setAttribute('original_value', parentname);
			$(l_form.e_to).value = emailaddress;
		}
	}
	catch (e) {
	}

    c_Common_SetOnBlur(l_form.ContactID, l_form, 'ContactID', c_Email_ScriptFileName, false);

	try {
		//Тип письма (код)
		var code_str = l_form.EmailTypeID.options[l_form.EmailTypeID.selectedIndex].getAttribute('code');
		
		//Если редактируем входящее письмо
		if ((l_form._mode.value == 'update') && (code_str == 'Inbox')) {
			//Добавим себя в список прочитавших
			var has_readed_str = $(l_form._parent_id.value).rows[$(l_form._parent_id.value).getAttribute('selectedrow')].getAttribute('t0_has_readed');
			var has_readed_str_old = has_readed_str;
			if (has_readed_str == '') {
				has_readed_str = '["'+g_session_values['userid']+'"]';
			}
			else {
				if ((has_readed_str.indexOf(g_session_values['userid']) == -1) && (has_readed_str.isJSON() == true)) {
					var has_readed_arr = has_readed_str.evalJSON();
					has_readed_arr[has_readed_arr.length] = g_session_values['userid'];
					has_readed_str = Object.toJSON(has_readed_arr);
				}
			}
			//l_form.Subject.parentNode.innerHTML += '<input id="has_readed" type="hidden" value=""/><input id="#has_readed" type="hidden" value="string"/>';
			//l_form.has_readed.value = has_readed_str;
			debug('has_readed_str '+has_readed_str);
			
			//Отправим тут же запрос на обновление списка прочитавших
			if (has_readed_str_old != has_readed_str) {
				new Ajax.Request(g_path+c_Email_ScriptFileName, {
					parameters: {
						'_func': 'UpdateReaders',
						'_p_id': l_form._id.value,
						'_p_readers': has_readed_str
					},
					onSuccess: function(transport) {
						debug('UpdateReaders onSuccess');
						var result = transport.responseText;
						if (result.isJSON() == true) {
							var res_json = result.evalJSON();
//							var id = res_json.UpdateReaders.id;
//							if (res_json.status == '+') {
//								redraw_grid('grid'); // miv: не нужно перерисовывать, к тому же шв грида не стоит указывать жостко
//							}
							// miv 25.05.2009: когда обновили прочитавших, то отметим это в гриде
							var grid = $(l_form._parent_id.value); // берем id родительского грида из карточки
							grid.rows[grid.getAttribute('selectedrow')].removeClassName('grid_newmail'); // отмесаем строчку как прочитаную
						} 
						else {
							var messageHTML = T.t('Возникла ошибка при установке отметки о прочтении');
							wnd_alert(messageHTML, 300, 100);
						}
					}
				});
			}
		}
	} 
	catch (e) {
	}

	//Сделаем недоступным для редактирования входящее письмо
	if (l_form.EmailTypeID.options[l_form.EmailTypeID.selectedIndex].getAttribute('code') == 'Inbox') {
		try {
			setTimeout(function(){
				try {
					var editor = CKEDITOR.instances[l_form.body.getAttribute('actualelement')];
					editor.document.$.body.contentEditable = false;
					editor.document.$.designMode = 'off';
				} catch (e) {}
			}, 1000);			
		} catch (e) {}
		l_form.EmailTypeID.setAttribute('disabled', 'disabled');
		l_form.e_from.setAttribute('disabled', 'disabled');
		l_form.e_to.setAttribute('disabled', 'disabled');
		l_form.Subject.setAttribute('disabled', 'disabled');
	}


	//Если создаем новое письмо
	if (l_form._mode.value == 'insert') {
		if (l_form._parent_id.value.charAt(0) == '#') {
			// если карточку открыли в режиме "ответить", то заполним нужные поля и выйдем
			l_form.setAttribute('DefaultValuesLoadingR', '1'); // miv 13.01.2011: чтобы не вызывалось событие, так как оно вешается до того, как приходят данные
			c_Email_SetReplyFields(l_form);
//			return;
		}
	
		//Выберем ящик отправки письма по умолчанию
		for (var n0=0; n0<l_form.emailaccountid.options.length; n0++) {
			if (l_form.emailaccountid.options[n0].getAttribute('is_primary') == '1') {
				l_form.emailaccountid.options[n0].selected = true;
				debug('выбрали аккаунт');
				l_form.e_from.value = l_form.emailaccountid.options[l_form.emailaccountid.selectedIndex].innerHTML;
			}
		}
		
		var elem_code;
		//Уберем типы писем и оставим те, которые могут быть у нового письма
		for (var n=l_form.EmailTypeID.options.length-1; n>=0; n--) {
			elem_code = l_form.EmailTypeID.options[n].getAttribute('code');
			if ((elem_code=='Inbox') || (elem_code=='Sent') || (elem_code=='Mailing_outbox') || (elem_code=='Mailing_sent')) {
				l_form.EmailTypeID.options[n] = null;
			}
			if (elem_code=='Outbox') {
				l_form.EmailTypeID.selectedIndex = n;
			}
		}

		//Значения по умолчанию
		l_form.setAttribute('DefaultValuesLoadingD', '1'); // miv 10.01.2011: чтобы не вызывалось событие, так как оно вешается до того, как приходят данные
		c_Common_SetDefaultValues(l_form, c_Email_ScriptFileName, function() {
			l_form.removeAttribute('DefaultValuesLoadingD');
			if (l_form.getAttribute('reply_mode') != 'yes') {
				c_Email_FillTemplate(l_form, true); // если карточку открыли не в режиме ответить, то заполним поля по шаблону
			}

			//При изменении типа письма меняем состав обязательных полей
			if (l_form.EmailTypeID.getAttribute('disabled') != 'disabled') {
				c_Common_SetOnChange(l_form.EmailTypeID, l_form, 'EmailTypeID', c_Email_ScriptFileName, false, function() {
					l_form._hash.value = GetCardMD5(get_window_id(l_form));     
				});
			}
			l_form._hash.value = GetCardMD5(get_window_id(l_form));
        });
	}
	
	if ((l_form._mode.value == 'update') && (l_form.EmailTypeID.getAttribute('disabled') != 'disabled')) {
		c_Common_SetOnChange(l_form.EmailTypeID, l_form, 'EmailTypeID', c_Email_ScriptFileName, false, function() {
			l_form._hash.value = GetCardMD5(get_window_id(l_form));     
		}, false, true);
	}
	
	if ((l_form._mode.value == 'update') && (code_str == 'Inbox')) {
		// если редактируем входящее письмо, то нарисуем кнопку "создать инцидент"
		var button_cont = $(l_form.btn_cancel).up('table.form_table_buttons_panel').rows[0].cells[0];
		button_cont.innerHTML += '<input type="button" onclick="c_email_createIncident(this)" value="'+T.t('Создать инцидент')+'" style="width: 180px;" class="button" id="_createincident">';

        // нарисуем кнопку ответить
        addCardFooterButton(p_wnd_id, 'top', T.t('Ответить'), 'c_email_reply(\''+p_wnd_id+'\')', T.t('Ответить на письмо'));
	}
	

	if ((l_form._mode.value == 'insert') || ((l_form._mode.value == 'update') && (code_str == 'Inbox'))) {
		//Заполнение шаблона при изменении контакта и шаблона
		$(l_form.ContactID).observe('lookup:changed', function() {
			c_Email_FillTemplate(l_form);
		});
		$(l_form.emailtemplateid).observe('lookup:changed', function() {
			c_Email_FillTemplate(l_form);
		});
	}
	
	//Если новое письмо или с типом исходящее, то поле from должно быть выпадающим списком 
	//и также заполняться поле на ящик
	//if ((l_form._mode.value == 'insert') || (l_form.EmailTypeID.options[l_form.EmailTypeID.selectedIndex].getAttribute('code') == 'Outbox')) {
	if ((l_form._mode.value == 'insert') || (code_str == 'Outbox')) {
		//showDebug();	
		debug('новое или Outbox');
		
		//Скрываем поле От[почтовый адрес]
//		$(l_form.e_from.parentNode.parentNode).setStyle({'display': "none"});
		$(l_form.e_from).up('tr[class="form_row"]').setStyle({'display': "none"});

		//Делаем поле От[учетная запись] обязательным
		l_form.emailaccountid.setAttribute('mandatory', 'yes');
		$(l_form.emailaccountid).up('td').previous().down('span').addClassName('card_elem_mandatory');
		
		//Добавляем обработчик на поле От[учетная запись] для заполнения поля От[почтовый адрес]
		$(l_form.emailaccountid).observe('change', function() {
			l_form.e_from.value = l_form.emailaccountid.options[l_form.emailaccountid.selectedIndex].innerHTML;
			debug('сработало событие: '+l_form.e_from.value);
		});
		
		//Изменяем интерфейс для поля "кому"
		ChangeToField(l_form);
	} 
	else {
		$(l_form.emailaccountid.parentNode.parentNode).setStyle({'display': "none"});
	}
	
	// miv 25.08.2010: добавлена кнопка отправить
	if ((l_form._mode.value == 'insert') || (code_str == 'Outbox'))
		var send_btn = addCardFooterButton(p_wnd_id, 'top', T.t('Отправить письмо'), 'c_email_schedule_send(this)', '');
	
	//Уменьшим высоту карточки
	UpdateCardHeight(p_wnd_id);
	l_form._hash.value = GetCardMD5(get_window_id(l_form));
	
/*
	// miv 20.10.2010: применить доступ
	if (l_form._mode.value == 'update')
		applyaccess_drawButton(p_wnd_id, 'iris_email');
*/
}


function c_Email_FillTemplate(l_form, p_start, p_is_replace) {
	if ((l_form.getAttribute('DefaultValuesLoadingR') == '1') || (l_form.getAttribute('DefaultValuesLoadingD') == '1'))
		return; // miv 10.01.2011: если еще не подгрузились значения по умолчанию, то выйдем
	if (typeof(p_start) == "undefined") {
		p_start = false;
	}
	var contactid = l_form.ContactID.getAttribute('lookup_value');
	if ('null' == contactid) {
		contactid = null;
	}
	var templateid = l_form.emailtemplateid.getAttribute('lookup_value');
	if ('null' == templateid) {
		templateid = null;
	}
	if (templateid && contactid && (!l_form.Subject.value || !l_form.body.value)) {
		var fillsubject = 0;
		var fillbody = 0;
		if (!l_form.Subject.value) {
			fillsubject = 1;
		}
		var body_value = c_Common_GetElementValue(l_form.body);
		if (body_value == '') {
			fillbody = 1;
		}
		
		if ((p_is_replace == undefined) && ((!fillsubject) || (!fillbody))) {
			var message = T.t('Сформировать содержание письма заново из шаблона?');
			Dialog.confirm(message,{onOk:function() {Dialog.closeInfo(); c_Email_FillTemplate(l_form, p_start, true);}, className: "iris_win", width: 300, buttonClass:"button", okLabel: T.t("Да"), cancelLabel: T.t("Нет")});
			return;
		} else {
			fillsubject = 1;
			fillbody = 1;
		}
		
		new Ajax.Request(g_path+c_Email_ScriptFileName, {
			parameters: {
    			'_func': 'FillTemplate', 
    			'_p_contactid': contactid,
    			'_p_emailtemplateid': templateid,
    			'_p_fillsubject': fillsubject,
    			'_p_fillbody': fillbody,
    			'_p_address': l_form.e_to.value
    		},
    		onComplete: function(transport) {
    			c_Common_SetFieldValues_end(transport, l_form, true, function(){}, true);
				if (l_form.getAttribute('parent_body') != null)
					CKEDITOR.instances[l_form.body.getAttribute('actualelement')].setData(c_Common_GetElementValue(l_form.body) + l_form.getAttribute('parent_body'), function() {
						l_form._hash.value = GetCardMD5(get_window_id(l_form));
					});
				
    			if (p_start) {
    				l_form._hash.value = GetCardMD5(get_window_id(l_form));
    			}
    		}
		});
	}
}

// устанавливает поля тема, кому, заказ, услуга (если карточку открыли в режиме "ответить")
function c_Email_SetReplyFields(p_form) {
	var res_array = p_form._parent_id.value.split('#');
	p_form._parent_id.value = res_array[2];
	var rec_id = res_array[1];
	$(p_form._params).insert({'after': '<input id="_reply_email_id" type="hidden" value="'+rec_id+'">'})
	p_form.setAttribute('reply_mode', 'yes');
	var params = {'_func': 'GetReplyFields', '_p_parent_id': rec_id};
	new Ajax.Request(g_path + c_Email_ScriptFileName, {
		parameters: params, 
		onSuccess: function(transport) {
			c_Common_SetFieldValues_end(transport, p_form, true);
			p_form.removeAttribute('DefaultValuesLoadingR');
			
			var result = transport.responseText.evalJSON();
			var parentbody = GetFieldValueByFieldName(result.FieldValues, '_parent_body'); // сохраняем текст письма, на которое отвечаем
			p_form.setAttribute('parent_body', parentbody);
			
			p_form._hash.value = GetCardMD5(get_window_id(p_form));
/*			
			var result = transport.responseText.evalJSON();
			// тема
			p_form.Subject.value = result.Subject;
			// заказ
			if (!Object.isUndefined(result.AccountID)) {
				tmp_setLookupValue(p_form.AccountID, result.AccountID, result.project_name);
			}
			// услуга
			if (!Object.isUndefined(result.product_id)) {
				tmp_setLookupValue(p_form.ProductID, result.product_id, result.product_name);
			}
			// кому
			if (!Object.isUndefined(result.recipient_id)) {
				tmp_setLookupValue(p_form.RecipientID, result.recipient_id, result.recipient_name);
			}
*/			
		}
	});	
}


//Изменение интерфейса поля "Кому"
function ChangeToField(p_form) {
	//Ячейка таблицы, в которой находится поле "кому"
	var e_to_cell = p_form.e_to.parentNode;
	//html текст элемента "кому"
	var e_to_html = e_to_cell.innerHTML;
	var e_to_value = p_form.e_to.value;  //mnv

	var new_html  = "<table style='width: 100%'><tbody><tr>";
	//1 столбец - текстовое поле "кому"
	new_html += "<td>"+e_to_html+"</td>";
	//2 столбец - выпадающий список компания / контакт
	var select_html  = "<select id='_to_mode' class='edttext' elem_type='select' onblur= \"this.className = 'edtText';\" onfocus=\"this.className = 'edtText_selected';\" style='width: 100%; margin-left: 2px;' mandatory='no'>";
	select_html += "<option value='Contact'>"+T.t('Контакт')+"</option>";
	select_html += "<option value='Account'>"+T.t('Компания')+"</option>";
	select_html += "</select>";
	new_html += "<td style='width: 120px'>"+select_html+"</td>";
	//3 столбец - скрытое поле lookup и кнопка выбора адреса "..."

	var hidden_elem_html = '<input type="text" elem_type="lookup" original_value="" value="" lookup_value="null" lookup_column="Email" lookup_grid_source_name="Contact" lookup_grid_source_type="grid" is_lookup="Y" style="display: none"  mandatory="no" id="_emailaddress"/>';
	var button_html = '<input type="button" onclick="openlookupwindow(this)" value="+" id="_emailaddress_btn" style="margin: 0px 0px 0px 4px; width: 20px;" class="button"/>';
	new_html += "<td style='width: 20px'>"+hidden_elem_html+button_html+"</td>";
	new_html += "</tr></tbody></table>";
	e_to_cell.innerHTML = new_html;
	p_form.e_to.value = e_to_value;  //mnv

	// при смене select изменить параметры lookup
	$(p_form._to_mode).observe('change', function() {
		$(p_form._emailaddress).setAttribute('lookup_grid_source_name', p_form._to_mode.options[p_form._to_mode.selectedIndex].value);
	});
	
	// TODO: повесить событие на изменение lookup - ++ к основному и тут же очистить
	$(p_form._emailaddress).observe('lookup:changed', function() {
		if (p_form.e_to.value == '') {
			p_form.e_to.value = p_form._emailaddress.value;
		} 
		else 
		if (p_form.e_to.value.include(p_form._emailaddress.value) == false) {
			p_form.e_to.value += ', '+p_form._emailaddress.value;
		}

		//Событие на добавление email (выбор контакта, компании...)
		c_Common_LinkedField_OnChange(p_form, '_emailaddress', c_Email_ScriptFileName, false, function() {});

		p_form._emailaddress.value = '';
		p_form._emailaddress.setAttribute('lookup_value', 'null');
		p_form._emailaddress.setAttribute('original_value', '');
	});	
}


//После сохранения записи
function c_email_on_after_save(p_rec_id, p_mode) {
	//var wnd_id = Windows.getFocusedWindow().getId();
	var wnd_id = arguments[3];
	var form = $(wnd_id).getElementsByTagName("form")[0];
	
	// отправим после отправки
	if (form.getAttribute('send_after_save') == 'yes') {
		c_email_send(p_rec_id);		
	}
}

// создание инцидента из псьма
function c_email_createIncident(p_this) {
	var form = document.getElementById(get_window_id(p_this)).getElementsByTagName("form")[0];
	var params = {mode: 'incident_from_email', emailid: form._id.value};
	
	form._hash.value = 'close';
	CloseCardWindow(p_this);
	
	openCard({
		source_name: 'Incident', 
		card_params: Object.toJSON(params)
	});
}


function c_email_schedule_send(p_this) {
	p_this.form.setAttribute('send_after_save', 'yes');
	if ($(p_this.form.btn_ok)) {
		p_this.form.btn_ok.onclick();
		p_this.setAttribute('disabled', 'disabled');
	}
}

function c_email_send(p_rec_id) {
    new Ajax.Request(g_path+'/config/sections/Email/lib/send.php', {
		parameters: {'id': p_rec_id, 'send_mode': 'Outbox'},
		onSuccess: function(transport) {
			var result = transport.responseText;
			if (result.isJSON() == true) {
				var res_json = result.evalJSON();
				var messageHTML = res_json.message;
			}
			else {
				messageHTML = T.t('Возникла ошибка при отправке почты');
				messageHTML += ':<br><textarea class="edtText" style="margin: 10px 5px 0px 5px; width: 280px; heigh: 80px" readonly="true">'+result+'</textarea>';
			}
			wnd_alert(messageHTML, 300);
		}
	});
}

// ответить на письмо в текущем окне
function c_email_reply(p_wnd_id) {
    var form = $(p_wnd_id).getElementsByTagName("form")[0];
    switchShadowCard(form, 'show'); // устанавливаем тень на карточке

    openCard({
        source_type			: 'grid',
        source_name			: 'Email',
        rec_id				: '',
        parent_id			: '#'+form._id.value+'#'+form._parent_id.value,
        replace_window_id   : p_wnd_id
    });
}
