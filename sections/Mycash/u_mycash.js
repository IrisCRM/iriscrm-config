/**********************************************************************
Скрипт раздела "Мой кошелек (для клиентов)"
**********************************************************************/
var c_Mycash_ScriptFileName = '/config/sections/Mycash/u_mycash.php';

function draw_mycash() {
	new Ajax.Request(g_path+c_Mycash_ScriptFileName, {
		parameters: {
			'_func': 'draw_mycash'
		}, 
		onSuccess: function(transport) {
			g_Prepare_Custom_Section(transport.responseText);
		}
	});	
}

function mycash_refresh(p_this) {
	$(p_this).addClassName('mycash_refresh_act');
	new Ajax.Request(g_path+c_Mycash_ScriptFileName, {
		parameters: {
			'_func': 'RefreshValue'
		}, 
		onSuccess: function(transport) {
			$('mycash_balancevalue').update(transport.responseText);//(float)transport.responseText;
			$(p_this).removeClassName('mycash_refresh_act');
			if (transport.responseText > 0) {
				$('mycash_balancevalue').removeClassName('mycash_balancezero');
			}
			else {
				$('mycash_balancevalue').addClassName('mycash_balancezero');
			}
		}
	});
}
