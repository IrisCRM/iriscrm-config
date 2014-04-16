//********************************************************************
// Скрипт карточки раздела "Сообщения"
//********************************************************************

var g_Message_ScriptFileName = '/config/sections/Message/c_message.php';

function message_grid_init(p_grid_id) {
	g_InsertUserButtons(p_grid_id, [
		{
			name: T.t('Ответить'), 
			onclick: "replyMessage('" + p_grid_id + "', 0);"
		}
	], 'iris_Message');
	highlightNewMessages(p_grid_id);
}

function replyMessage(p_grid_id) {
	var row = $(p_grid_id).getAttribute('selectedrow');
	if (0 > row) {
		return;
	}
	var rec_id = $(p_grid_id).rows[row].getAttribute('rec_id');

	// открытие карточки, которой в качестве родителя передаем признак...
	openCard('grid', 'Message', '', '#'+rec_id+'#'+p_grid_id);
}
/*
function printform() {
	new Ajax.Request(g_path+g_Message_ScriptFileName, {
		parameters:  {
			'_func': 'FillPrintForm'
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText;
			alert(result);
		}
	});
}
*/

function highlightNewMessages(p_grid_id) {
	var l_grid = $(p_grid_id);
	var l_ids_arr = new Array();
	//По всем строчкам таблицы
	for (var i=1; i < l_grid.rows.length; i++) {
		l_ids_arr.push(l_grid.rows[i].getAttribute('rec_id'));
	}

	new Ajax.Request(g_path+g_Message_ScriptFileName, {
		parameters: {'_func': 'highlightNewMessages', 'ids': Object.toJSON($A(l_ids_arr))}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			//По всем строчкам таблицы
			for (var i=1; i < l_grid.rows.length; i++) {
				if (result.indexOf(l_grid.rows[i].getAttribute('rec_id')) != -1) {
					$(l_grid.rows[i]).addClassName('grid_newmessage');
				}
			}
		}
	});	
}