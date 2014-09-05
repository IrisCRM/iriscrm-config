//***************************************************
// Функции для печатных форм					    
//***************************************************


// добавляет кнопку печатной формы на гриде 
function printform_createButton(p_grid_id, p_button_caption) {

//	if (g_session_values['userrolecode'] == 'Client')
//		return;

	if (p_button_caption == undefined)
		p_button_caption = T.t('Печать')+'&hellip';
		
	var elem = getGridFooterTable(p_grid_id);	
	
	var btn_id = 'el'+(Math.random()+"").slice(3);
	var button_html = '';

   //var filter_where = "displayinsection='1' and T1.code='"+$(p_grid_id).getAttribute('source_name')+"'";
    //var filter_where = "displayinsection='1' and T0.sectionid in (select sectionid from iris_table where code=lower('" + $(p_grid_id).getAttribute('table_name') + "'))";

    var filter_1 = "T0.sectionid in (select sectionid from iris_table where code=lower('" + $(p_grid_id).getAttribute('table_name') + "'))";
    var filter_2 = "T1.code='"+$(p_grid_id).getAttribute('source_name')+"'";
	if ($(p_grid_id).getAttribute('detail_name') != '') {
		var filter_where = "displayinsection='1' and "+filter_1; // если вкладка, то ищем ПФ по таблице (но новому)
	} else {
		var filter_where = "displayinsection='1' and ("+filter_2+")"; // если радел, то ищем по разделу (по старому)
	}

	g_InsertUserButtons(p_grid_id, [
		{
			name: p_button_caption, 
			onclick: '$(\''+btn_id+'_btn\').click(); $(\''+btn_id+'\').stopObserving(\'lookup:changed\'); $(\''+btn_id+'\').observe(\'lookup:changed\', function() {printform_show(\'' + p_grid_id + '\', $(\''+btn_id+'\'));});'
		}
	], undefined, 'pf_button');

	// TODO: Вынести в представление
    button_html += '<div style="display: none" id="fake_wnd_'+btn_id+'" class="dialog">';
	button_html += '<form style="display: none"> <table><tbody><tr><td><input type="text" style="display: none" elem_type="lookup" original_value="" value="" lookup_value="" filter_where="' + filter_where + '" filter_null="no" lookup_column="Name" lookup_grid_source_name="Printform" lookup_grid_source_type="grid" is_lookup="Y" mandatory="no" id="'+btn_id+'"/></td><td width="20"><input type="button" class="button" onclick="openlookupwindow(this)" value="Печатная форма..." id="'+btn_id+'_btn"/></td></tr></tbody></table></form>';
	button_html += '</div>';
	g_InsertUserButtons(p_grid_id, button_html, undefined, 'pf_button');
}

function printform_show(p_grid_id, p_lookup_elem) {
  var rec_id = getGridSelectedID(p_grid_id);
  printform_showbyid(rec_id, p_lookup_elem);
}

function printform_showbyid(rec_id, p_lookup_elem) {
  if (p_lookup_elem.getAttribute('lookup_value') == '') {
    return;
  }

  if (rec_id != '') {
    window.open(g_path + '/core/engine/printform.php?_func=render&record_id=' + 
        rec_id + '&id=' + p_lookup_elem.getAttribute('lookup_value'));
  }
  SetLookupValue(p_lookup_elem, '');
}

function printform_showbycode(rec_id, code) {
  if (rec_id != '' && code != undefined) {
    window.open(g_path + '/core/engine/printform.php?_func=render&record_id=' + 
        rec_id + '&code=' + code);
  }
}

function printform_createCardHeaderButton(p_wnd_id, p_position, p_caption) {
	if (p_caption == undefined)
		p_caption = T.t('Печать')+'&hellip;';
		
	if ($(p_wnd_id).down('div.card_header_div') == null)
		return; // если верхней панели нету, то выйдем
	var btn_container = $(p_wnd_id).down('div.card_header_div').down('div.card_'+p_position+'_buttons_div');
	if (btn_container == null) {
		return; // если неверно указана позиция кнопки (top|bottom), то выйдем
	}
	if (btn_container.innerHTML != '')
		btn_container.insert({'bottom': '<span class="card_header_separator">|</span>'})
	
	var form = $(p_wnd_id).getElementsByTagName("form")[0];
    var filter_where = "displayinsection='1' and ( T0.sectionid in (select sectionid from iris_table where code=lower('" + form._table.value + "')) )";
	
	var btn_id = 'el'+(Math.random()+"").slice(3);	
	var button_html = '';
    button_html += '<div id="fake_wnd_'+btn_id+'" class="dialog">';
	button_html += '<span style="display: none"> <table><tbody><tr><td><input type="text" style="display: none" elem_type="lookup" original_value="" value="" lookup_value="" filter_where="' + filter_where + '" filter_null="no" lookup_column="Name" lookup_grid_source_name="Printform" lookup_grid_source_type="grid" is_lookup="Y" mandatory="no" id="'+btn_id+'"/></td><td width="20"><input type="button" class="button" onclick="openlookupwindow(this)" value="Печатная форма..." id="'+btn_id+'_btn"/></td></tr></tbody></table></span>';
	button_html += '</div>';
	button_html += '<span class="card_top_panel_button" onclick="if (common_cardIsSaved(\''+p_wnd_id+'\', 1) == 1) { $(\''+btn_id+'_btn\').click(); $(\''+btn_id+'\').stopObserving(\'lookup:changed\'); $(\''+btn_id+'\').observe(\'lookup:changed\', function() {printform_showbyid(\'' + form._id.value + '\', $(\''+btn_id+'\'));}); }">'+p_caption+'</input>';
		
	btn_container.insert({'bottom': button_html});
	return btn_container.children[btn_container.children.length-1];
}