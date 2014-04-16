//********************************************************************
// Скрипт карточки Пользователи
//********************************************************************

function users_card_init(p_wnd_id) {
	// находим форму карточки
	var card_form = $(p_wnd_id).getElementsByTagName("form")[0];
	// если изменяем, то дизаблим ФИО
	//if (card_form._mode.value != 'insert') {
	//	card_form.Name.setAttribute('disabled', '');
	//	card_form.Name.className = 'edtText_disabled';
	//}

	// колонку пароль делаем небазовой, создаем небазовую для подтверждения и базовую скрытую для значения
	var l_pwd = card_form.Password.value;
	card_form.Password.value = '';
	$($(card_form).Password).writeAttribute('id', '_Password_1');

	var l_table = '';
	l_table = $(card_form).down('table');

	$($(l_table).rows[3].cells[0]).update('Подтверждение');
	$($(l_table).rows[3].cells[1]).update(
		'<input id="_Password_2" type="password" onblur="this.className = '+"'"+'edtText'+"'"+'" onfocus="this.className = '+"'"+'edtText_selected'+"'"+'" value="" mandatory="no" style="width: 100%;" class="edtText"/>' +
		'<input id="Password" type="hidden" value="' + l_pwd + '"/>');

	if ($($(card_form).btn_ok)) {
		$($(card_form).btn_ok).writeAttribute('onclick', '');
		$($(card_form).btn_ok).observe('click', function() {
			create_password_and_apply(p_wnd_id);
		} );
	}
	
	// miv 07.07.2010: Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
	card_form._hash.value = GetCardMD5(p_wnd_id);	
}

function create_password_and_apply(p_wnd_id) {
	// находим форму карточки
	var card_form = $(p_wnd_id).getElementsByTagName("form")[0];
	var l_pwd_1 = card_form._Password_1;
	var l_pwd_2 = card_form._Password_2;

	// проверки на правильность заполнения или не заполнения полей
	
	//////////////////////////////////////////////////////////////
	var l_table = '';
	if ($(l_pwd_1).value != $(l_pwd_2).value) {
		l_table = $(card_form).Password.parentNode.parentNode.parentNode.parentNode;
		$($(l_table).rows[4].cells[1]).update('<font color="red">Пароли не совпадают!</font>');
		return;
	}

	if (($(card_form)._mode.value == 'insert') && (($(l_pwd_1).value == '') || ($(l_pwd_1).value == null))) {
		l_table = $(card_form).Password.parentNode.parentNode.parentNode.parentNode;
		$($(l_table).rows[4].cells[1]).update('<font color="red">Пароль не задан!</font>');
		return;
	}

	// если пароль не меняли, то сохраним что было. request для получения хеша не нужен
	if (($(l_pwd_1).value == '') || $(l_pwd_1).value == null) {
		apply_card_changes('save_and_close', p_wnd_id);
		return;
	}
	
	card_form.Password.value = hex_md5($(l_pwd_1).value);
	apply_card_changes('save_and_close', p_wnd_id);
}