//********************************************************************
// Скрипт карточки прав доступа
//********************************************************************
var c_Access_ScriptFileName = '/config/common/Template/c_access.php';

function access_card_init(p_wnd_id) {
	var card_form = $(p_wnd_id).down('form');

	// miv 22.04.2011: значения по умолчанию
	if (card_form._mode.value == 'insert') {
		c_Common_SetElementValue(card_form.R, {'Value': '0'});
		c_Common_SetElementValue(card_form.W, {'Value': '0'});
		c_Common_SetElementValue(card_form.D, {'Value': '0'});
		c_Common_SetElementValue(card_form.A, {'Value': '0'});
	}
	
	// miv 25.10.2010: массовая смена доступа
	if (card_form._params.value.isJSON() == true) {
		var params = card_form._params.value.evalJSON();
		if (params.mode == 'mass_update') {
			var wnd = Windows.getWindow(p_wnd_id);
			wnd.setTitle("<b>Массовая смена доступа</b>");
			var header_title_cont = wnd.getContent().down('tr.card_header_middle_row').update('');
			//header_title_cont.update("Для выбранных записей будут применены указанные права доступа");
			
			$(card_form['RecordID']).up('tr[class="form_row"]').setStyle({'display': "none"}).insert({after: '<tr class="form_row"><td class="form_table" style="text-align: center" colspan=4>Укажите правило доступа, которое будет применено к '+params.id_list.length+' '+getNumberCaption(params.id_list.length, ['выбранной записи', 'выбранным записям', 'выбранным записям'])+'</td></tr>'});
			$(card_form.btn_ok).hide_().insert({after: '<input type="button" class="button" value="'+T.t('Применить доступ')+'" onclick="access_applyMassAccess(this)"/>'});
			
			var button_cont = $($(card_form.btn_cancel).up('table.form_table_buttons_panel').rows[0].cells[0]);
			var tmp_id = '_'+(Math.random()+'').slice(3);
			card_form.setAttribute('cb_id', tmp_id);
			var dj = "{&quot;value&quot;:&quot;&quot;,&quot;row_type&quot;:{&quot;0&quot;:&quot;domain&quot;},&quot;domain_values&quot;:[&quot;0&quot;,&quot;1&quot;],&quot;domain_captions&quot;:[&quot;\u041d\u0435\u0442&quot;,&quot;\u0414\u0430&quot;]}";
			button_cont.insert({top: '<input id="'+tmp_id+'" type="checkbox" class="checkbox" domain_json="'+dj+'" checked_index="0" checked/> <span style="margin: 3px"><label style="cursor: pointer" for="'+tmp_id+'" title="Исключить из доступа: Если вы полностью снимаете доступ, то указанный пользователь (роль) будет исключён из доступа к записи. Не исключать из доступа: Если вы полностью снимаете доступ, то указанный пользователь (роль) всё ещё может иметь доступ к записи, если если это позволяют другие записи во вкладке &quot;Доступ&quot;.">Исключить из доступа</label></span>'});
			return;
		}
	}
	
	// устанавливаем значение RecordID
	card_form['RecordID'].value = card_form['_detail_column_value'].value;
	// скрываем первый столбец, который содержит ID родительской записи
	$(card_form['RecordID']).up('tr[class="form_row"]').setStyle({'display': "none"});
//	$(card_form['RecordID'].parentNode.parentNode).setStyle({'display': "none"});

	// получаем объект окна
	Windows.focus(p_wnd_id);
	var wnd = Windows.getFocusedWindow();
	
	card_form._hash.value = GetCardMD5(p_wnd_id);
	
	//Уменьшим высоту карточки
	UpdateCardHeight(p_wnd_id);
}

function access_applyMassAccess(p_this) {
	var params = p_this.form._params.value.evalJSON();
	new Ajax.Request(g_path + c_Access_ScriptFileName, {
		parameters: {
			'_func': 'applyAccess', 
			'table': params.table,
			'id_list': Object.toJSON(params.id_list),
			'access': Object.toJSON({
				accessroleid: c_Common_GetElementValue(p_this.form.AccessRoleID), 
				userid: c_Common_GetElementValue(p_this.form.ContactID), 
				r: c_Common_GetElementValue(p_this.form.R), 
				w: c_Common_GetElementValue(p_this.form.W), 
				d: c_Common_GetElementValue(p_this.form.D), 
				a: c_Common_GetElementValue(p_this.form.A),
				mode: ((p_this.form[p_this.form.getAttribute('cb_id')].checked == true) ? 'strict' : 'soft')
			})
		}, 
		onComplete: function(transport) {
			try {
				var result = transport.responseText.evalJSON();
				if (result.success == 0)
					wnd_alert(result.message, 350);
				else {
					Dialog.confirm(result.message,{onOk:function() {Dialog.closeInfo(); p_this.form._hash.value = 'close'; p_this.form.btn_cancel.onclick()}, className: "iris_win", width: 300, height:null, buttonClass:"button", okLabel:"Ок", cancelLabel:"Продолжить"});
				}
			} catch (e) {
				wnd_alert('Внимание! Не удалось изменить доступ у выбранных записей', 350);
			}
		}
	});	
}
