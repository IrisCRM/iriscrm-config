//********************************************************************
// Раздел "Мои счета"
//********************************************************************

var g_Myinvoice_ScriptFileName = '/config/sections/Myinvoice/g_myinvoice.php';


//Инициализация таблицы
function myinvoice_grid_init(p_grid_id) 
{
	g_InsertUserButtons(p_grid_id, [
		{
			name: T.t('Оплатить счет'), 
			onclick: "myinvoice_askpay('" + p_grid_id + "', 0);"
		}
	], 'iris_Invoice');	

	printform_createButton(p_grid_id, T.t('Печать')+'&hellip;');
}


//Подтверждение оплаты
function myinvoice_askpay(p_grid_id) {
	var table = $(p_grid_id);
	var rec_id = $($(table).rows[table.getAttribute('selectedrow')]).getAttribute('rec_id');

	new Ajax.Request(g_path+g_Myinvoice_ScriptFileName, {
		parameters: {
			'_func': 'CheckBalanceNeened', 
			'invoice_id': rec_id
		}, 
		onSuccess: function(transport) {
			var result = transport.responseText.evalJSON();
			if ((result.errm != '') && (result.errm != undefined)) {
				Dialog.alert(result.errm, {
					className: "iris_win", 
					buttonClass: "button", 
					width: 350, 
					height: null, 
					okLabel: "ОК", 
					ok: function(win) { 
						return true; 
					}
				});
				return;
			}
			if (1 == result.isok) {
				//Средств баланса достаточно, чтобы оплатить счет => оплата счета
				Dialog.confirm("Внимание! Нажимая кнопку <b>«Оплатить»</b>, Вы соглашаетесь с тем, что Ваш баланс уменьшится на <b>"+result.amount+"</b>. Вернуть эту сумму будет нельзя. Если Вы НЕ хотите, чтобы с Вашего баланса списывались деньги, нажмите «Отмена».", {
					onOk: function() {
						Dialog.closeInfo(); 
						myinvoice_pay(result.invoiceid);
					}, 
					className: "iris_win", 
					width: 400, 
					height: null, 
					buttonClass: "button", 
					okLabel: "Оплатить", 
					cancelLabel: "Отмена"
				});
			}
			else {
				//Средств недостаточно => нужно пополнить баласн
				Dialog.confirm("Средств на Вашем кошельке недостаточно для оплаты счета. <br>Доступный баланс: <b>"+result.balance+"</b>.<br> Сумма оплаты: <b>"+result.amount+"</b>.", {
					onOk: function() {
						Dialog.closeInfo(); 
						increaseBalance(); 
					}, 
					className: "iris_win", 
					width: 400, 
					height: null, 
					buttonClass: "button", 
					okLabel: "Пополнить баланс", 
					cancelLabel: "Отмена"
				});
			}
		}
	});
	//openCard('grid', 'Invoice', '', p_grid_id+'_project_'+rec_id);	
}


//Идет оплата счета...
function myinvoice_pay(p_invoice_id) {
	Dialog.info("идет оплата счета...", {
		className: "iris_win", 
		width: 250, 
		height: 100, 
		showProgress: true
	});
	new Ajax.Request(g_path+g_Myinvoice_ScriptFileName, {
		parameters: {
			'_func': 'PayInvoice', 
			'invoice_id': p_invoice_id
		}, 
		onComplete: function(transport) {
			Dialog.closeInfo();
			var result = transport.responseText.evalJSON();
			Dialog.alert(result.message, {
				width: 350, 
				height: null, 
				okLabel: "ОК", 
				className: "iris_win", 
				buttonClass: "button", 
				ok: function(win) {
					return true;
				}
			});
			refresh_grid('grid');
			refresh_grid('detail');
		}
	});
}
