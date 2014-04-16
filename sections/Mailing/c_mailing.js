//********************************************************************
// Раздел "Рассылка". Карточка.
//********************************************************************

var c_Mailing_ScriptFileName = '/config/sections/Mailing/c_mailing.php';

//Инициализация карточки
function c_mailing_card_init(p_wnd_id) {
	//Форма карточки
	var l_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

	// можно выбрать только те почтовые аккаунты, с которых пользователь может отправлять письма
	l_form.emailaccountid.setAttribute('filter_where', "T0.id in (select OA.emailaccountid from iris_emailaccount_outboxaccess OA where OA.contactid=_iris_user_id[] and is_send='1')");

	l_form.StartDate.setAttribute('disabled', 'disabled');
	l_form.StartDate.setAttribute('id', '_StartDate');
	l_form.EndDate.setAttribute('disabled', 'disabled');
	l_form.EndDate.setAttribute('id', '_EndDate');

	// установим полю "Отвественный" фильтр, чтобы выводились только сотрудники "Вашей компании"
	l_form.ownerid.setAttribute('filter_where', "T0.accountid in (select A.id from iris_account A left join iris_accounttype AT on A.accounttypeid=AT.id where AT.code = 'Your')");

	if (l_form._mode.value == 'insert') {
		// заполним поле "Ответсвенный"
		c_Common_SetElementValue(l_form.ownerid, c_Common_MakeFieldValue(g_session_values.userid, g_session_values.username));
	}
	
	var button_html = '<input type="button" class="button" style="width: 140px" id="_send" value="Отправить рассылку" onclick="sendMailing(\''+l_form._id.value+'\', false)"/>';
	$($(l_form).btn_cancel.parentNode.parentNode.parentNode.getElementsByTagName('td')[0]).update(button_html);
	
	l_form._hash.value = GetCardMD5(p_wnd_id);	
}

function sendMailing(p_mailing_id, p_recursive) {
	if (p_recursive == false) {
		Dialog.alert('Рассылка отправляется<div id="mailing_operation" type="mailing_operation" style="color: #3E569C">Подготовка...</div><div type="progrssbar" style="width: 250px; height: 15px; margin: 10px 20px 0px; border: 1px solid #3E569C"><div type="pb_scale" style="width: 0%; height: 100%; background-color: #3E569C;"></div></div>', {width:300, height:100, className: "iris_win", buttonClass: "button", okLabel: "Отмена", onOk: function() {cancelMailing(0);} });
	}
	
	new Ajax.Request(g_path + c_Mailing_ScriptFileName, {
		parameters: {
			'_func': 'SendMailing', 
			'mailing_id': p_mailing_id
		}, 
		onComplete: function(transport) {
			try {
				var result = transport.responseText.evalJSON();
			} catch (e) {
				Dialog.closeInfo();
				wnd_alert('Возникла ошибка при отправке рассылки');
				return; // если вернулся пустой запрос, то прервем рассылку
			}
			
			var messagebox_op = $('mailing_operation');
			if (messagebox_op.getAttribute('cancel') == 'yes') {
				Dialog.closeInfo();
				wnd_alert('Рассылка писем прервана');
				return;
			}
			
			if (result.errno != 0) {
				Dialog.closeInfo();
				wnd_alert(result.errm);
				return;
			}
			
			if (result.sended < result.all) {
				sendMailing(p_mailing_id, true);
			}
		}
	});
	
	// периодическое обновление информации о рассылке
	new PeriodicalExecuter(function(pe) {
		new Ajax.Request(g_path + c_Mailing_ScriptFileName, {
			parameters: {
				'_func': 'GetMailingStatus', 
				'mailing_id': p_mailing_id
			},
			onComplete: function(transport) {
				try {
					var result = transport.responseText.evalJSON();
				} catch (e) {
					return;
				}
				
				var messagebox_op = $('mailing_operation');
				var messagebox = messagebox_op.up('div.iris_win_content');
				if (messagebox_op == null) {
					pe.stop();
					return;
				}
				
				messagebox_op.update('Отправлено писем '+result.sended+' из '+result.all);
				messagebox.down('div[type="pb_scale"]').setStyle({"width": (result.sended/result.all)*100 + '%'});
				
				if (result.sended >= result.all) {
					pe.stop();
					if ($('mailing_operation_closebtn') == null) {
						messagebox_op.setStyle({'color': '#37be0e'});
						messagebox.down('input.button').hide_().insert({'after': '<input id="mailing_operation_closebtn" type="button" class="button" value="Закрыть" onclick="Dialog.closeInfo()">'});
					}
				}
			}
		});
	}, 20);
}

function cancelMailing(p_errflag) {
	var messagebox = $(Windows.getFocusedWindow().getId());
	
	messagebox.down('div[type="mailing_operation"]').update('Отмена операции').setAttribute('cancel', 'yes');;
	messagebox.down('input.button').setAttribute('disabled', 'disabled');
}