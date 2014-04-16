//********************************************************************
// Скрипт карточки раздела "Сообщения"
//********************************************************************

var c_Message_ScriptFileName = '/config/sections/Message/c_message.php';



function c_Message_Init(p_wnd_id) {
	//Форма карточки
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];


	// делаем поля неактивными
	// дата
	card_form.MessageDate.setAttribute("disabled", "disabled");
	// статус
	card_form.StatusID.setAttribute("disabled", "disabled");
	// автор
	card_form.AutorID.setAttribute("disabled", "disabled");
	try {
		// кнопки "..." может и не быть если открыли в закладке
		card_form.AutorID_btn.setAttribute("disabled", "disabled"); 
	} 
	catch (e) {} 

	
	//Если создается новая запись
	if (card_form._mode.value == 'insert') {
		//Заполнение полей значениями по умолчанию
		c_message_setDefaultValues(card_form);

		if (card_form._parent_id.value.charAt(0) == '#') {
			// если карточку открыли в режиме "ответить", то заполним нужные поля и выйдем
			setReplyFields(card_form);
			return;
		}

		try {
			// если закладка раздела Решения
			if (card_form.AnswerID.value != "") {
				c_message_setRecipient(card_form, 'GetRecipientFromAnswer', card_form.AnswerID.getAttribute('lookup_value'));
			}
		}
		catch (e1) {} 

		try {
			// если закладка раздела Замечания
			if (card_form.BugID.value != "") {
				c_message_setRecipient(card_form, 'GetRecipientFromBug', card_form.BugID.getAttribute('lookup_value'));
			}
		}
		catch (e2) {} 

		// если закладка Проекты, то - ajax запрос который вернет получателя сообщения (проект.ответственный или проект.клиент)
		if (card_form.ProjectID.value != "") {
			c_message_setRecipient(card_form, 'GetRecipientFromProject', card_form.ProjectID.getAttribute('lookup_value'));
		}
		else {
			//Если не проекты, но закладка, то это Контакты. Тогда Кому - контакт. 
			if (card_form._detail_column_value.value != "") {
				c_message_setRecipient(card_form, 'GetRecipientFromContact', card_form._detail_column_value.value);
			}
			else {
				//Если не закладка, то значит раздел
				if (g_session_values['userrolecode'] == 'Client') {
					//Установим получателя
					c_message_setRecipient(card_form, 'GenerateNewOwner', '');		
//					c_message_setProject(card_form, g_session_values['userid']);
				}
				else {
					//Если это не клиент, то при выборе кому будет выбираться последний активный заказ
					$(card_form.RecipientID).observe('lookup:changed', function() {c_message_setProject(card_form, card_form.RecipientID.getAttribute('lookup_value'));});
				}			
			}
		}
				
		//Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
		card_form._hash.value = GetCardMD5(get_window_id(card_form));
	}
	//Если редактируем карточку
	else {
		WriteWhoReaded(card_form._parent_id.value, card_form._id.value); // запишем кто прочел сообщение
		
		//Если пользователь = адресат сообщения, то выставим статус сообщение "прочитано"
		if (getElementValue(card_form.RecipientID) == g_session_values['userid']) {
			SetSelectValueByAttribute(card_form.StatusID, 'code', 'Readed');
			// miv 02.04.2010: Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
			card_form._hash.value = GetCardMD5(get_window_id(card_form));
			// miv 02.04.2010: обновим значение в гриде
			var grid = $('grid');
			for (var i=1; i < grid.rows.length; i++) {
				if (grid.rows[i].getAttribute('rec_id') == card_form._id.value)
					$(grid.rows[i].cells[(grid.getAttribute('source_name') == 'Message' ? 5 : 4)]).down('span').update(card_form.StatusID.options[card_form.StatusID.selectedIndex].innerHTML);
				if (grid.rows[i].getAttribute('rec_id') == null)
					break;
			}			
		}
		
		// miv 30.08.2010: добавляем кнопку ответить
		addCardFooterButton(p_wnd_id, 'top', T.t('Ответить'), "c_message_reply('"+p_wnd_id+"')", T.t('Ответить отправителю на данное сообщение'));	
	}
	
	// miv 15.11.2010: если карточка в режиме чтения, то поле текст сообщения сделаем read only
	if ($(card_form.message).readAttribute('disabled') == 'disabled') {
		card_form.message.removeAttribute('disabled');
		card_form.message.setAttribute('readOnly', 'readOnly');
	}	
}


