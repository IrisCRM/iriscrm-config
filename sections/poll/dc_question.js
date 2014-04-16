//********************************************************************
// Раздел "Опросы". Вкладка "Вопросы". Карточка.
//********************************************************************

//var dc_Poll_Question_ScriptFileName = '/config/sections/poll/dc_question.php';

//Инициализация карточки
function dc_poll_question_init(p_wnd_id) 
{
  //Форма карточки
  var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

	bind_lookup_element(card_form.PollID, card_form.QuestionID, 'PollID');
}
