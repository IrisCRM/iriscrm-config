//********************************************************************
// Скрипт карточки импорта
//********************************************************************

var c_Import_ScriptFileName = '/config/sections/Import/c_import.php';

//Инициализация карточки
function c_Import_Init(p_wnd_id) 
{
	//Форма карточки
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

	//Если создается новая запись
	if (card_form._mode.value == 'insert') {
    	//Сделаем поля недоступными для редактирования на время выполнения запроса 
		card_form.OwnerID_btn.setAttribute('disabled', 'disabled');
        card_form.OwnerID.setAttribute('disabled', 'true');
		card_form.ImportTypeID.setAttribute('disabled', 'disabled'); 

		//Заполнение полей значениями по умолчанию
		c_Common_SetDefaultValues(card_form, c_Import_ScriptFileName, function() { c_Import_GetDefaultValues_end(card_form); } );
	}
	else {
	}
	
}


//После вставки значений по умолчанию
function c_Import_GetDefaultValues_end(p_form)
{
	//Сделаем поля снова доступными для редактирования 
	p_form.OwnerID_btn.removeAttribute('disabled');
	p_form.OwnerID.removeAttribute('disabled');
	p_form.ImportTypeID.removeAttribute('disabled');
	
	//Обновим хеш карточки, чтобы при отмене не задавался лишний вопрос
	p_form._hash.value = GetCardMD5(get_window_id(p_form));
}

