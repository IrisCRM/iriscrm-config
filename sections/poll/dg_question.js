//********************************************************************
// Раздел "Опросы". Вкладка "Вопросы". Таблица.
//********************************************************************

var dg_PollQuestion_ScriptFileName = "/config/sections/poll/dg_question.php";


//********************************************************************
// Таблица. Изменение непосредственно в гриде.
//********************************************************************

//После удаления в гриде
function dg_poll_question_onAfterDelete(p_values)
{
	//Получим id родителя, в котором был удаленный продукт
	var parent_id = null;
	try {
		var values = p_values.evalJSON();
		values.each(function(field) {
			if (("string" == typeof(field.Name)) && ('pollid' == (field.Name).toLowerCase())) {
				parent_id = field.Value;
			}
			if (("string" == typeof(field.Name)) && ('orderpos' == (field.Name).toLowerCase())) {
				orderpos = field.Value;
			}
		});
	}
	//Если не получили массив в параметре, например, то это ошибка
	catch (e) {
		return;
	}
	//Если не было в списке колонок pollid
	if ((null == parent_id) || (null == orderpos)) {
		return;
	}

	//Перенумеруем позиции во вкладке, если необходимо
	new Ajax.Request(g_path + dg_PollQuestion_ScriptFileName, {
		parameters: {
			'_func': 'Renumber', 
			'_p_id': parent_id,
      '_p_orderpos': orderpos
		} 
	});
}
