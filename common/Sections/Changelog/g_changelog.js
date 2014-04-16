//********************************************************************
// Скрипт реестра записей закладки "История изменений"
//********************************************************************

var g_Changelog_ScriptFileName = '/config/common/Sections/Changelog/changelog.php';

//Инициализация реестра записей
function g_Changelog_Init(p_grid_id) {
	g_Changelog_switchmonitoring(p_grid_id, 'init', 0);
	//g_Changelog_marknewchanges(p_grid_id);
}


function g_Changelog_switchmonitoring(p_grid_id, p_mode, p_this) {
	var elem = getGridFooterTable(p_grid_id);
	if (p_this != 0) {
		p_this.setAttribute('disabled', 'disabled');
		$(p_this).up('table').down('label').setOpacity(0.5);
	}

	new Ajax.Request(g_path+g_Changelog_ScriptFileName, {
		parameters: {
			'_func': 'Changelog_SwitchMonitoring',
			'p_rec_id': $(p_grid_id).getAttribute('detail_parent_record_id'),
			'p_grid_id': p_grid_id,
			'p_mode': p_mode
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			$(elem.rows[0].cells[0]).update(result.html);
			g_Changelog_marknewchanges(p_grid_id);
		}
	});		
}

// miv 21.10.2009
function g_Changelog_marknewchanges(p_grid_id) {
	var l_grid = $(p_grid_id);
	
	var elem = getGridFooterTable(p_grid_id);
	var monitorDateStr = $(elem).down('input').getAttribute('date_str');
	if (monitorDateStr != '')
		var monitorDate = g_Changelog_StrToDate(monitorDateStr);
		
	//По всем строчкам таблицы
	for (var i=1; i < l_grid.rows.length; i++) {
		if (l_grid.rows[i].getAttribute('rec_id') == null)
			break;
		if (monitorDateStr == '') {
			$(l_grid.rows[i]).removeClassName('grid_newchangelog'); // если дата не указана, то просто снимем выделение со всех строк
		} else {
			var changeDate = g_Changelog_StrToDate(l_grid.rows[i].getAttribute('t0_changedate'));
			if ((changeDate >= monitorDate) && (l_grid.rows[i].getAttribute('t0_userid') != g_session_values.userid)) {
				$(l_grid.rows[i]).addClassName('grid_newchangelog');
			}
		}
	}
}

function g_Changelog_StrToDate(p_datestr) {
	// p_datestr = 05.01.2010 21:24
	//new Date(1969, 11, 31, 19);
	//-> '"1969-12-31 19:00:00"'
	var d_t = p_datestr.split(' ');
	var t = d_t[1].split(':');
	var d = d_t[0].split('.'); 

	return new Date(parseInt(d[2], 10), parseInt(d[1], 10)-1, parseInt(d[0], 10), parseInt(t[0], 10), parseInt(t[1], 10));
}