//Установить заказ
function c_message_setProject(p_form, p_client_id) {
	new Ajax.Request(g_path+c_Message_ScriptFileName, {
		parameters: {
			'_func': 'SetProject', 
			'client_id': p_client_id
		},
		onSuccess: function(transport) {
			c_Common_SetFieldValues_end(transport, p_form, true);
		}
	});
}


//Функция определения получателя
//Если сообщение создали из закладки в разделе проекты, то получатели или клиент или ответственный в зависимости от текущего пользователя
//Если создали не из закладки, то просто назначим ответственного на основании выбраной услуги
function c_message_setRecipient(p_form, p_func, p_record_id) {
	new Ajax.Request(g_path+c_Message_ScriptFileName, {
		parameters: {
			'_func': p_func, 
			'record_id': p_record_id,
			'user_id': g_session_values['userid'],
			'user_type': g_session_values['userrolecode']
		}, 
		onSuccess: function(transport) {
			c_Common_SetFieldValues_end(transport, p_form, true);
		}
	});
}


//Значения по умолчанию
function c_message_setDefaultValues(p_form) {
	//Дата сообщения
	var today = new Date();
	p_form.MessageDate.value = today.toFormattedString(true); // 'DA-MO-YE НО:MI'

	//Статус
	var i = 0;
	for (i=0; i<p_form.StatusID.options.length; i++) {
		if (p_form.StatusID.options[i].getAttribute('code') == 'New') {
			p_form.StatusID.selectedIndex = i;
			break;
		}
	}
	
	//Важность
	var i = 0;
	for (i=0; i<p_form.ImportanceID.options.length; i++) {
		if (p_form.ImportanceID.options[i].getAttribute('code') == 'Medium') {
			p_form.ImportanceID.selectedIndex = i;
			break;
		}
	}
	
	//Автор
	p_form.AutorID.setAttribute('original_value', g_session_values['username']);
	SetLookupValue(p_form.AutorID, g_session_values['userid']);
	p_form.AutorID.value = g_session_values['username'];
}


//После сохранения
function c_message_onAfterSave(p_rec_id, p_mode) {
    if (p_mode == 'insert') {
		//Изменить доступ (чтение)
		new Ajax.Request(g_path+c_Message_ScriptFileName, {
			parameters: {
				'_func': 'ChangeAccess', 
				'rec_id': p_rec_id
			}
		});

		//Послать уведомление получателю сообщения
		new Ajax.Request(g_path+c_Message_ScriptFileName, {
			parameters: {
				'_func': 'SendEmailToUser', 
				'rec_id': p_rec_id
			}
		}); 
	}
}


//Отметить прочитавших
function WriteWhoReaded(p_grid, p_rec_id) {
	new Ajax.Request(g_path+c_Message_ScriptFileName, {
		parameters: {
			'_func': 'SaveReaded', 
			'rec_id': p_rec_id
		}
	});
	
	try {
		var grid = $(p_grid);
		var row = grid.getAttribute('selectedrow');
		if (row < 0)
			return;
		$(grid.rows[row]).removeClassName('grid_newmessage'); // отмечаем строчку как прочитаную
	} catch (e) {}	
}


//Устанавливает поля тема, кому, заказ (если карточку открыли в режиме "ответить")
function setReplyFields(p_form) {
	var res_array = p_form._parent_id.value.split('#');
	p_form._parent_id.value = res_array[2];
	var rec_id = res_array[1];
	
	new Ajax.Request(g_path+c_Message_ScriptFileName, {
		parameters: {
			'_func': 'GetReplyFields', 
			'message_id': rec_id
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			//Тема
			p_form.Subject.value = result.subject;
			//Заказ
			if (Object.isUndefined(result.project_id) == false) {
				tmp_setLookupValue(p_form.ProjectID, result.project_id, result.project_name);
			}
			//Кому
			if (Object.isUndefined(result.recipient_id) == false) {
				tmp_setLookupValue(p_form.RecipientID, result.recipient_id, result.recipient_name);
			}
			
			//Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
			p_form._hash.value = GetCardMD5(get_window_id(p_form));
		}
	});	
}


// TODO: написать нормальную функию и добавить в общие
function tmp_setLookupValue(p_elem, p_value, p_caption) {
	p_elem.setAttribute('original_value', p_caption);
	p_elem.value = p_caption;
	SetLookupValue(p_elem, p_value);
}

function c_message_reply(p_wnd_id) {
	var card_form = $(p_wnd_id).getElementsByTagName("form")[0];
	openCard('grid', 'Message', '', '#'+card_form._id.value+'#'+card_form._parent_id.value);
	CloseCardWindow(card_form.btn_cancel);
}