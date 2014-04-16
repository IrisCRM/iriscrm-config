//************************************************************************
// Скрипт карточки раздела "История" (открывается из GRID_WND напоминания)
//************************************************************************
var g_Changemonitor_ScriptFileName = '/config/sections/Changemonitor/c_changemonitor.php';

// Инициализация карточки
function g_Changemonitor_CardInit(p_wnd_id) {
	//Форма карточки
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];
	var rec_id = card_form.recordid.value;
	var table_id = card_form.TableID.options[card_form.TableID.selectedIndex].value;


	var wnd_id = get_window_id(card_form);
	//Windows.close(wnd_id); // закроем текущее окно
	Windows.focus(wnd_id);
	var wnd =Windows.getFocusedWindow();
	wnd.setHTMLContent('<table width=100% height=100%><tr><td align="center" style="vertical-align: middle"><img src="core/templates/' + g_layout_params.STYLE_NAME + '/images/card_loading.gif"/></td></tr></table>');
	
	new Ajax.Request(g_path+g_Changemonitor_ScriptFileName, {
		parameters: {
			'_func': 'Changelog_GetCardInfo',
			'p_table_id': table_id
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			
			var dictonary_name = result.dictionary;
			var section_name = result.section;
			var detail_name = result.detail;
			
			Windows.close(wnd_id); // закроем текущее окно
			if (result.section != '') {
				openCard('grid', result.section, rec_id);
			}
		}
	});		
}

// Инициализация реестра
function g_Changemonitor_GridInit(p_grid_id) {
	$(p_grid_id).observe('dblclick', function(event) {
		var element = Event.element(event);
		if ('TR' == element.up('tr').tagName)
			g_Changemonitor_GridClick(element.up('tr'));
	});
}

function g_Changemonitor_GridClick(p_this) {
	//alert(p_this.getAttribute('t0_recordid'));

	//dictionary - имя справочника
	//SectionID - select отображающий имя раздела
	//detail - имя закладки

	var dictonary_name = p_this.getAttribute('tt_dictionary');
	var section_name = p_this.getAttribute('ts_code');
	var detail_name = p_this.getAttribute('tt_detail');
	
	if (section_name != '') {
		openCard('grid', section_name, p_this.getAttribute('t0_recordid'));
	}
}
