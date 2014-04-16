//********************************************************************
// Скрипт раздела "Мои настройки (для клиентов)"
//********************************************************************

var c_MyPrefs_ScriptFileName = '/config/sections/Mypref/u_mypref.php';


function draw_mypref() {
	var section_html  = '<div class="myprefs_div">';
	section_html += '<div class="myprefs_item myprefs_contact" onclick="openCard(\'grid\', \'Mycontact\', \''+g_session_values['userid']+'\', \'\')">Мои данные</div>';
	section_html += '<div class="myprefs_item myprefs_pwd" onclick="openChangePwdWindow();">Сменить пароль</div>';
	section_html += '</div>';

	g_Prepare_Custom_Section(section_html);
}


function openChangePwdWindow() {
	// id будущего окна. должно быть случайное, без символа _ !!!
	var win_id = "wnd"+(Math.random()+"").slice(3);

	var win = new Window( {
		id: win_id, 
		className: "iris_win", 
		title: "Смена пароля", 
		width: 350, 
		height: 100
	}); 
	$(win).setConstraint(true, {
		left: 5, 
		right: 5, 
		top: 5, 
		bottom: 5
	});

	var form_html = '';
	form_html += '<form>';
	form_html += '<table class="form_table" width="100%">';
	form_html += '<tbody>';
	form_html += '	<tr class="form_row">';
	form_html += '		<td class="form_table" width="1%" align="left">	<nobr><b>Текущий пароль</b><br/> </nobr> </td>';
	form_html += '		<td class="form_table" width="75%" colspan="3">';
	form_html += '			<input id="curpwd" class="edtText" type="password" autocomplete="off" elem_type="text" onblur="this.className = \'edtText\';" onfocus="this.className = \'edtText_selected\';" value="" mandatory="yes" style="width: 100%;"/>';
	form_html += '		</td>';
	form_html += '	</tr>';

	form_html += '	<tr class="form_row">';
	form_html += '		<td class="form_table" width="1%" align="left">	<nobr><b>Новый пароль</b><br/> </nobr> </td>';
	form_html += '		<td class="form_table" width="75%" colspan="3">';
	form_html += '			<input id="newpwd1" class="edtText" type="password" autocomplete="off" elem_type="text" onblur="this.className = \'edtText\';" onfocus="this.className = \'edtText_selected\';" value="" mandatory="yes" style="width: 100%;"/>';
	form_html += '		</td>';
	form_html += '	</tr>';

	form_html += '	<tr class="form_row">';
	form_html += '		<td class="form_table" width="1%" align="left">	<nobr><b>Подтверждение</b><br/> </nobr> </td>';
	form_html += '		<td class="form_table" width="75%" colspan="3">';
	form_html += '			<input id="newpwd2" class="edtText" type="password" autocomplete="off" elem_type="text" onblur="this.className = \'edtText\';" onfocus="this.className = \'edtText_selected\';" value="" mandatory="yes" style="width: 100%;"/>';
	form_html += '		</td>';
	form_html += '	</tr>';
	
	form_html += '<tr class="form_table_buttons_panel">';
	form_html += '<td colspan="4">';
	form_html += '<table class="form_table_buttons_panel">';
	form_html += '<tbody>';
	form_html += '<tr>';
	form_html += '<td align="right">';
	form_html += '<input type="button" onclick="SetNewPassword(this)" value="Поменять" style="width: 70px;" class="button" id="btn_ok"/>';
	form_html += '<input type="button" onclick="Windows.close(get_window_id(this))" value="Отмена" style="width: 70px;" class="button" id="btn_cancel"/>';
	form_html += '</td>';
	form_html += '</tr>';
	form_html += '</tbody>';
	form_html += '</table>';
	form_html += '</td>';
	form_html += '</tr>';
	
	form_html += '</tbody>';
	form_html += '</table>';
	form_html += '</form>';

	$(win).getContent().update(form_html);
	
	$(win).setDestroyOnClose(); 
	$(win).toFront();
	$(win).setZIndex(Windows.maxZIndex + 1);// для исправления глюка IE с просвечиванием списков
	$(win).showCenter(0);
}

function SetNewPassword(p_this) {
	new Ajax.Request(g_path+c_MyPrefs_ScriptFileName, {
		parameters: {
			'_func': 'ChangePassword', 
			'current': $(p_this).form.curpwd.value, 
			'new1': $(p_this).form.newpwd1.value, 
			'new2': $(p_this).form.newpwd2.value
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText;
			var message = '';
			try {
				result = result.evalJSON();
				if (result.errm == '') {
					message = 'Ваш пароль успешно изменен';
					Windows.close(get_window_id($(p_this)));
				}
				else {
					message = result.errm;
				}
			} 
			catch (e) {
				message = 'Невозможно сменить пароль';
			}
			if (message != '') {
				wnd_alert(message);
			}
		}
	});
}
