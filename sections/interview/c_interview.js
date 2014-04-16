//********************************************************************
// Раздел "Интервью". Карточка.
//********************************************************************

var c_Interview_ScriptFileName = '/config/sections/interview/c_interview.php';

//Инициализация карточки
function c_interview_init(p_wnd_id) 
{
  //Форма карточки
  var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

  //Связанные поля
  c_Common_SetOnChange(card_form.AccountID, card_form, 'AccountID', c_Interview_ScriptFileName);
  c_Common_SetOnChange(card_form.ContactID, card_form, 'ContactID', c_Interview_ScriptFileName);

	$(card_form.InterviewResultID).observe('change', function() { 
    c_Common_LinkedField_OnChange(card_form, 'InterviewResultID', c_Interview_ScriptFileName, true); 
  } );
}

