//********************************************************************
// Раздел "E-mail". Таблица.
//********************************************************************

//Инициализация таблицы
function g_email_grid_init(p_grid_id) 
{
	var l_grid = $(p_grid_id);
	var code_str;
	var files_col_idx = getItemIndexByParamValue(l_grid.rows[0].cells, 'db_field', 'files');
	var star_col_idx = getItemIndexByParamValue(l_grid.rows[0].cells, 'db_field', 'star');
	var reply_col_idx = getItemIndexByParamValue(l_grid.rows[0].cells, 'db_field', 'reply');

	//По всем строчкам таблицы
	for (var i=1; i < $(l_grid).rows.length; i++) {
		if (($(l_grid).rows[i].getAttribute('rec_id') == '') || ($(l_grid).rows[i].getAttribute('rec_id') == null)) {
			break;
		}

		code_str = $(l_grid).rows[i].getAttribute('et_code');

		//Отметим непрочитанные письма
		if ((code_str == 'Inbox') && ($(l_grid).rows[i].getAttribute('t0_has_readed').indexOf(g_session_values['userid']) == -1)) {
			$($(l_grid).rows[i]).addClassName('grid_newmail');
		}
		
		// прорисуем вложения для писем
		try {
		var files_tr = $($(l_grid).rows[i].cells[files_col_idx]);
		if (files_tr.down('span').innerHTML == '@')
			var files_html = '<div class="email_attachment_logo"></div>';
		else
			var files_html = '';
		files_tr.update('').setStyle({padding: '0'}).update(files_html);
		} catch (e) {}
		
		// прорисуем звездочки для писем
		try {
		var star_td = $($(l_grid).rows[i].cells[star_col_idx]);
		var star_classname = 'email_star_logo';
		if (star_td.down('span').innerHTML == '*')
			star_classname += ' email_star_logo_on';
		star_td.update('').setStyle({padding: '0'}).update('<div class="'+star_classname+'" onclick="email_triggerStar(event)" ondblclick="Event.stop(event);"></div>');
		} catch (e) {}
		// прорисуем значки для писем, на которые есть ответы
		try {
		var reply_td = $($(l_grid).rows[i].cells[reply_col_idx]);
		if (parseInt(reply_td.up('tr').getAttribute('replycnt'), 10) > 0)
			reply_td.update('').setStyle({padding: '0'}).update('<div class="email_reply_logo" onclick="email_openReply(event)" ondblclick="Event.stop(event);" title="Нажмите, чтобы посмотреть ответ(ы) на письмо"></div>');
		else
			reply_td.update('');
		} catch (e) {}			
	}
	if (l_grid.getAttribute('parent_elem_id') != '')
		return; // если грид нарисован для lookup элемента, то кнопки рисовать не будем
	
	//Добавление кнопок на панель
	g_InsertUserButtons(p_grid_id, [
		{
			name: T.t('Ответить'), 
			onclick: "g_email_replyMessage('" + p_grid_id + "');"
		},
		{
			name: T.t('Отправить'), 
			onclick: "email_send('" + p_grid_id + "');"
		},
		{
			name: T.t('Проверить почту'), 
			onclick: "fetch_mail('" + p_grid_id + "', this);"
		}
	], 'iris_Email');
}


function g_email_replyMessage(p_grid_id) {
	var row = $(p_grid_id).getAttribute('selectedrow');
	var rec_id = $(p_grid_id).rows[row].getAttribute('rec_id');

	// открытие карточки, которой в качестве родителя передаем признак...
	openCard('grid', 'Email', '', '#'+rec_id+'#grid');
}


