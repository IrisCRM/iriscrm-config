//********************************************************************
// функции для смены ответсвенного в разделах комапнии и контакты
//********************************************************************

var common_CHOWN_ScriptFileName = '/config/common/Lib/chown.php';


// нарисуем кнопку "Сменить ответсвенного"
function c_common_drawChownBtn(p_mode, p_wnd_id) {
	var l_form = $(p_wnd_id).getElementsByTagName("form")[0];
	var button_html = '';
	
	var btn_disabled = '';
	if ($(l_form).OwnerID.value == '') {
		btn_disabled = 'disabled="disabled"';
	}
	
	// фальшивый lookup для выбора ответсвенного
	var btn_title = 'Смена ответственного для текущей записи и всех дочерних записей (вкладок), у которых ответственный = ответственному текущей записи. При этом, для этих записей, права доступа у нового ответственного будут такими, как у старого ответственного. У старого ответственного доступ снимается.';
	button_html += '<table><tbody><tr><td style="width: 0px"><input type="text" style="display: none;" elem_type="lookup" original_value="" value="" lookup_value="" lookup_column="Name" lookup_grid_source_name="Contact" lookup_grid_source_type="grid" is_lookup="Y" mandatory="no" id="_chownID" filter_column="AT.Code" filter_value="Your" filter_null="no" /></td><td width="20"><input type="button" class="button" onclick="openlookupwindow(this);" value="'+T.t('Сменить ответственного')+'" title="'+btn_title+'" id="_chownID_btn" '+btn_disabled+'/></td></tr></tbody></table>';

	//mnv: исправил для ie
	$($(l_form).btn_cancel.parentNode.parentNode.parentNode.getElementsByTagName('td')[0]).update(button_html);

	$($(l_form)._chownID).observe('lookup:changed', function() {
		c_common_chown(p_mode, $(l_form)._id.value, $(l_form)._chownID.getAttribute('lookup_value'));
		c_Common_SetElementValue($(l_form).OwnerID, c_Common_MakeFieldValue($(l_form)._chownID.getAttribute('lookup_value'), $(l_form)._chownID.value));
	});
}

// отослать запрос на сервер для смены ответсвенного
function c_common_chown(p_mode, p_record_id, p_OwnerID_B) {
	var message = '<table id="chown_table" class="chown_table"><tbody><tr><td>';
	message += 'Идет смена ответсвенного:<br>';
	message += '<div class="chown_div">';
	message += '<ul id="chown_ul" class="chown_ul">';
	var num = 0;
	if (p_mode == 'account') {
		message += '<li id="li_item_'+num+'" class="chown_li" tab="">Компании</li>'; num++;
		message += '<li id="li_item_'+num+'" class="chown_li" tab="contact">Контакты</li>'; num++;
		message += '<li id="li_item_'+num+'" class="chown_li" tab="object">Объекты</li>'; num++;
		message += '<li id="li_item_'+num+'" class="chown_li" tab="email">Почта</li>'; num++;
		message += '<li id="li_item_'+num+'" class="chown_li" tab="project">Заказы</li>'; num++;
	}
	else
	if (p_mode == 'contact') {
		message += '<li id="li_item_'+num+'" class="chown_li" tab="">Контакты</li>'; num++;
		message += '<li id="li_item_'+num+'" class="chown_li" tab="object">Объекты</li>'; num++;
		message += '<li id="li_item_'+num+'" class="chown_li" tab="email">Почта</li>'; num++;
		message += '<li id="li_item_'+num+'" class="chown_li" tab="project">Заказы</li>'; num++;
	}
	else
	if (p_mode == 'project') {
		message += '<li id="li_item_'+num+'" class="chown_li" tab="">Заказы</li>'; num++;
	}

	message += '<li id="li_item_'+num+'" class="chown_li" tab="task">Дела</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="message">Общение</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="payment">Платежи</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="incident">Инцинденты</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="offer">КП</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="pact">Договоры</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="invoice">Счета</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="factinvoice">Накладные</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="document">Документы</li>'; num++;
	message += '<li id="li_item_'+num+'" class="chown_li" tab="file">Файлы</li>'; num++;

	message += '</ul>';
	message += '</div>';
	message += '</td></tr></tbody></table>';
	Dialog.alert(message, {
		onOk: function() { Dialog.closeInfo(); }, 
		className: "iris_win", 
		width: 250, 
		height: null, 
		buttonClass: "button", 
		okLabel: "Закрыть"
	});
	$('chown_table').parentNode.parentNode.getElementsByTagName('input')[0].setAttribute('disabled', 'disabled');
	
	$('li_item_0').className = 'chown_li_now';
	new Ajax.Request(g_path+common_CHOWN_ScriptFileName, {
		parameters: {
			'_func': 'ChangeOwner', 
			'mode': p_mode, 
			'record_id': p_record_id, 
			'owner_b_id': p_OwnerID_B
		},
		onComplete/*onSuccess*/: function(transport) {
			//debug('комании');
			$('li_item_0').className = 'chown_li_ok';
			c_common_chown_child(p_mode, p_record_id, p_OwnerID_B, 1);
		}
	}); 
}

//Рекурсивная функция для смены ответственного во всех связанных сущностях
function c_common_chown_child(p_mode, p_record_id, p_OwnerID_B, p_i) {
	if (p_i == $('chown_ul').children.length) {
		$('chown_table').parentNode.parentNode.getElementsByTagName('input')[0].removeAttribute('disabled');
		return;
	}
	var table = $('chown_ul').children[p_i].getAttribute('tab');
	try {	
		$('chown_ul').children[p_i].className='chown_li_now';
	} 
	catch (e) {}
	new Ajax.Request(g_path+common_CHOWN_ScriptFileName, {
		parameters: {
			'_func': 'ChangeChildOwner', 
			'mode': p_mode, 
			'record_id': p_record_id, 
			'owner_b_id': p_OwnerID_B, 
			'table': table
		},
		onComplete/*onSuccess*/: function(transport) {
			try {
				$('chown_ul').children[p_i].className = 'chown_li_ok';
			} 
			catch (e) {}
			c_common_chown_child(p_mode, p_record_id, p_OwnerID_B, p_i+1);
		}
	}); 
}
