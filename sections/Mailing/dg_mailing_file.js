//********************************************************************
// Раздел "Рассылка" Закладка "Файлы"
//********************************************************************
var c_Mailing_file_ScriptFileName = '/config/sections/Mailing/dc_file.php';

//Инициализация таблицы
function g_mailing_file_grid_init(p_grid_id) {
	var elem = getGridFooterTable(p_grid_id);
	var mailing_id = $(p_grid_id).getAttribute('detail_parent_record_id');
	var btn_cont = $(elem.rows[0].cells[0]);
	
	// прячем старые кнопки
	jQuery(btn_cont).find('input').hide();

	
	// кнопка применить доступ (фальшивый lookup)
	var add_button_html = '';
	var btn_title = 'Прикрепить к письму существующий файл';
	var btn_id = '_'+p_grid_id+'_file';
	var where = 'T0.id not in (select MF.fileid from iris_mailing_file MF where MF.mailingid = \''+mailing_id+'\')';
	var add_button_html = '<form><table><tbody><tr><td style="width: 0px"><input type="text" style="display: none;" elem_type="lookup" original_value="" value="" lookup_value="" lookup_column="file_filename" lookup_grid_source_name="File" lookup_grid_source_type="grid" is_lookup="Y" mandatory="no" id="'+btn_id+'" filter_where="'+where+'" /></td><td width="20"><input type="button" class="button" onclick="openlookupwindow(this);" value="Прикрепить файл" title="'+btn_title+'" id="'+btn_id+'_btn"/></td></tr></tbody></table></form>';
	var new_button = btn_cont.insert({'bottom':add_button_html}).down('input[elem_type="lookup"]');
	new_button.observe('lookup:changed', function() {
		if (new_button.value == '')
			return;
		
		new Ajax.Request(g_path + c_Mailing_file_ScriptFileName, {
			parameters: {'_func': 'AttachFile', 'file_id': new_button.getAttribute('lookup_value'), 'mailing_id': mailing_id}, 
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
	
	
	// новая кнопка "удалить"
	var del_button_html = '<input type="button" title="Удалить выбранную запись" value="Удалить" onclick="" style="width: 70px;" class="button">';
	var new_del_btn = btn_cont.next().insert({'bottom':del_button_html}).down('input[value="Удалить"]');
	new_del_btn.observe('click', function() {
		try {
			var file_id = $(p_grid_id).down('tr.' + g_vars.selected_class).getAttribute('rec_id');
		} catch (e) { return; }
		Dialog.confirm("Открепить данный файл от рассылки?<br>(сам файл удален не будет)",{onOk:function() {Dialog.closeInfo(); g_mailing_file_deattachFile(p_grid_id, file_id, mailing_id);}, className: "iris_win", width: 300, height:null, buttonClass:"button", okLabel:"Да", cancelLabel:"Нет"});
	});	
}

function g_mailing_file_deattachFile(p_grid_id, p_file_id, p_mailing_id) {
	new Ajax.Request(g_path + c_Mailing_file_ScriptFileName, {
		parameters: {'_func': 'DeattachFile', 'file_id': p_file_id, 'mailing_id': p_mailing_id}, 
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