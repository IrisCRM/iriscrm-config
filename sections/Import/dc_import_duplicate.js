//********************************************************************
// Скрипт карточки вкладки "Дубликаты при импорте"
//********************************************************************


//Инициализация карточки
function dc_Import_Duplicate_Init(p_wnd_id) 
{
	//Форма карточки
	var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

	bind_lookup_element(card_form.TableID, card_form.ColumnID, 'TableID');
}
