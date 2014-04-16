//********************************************************************
// Раздел "E-mail". Вкладка файлы. Таблица.
//********************************************************************
c_EmailFile_ScriptFileName = '/config/sections/Email/dc_file.php';

//Инициализация таблицы
function g_email_file_grid_init(p_grid_id) {
	var elem = getGridFooterTable(p_grid_id);
	var email_id = $(p_grid_id).getAttribute('detail_parent_record_id');

	// новая кнопка удалить
	var del_button_html = '<input type="button" title="Удалить выбранную запись" value="Удалить" onclick="" new_del="yes" style="width: 70px;" class="button">';
	var old_del_btn = $(elem.rows[0].cells[0]).down('input[value="Удалить"]').hide_();
	var new_del_btn = old_del_btn.up().insert({'bottom':del_button_html}).down('input[new_del="yes"]');	
	new_del_btn.observe('click', function() {
		try {
			var t0_emailid = $(p_grid_id).down('tr.' + g_vars.selected_class).getAttribute('t0_emailid');
			var file_id = $(p_grid_id).down('tr.' + g_vars.selected_class).getAttribute('rec_id');
		} catch (e) { return; }
		
		// если обычный файл - то вызовем функцию его удаления и выйдем
		if (t0_emailid == email_id) {
			old_del_btn.onclick();
			return;
		} else {
			Dialog.confirm("Открепить данный файл от письма?<br>(сам файл удален не будет)",{onOk:function() {Dialog.closeInfo(); deattachEmailFile(p_grid_id, file_id, email_id);}, className: "iris_win", width: 300, height:null, buttonClass:"button", okLabel:"Да", cancelLabel:"Нет"});
		}
	});
	
	// кнопка применить доступ (фальшивый lookup)
	var add_button_html = '';
	var btn_title = 'Прикрепить к письму существующий файл';
	var btn_id = '_'+p_grid_id+'_file';
	var where = '(T0.emailid<>\''+email_id+'\' or T0.emailid is null) and T0.id not in (select EF.fileid from iris_email_file EF where EF.emailid = \''+email_id+'\')';
	add_button_html += '<form><table><tbody><tr><td style="width: 0px"><input type="text" style="display: none;" elem_type="lookup" original_value="" value="" lookup_value="" lookup_column="file_filename" lookup_grid_source_name="File" lookup_grid_source_type="grid" is_lookup="Y" mandatory="no" id="'+btn_id+'" filter_where="'+where+'" /></td><td width="20"><input type="button" class="button" onclick="openlookupwindow(this);" value="Прикрепить файл" title="'+btn_title+'" id="'+btn_id+'_btn"/></td></tr></tbody></table></form>';
	
	var new_button = $(elem.rows[0].cells[1]).insert({'bottom':add_button_html}).down('input[elem_type="lookup"]');
	new_button.observe('lookup:changed', function() {
		if (new_button.value == '')
			return;
		
		new Ajax.Request(g_path + c_EmailFile_ScriptFileName, {
			parameters: {'_func': 'AttachFile', 'file_id': new_button.getAttribute('lookup_value'), 'email_id': email_id}, 
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
}

function deattachEmailFile(p_grid_id, p_file_id, p_email_id) {
	new Ajax.Request(g_path + c_EmailFile_ScriptFileName, {
		parameters: {'_func': 'DeattachFile', 'file_id': p_file_id, 'email_id': p_email_id}, 
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