//********************************************************************
// Справочник "Ответы". Карточка.
//********************************************************************

var d_Response_ScriptFileName = '/config/dictionary/response.php';
//var d_Response_ScriptFileName = '/config/sections/interview/dc_response.php';

//Инициализация карточки
function d_response_init(p_wnd_id) 
{
  //Форма карточки
  var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

  c_Common_SetOnChange(card_form.QuestionID, card_form, 'QuestionID', 
    d_Response_ScriptFileName, true,
    function() { 
      d_response_showhide(card_form); 
    } 
  );

  d_response_showhide(card_form);
}


//После выбора вопроса
function d_response_showhide(p_form)
{
  //Получить код типа ответа на вопрос, скрыть ненужные типы ответов и показать нужные
  new Ajax.Request(g_path+d_Response_ScriptFileName, {
    parameters: {
      '_func': 'GetQuestionInfo', 
      //по PollQuestionID надёжнее
      '_p_questionid': p_form.QuestionID.getAttribute('lookup_value')
    },
    onSuccess: function(transport) {
      var result = transport.responseText.evalJSON();
      var code = result.Params.ResponseTypeCode;

      $(p_form.stringvalue.up('.form_row')).style.display = 'none';
      $(p_form.intvalue.up('.form_row')).style.display = 'none';
      $(p_form.floatvalue.up('.form_row')).style.display = 'none';
      $(p_form.datevalue.up('.form_row')).style.display = 'none';
      $(p_form.datetimevalue.up('.form_row')).style.display = 'none';

      if (('String' == code) || ('Single' == code) || ('Multi' == code)) {
        $(p_form.stringvalue.up('.form_row')).style.display = 'table-row';
      }
      if ('Int' == code) {
        $(p_form.intvalue.up('.form_row')).style.display = 'table-row';
      }
      if ('Float' == code) {
        $(p_form.floatvalue.up('.form_row')).style.display = 'table-row';
      }
      if ('Date' == code) {
        $(p_form.datevalue.up('.form_row')).style.display = 'table-row';
      }
      if ('Datetime' == code) {
        $(p_form.datetimevalue.up('.form_row')).style.display = 'table-row';
      }
    }
  });
}

