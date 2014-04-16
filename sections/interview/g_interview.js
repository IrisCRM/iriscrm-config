//********************************************************************
// Раздел "Интервью" (вкладка в разделе "Опросы"). Таблица.
//********************************************************************

var g_Interview_ScriptFileName = '/config/sections/interview/g_interview.php';


//Инициализация таблицы
function g_Interview_init(p_grid_id) {

	var elem = getGridFooterTable(p_grid_id);
	var poll_id = $(p_grid_id).getAttribute('detail_parent_record_id');
  //Если мы находимся не на вкладке "Интервью", а в разделе Интервью, то ничего не делаем.
  if (IsEmptyGUIDValue(poll_id)) {
    //Печатные формы
    printform_createButton(p_grid_id, T.t('Печать')+'&hellip;');
    return;
  }
	var btn_cont = $(elem.rows[0].cells[0]);
	
	// новая кнопка "добавить..."
	var addrep_button_html = '<input type="button" title="Добавить клиентов в опрос" value="Добавить..." '+
    'onclick="g_Interview_addfromreport(\''+p_grid_id+'\', \''+poll_id+'\')" style="width: 90px;" class="button">';
	var addrep_button_html = btn_cont.next().insert({'bottom':addrep_button_html}).down('input[value="Добавить..."]');
}

function g_Interview_addfromreport(p_grid_id, p_poll_id) {
	var form = showParamsWindow({
		reportcode: 'mailing_contact', 
		okLabel: "Добавить", 
		onOk: function(p_form) { g_Interview_AddFromReport(p_form, p_grid_id, p_poll_id); } 
	}); // common/Lib/reportlib.js
}

function g_Interview_AddFromReport(p_form, p_grid_id, p_poll_id) {
	new Ajax.Request(g_path+g_Interview_ScriptFileName, {
		parameters: {
			'_func': 'AddFromReport', 
			'_reportcode': 'mailing_contact',
			'_pollid': p_poll_id,
			'_filters': Object.toJSON(getReportFilters(p_form))
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			if (result.errno != 0) {
				wnd_alert(result.errm);
			} 
      else {
				Windows.close(get_window_id(p_form));
				redraw_grid(p_grid_id);
			}
		}
	});
}
