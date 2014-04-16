/********************************************************************************************************
функции для копирования прав доступа дочерним записям (клиент)
********************************************************************************************************/
/*
function applyaccess_drawButton(p_wnd_id, p_table_name) {
	addCardHeaderButton(p_wnd_id, 'top', T.t('Применить доступ'), 'applyaccess_ask(this, \''+p_table_name+'\')', 'Применить права доступа от этой записи ко всем дочерним записям');
}

function applyaccess_ask(p_this, p_table_name) {
	Dialog.confirm("Права доступа данной записи будут применены ко всем ее дочерним записям. Продолжить?", {
		onOk: function() {Dialog.closeInfo(); applyaccess_apply(p_this, p_table_name);},
		className: "iris_win", width: 300, buttonClass: "button", okLabel: "Да", cancelLabel: "Нет"
	});
}

function applyaccess_apply(p_this, p_table_name) {
	var form = $(get_window_id(p_this)).getElementsByTagName("form")[0];
	Dialog.info('Доступ применяется...', {width:250, height:60, className: "iris_win", showProgress: true});
	new Ajax.Request(g_path + '/config/common/Lib/applyaccess.php', {
		parameters: {'_func': 'ApplyAccess', 'table_name': p_table_name, 'rec_id': form._id.value}, 
		onComplete: function(transport) {
			Dialog.closeInfo();
			try {
				var result = transport.responseText.evalJSON();
				wnd_alert(result.message, 350);
			} catch (e) {
				wnd_alert('Не удалось применить доступ', 350);
			}
		}
	});	
}
*/