var g_is_email_fetching = 0;
//var g_email_count = 0;
function fetch_mail(p_grid_id, p_this) {
	p_this.value = T.t('Загрузка писем')+'&hellip;';
	p_this.setAttribute("disabled", "disabled");

	if (g_is_email_fetching == 1) {
		return;
	}
	//showDebug();
	debug('+++ начало считывания писем');
    new Ajax.Request(g_path+'/config/sections/Email/lib/mail.php', {
    	onComplete: function(transport) {
			//showDebug();
			g_is_email_fetching = 0;
			
			var footer_table = getGridFooterTable($(p_grid_id));
			try {
				var fetch_button = $(footer_table).down('input[value="'+T.t('Загрузка писем')+'&hellip;'+'"]');
				fetch_button.removeAttribute("disabled");
				fetch_button.value = T.t('Проверить почту');
			} catch (e) {}
            var result = transport.responseText;
			
			if ((result.toLowerCase().indexOf('maximum execution time', 0) > 0) || (result.toLowerCase().indexOf('allowed memory size') > 0)) {
				result = '{"messages_count": "1"}'; // если скрипт закончился из-за времени(или из-за нехватки памяти), то сделаем вид что он считал новые письма
			}
			
			if (result.isJSON() == true) {
				var res_json = result.evalJSON();
				if (res_json.messages_count == -1) {
					debug('проверка почты для ящика '+res_json.email_account+' завершилась ошибкой:');
					debug(res_json.error);
				} 
				else {
					debug('новых писем: '+res_json.messages_count);
				}
				//Если есть новые письма, то загрузим все что осталось
				if (res_json.messages_count > 0) {
					fetch_mail(p_grid_id, p_this);
				} else {
					$(p_grid_id).setAttribute('page_show_rec_id', '');
					redraw_grid(p_grid_id); // miv 10.09.2010: перерисуем грид после получения почты
				}
				/*
				else {
					if (g_email_count != 0) {
						debug('всего писем: '+g_email_count);
					}
					g_email_count = 0;
				}
				*/
			} 
			else {
				debug('ошибка проверки почты');
				debug(result);
			}
        }
    });
	g_is_email_fetching = 1;
}

function email_send(p_grid_id) {
	var rec_id = getGridSelectedID(p_grid_id);
    new Ajax.Request(g_path+'/config/sections/Email/lib/send.php', {
		parameters: {'id': rec_id, 'send_mode': 'Outbox'},
		onSuccess: function(transport) {
			var result = transport.responseText;
			if (result.isJSON() == true) {
				var res_json = result.evalJSON();
				var messageHTML = res_json.message;
				if (res_json.status == '+') {
					redraw_grid(p_grid_id);
				}
			}
			else {
				messageHTML = T.t('Возникла ошибка при отправке почты');
                messageHTML += ':<br><textarea class="edtText" style="margin: 10px 5px 0px 5px; width: 280px; heigh: 80px" readonly="true">'+result+'</textarea>';
			}
			wnd_alert(messageHTML, 300, 60);
		}
	});
}

function email_triggerStar(p_event) {
	var cell = Event.element(p_event);
	Event.stop(p_event); // прерываем просачивание события, чтобы не происходил выбор строки таблицы записей

	if (cell.hasClassName('email_star_logo_loading') == true)
		return; // если в данный момент происходит смена состояния звездочки, то выйдем
	
	cell.addClassName('email_star_logo_loading');
    new Ajax.Request(g_path+'/config/sections/Email/g_email.php', {
		parameters: {'_func': 'triggerStar', 'id': cell.up('tr').getAttribute('rec_id'), 'currentValue': cell.hasClassName('email_star_logo_on')},
		onSuccess: function(transport) {
			var result = transport.responseText;
			cell.removeClassName('email_star_logo_loading');
			if (result.isJSON() == true) {
				var res_json = result.evalJSON();
				if (res_json.success == 1) {
					cell.toggleClassName('email_star_logo_on');
				}
			}
		}
	});	
}

function email_openReply(p_event) {
	var cell = Event.element(p_event);
	Event.stop(p_event); // прерываем просачивание события, чтобы не происходил выбор строки таблицы записей
	var row = cell.up('tr');
	if (row.getAttribute('replycnt') == '1')
		openCard({source_name: 'Email', rec_id: row.getAttribute('replyfirstid')});
	else {
		// TODO: изменить способ передачи условия
		opengridwindow(Math.random(), '', 'grid', 'Email', " T0.id in (select E.id from iris_email E where E.parentemailid = '"+row.getAttribute('rec_id')+"')");
	}
}
