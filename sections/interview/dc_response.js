//********************************************************************
// Раздел "Интервью". Вкладка "Ответы". Карточка.
//********************************************************************

var dc_Interview_Response_ScriptFileName = '/config/sections/interview/dc_response.php';

//Инициализация карточки
function dc_interview_response_init(p_wnd_id) 
{
  //Форма карточки
  var card_form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];

  if (IsEmptyValue(card_form.PollQuestionID.getAttribute('lookup_value'))) {
    wnd_alert('Опрос завершён');
  }

  //Вычисление оценки в зависимости от ответа-справочника (одновариантного)
  c_Common_SetOnChange(card_form.ResponseID, card_form, 'ResponseID', 
    dc_Interview_Response_ScriptFileName, true);

  //TODO: Заодно брать и список диапазонов значений с оценками в зависимости от типа ответа
  c_Common_SetOnChange(card_form.PollQuestionID, card_form, 'PollQuestionID', 
    dc_Interview_Response_ScriptFileName, true,
    function() { 
      dc_interview_response_showhide(card_form); 
    } 
  );

  //Подсчитать оценку в зависимости от ответа
  $(card_form.intvalue).observe('keyup', function() { 
    dc_interview_response_calcmark(card_form, card_form.intvalue.value, 'int'); 
  } );
  $(card_form.floatvalue).observe('keyup', function() { 
    dc_interview_response_calcmark(card_form, card_form.floatvalue.value, 'float'); 
  } );
  $(card_form.datevalue).observe('blur', function() { 
    dc_interview_response_calcmark(card_form, card_form.datevalue.value, 'date'); 
  } );
  $(card_form.datetimevalue).observe('blur', function() { 
    dc_interview_response_calcmark(card_form, card_form.datetimevalue.value, 'datetime'); 
  } );
  $(card_form.stringvalue).observe('keyup', function() { 
    dc_interview_response_calcmark(card_form, card_form.stringvalue.value, 'string'); 
  } );

  var interviewid = card_form.InterviewID.getAttribute('lookup_value');
  new Ajax.Request(g_path+dc_Interview_Response_ScriptFileName, {
    parameters: {
      '_func': 'GetInterviewParams', 
      '_p_id': interviewid
    },
    onSuccess: function(transport) {
      var result = transport.responseText.evalJSON();
      var pollid = result.Params.PollID;
      //card_form.QuestionID.setAttribute('filter_column', 'PollID');
      //card_form.QuestionID.setAttribute('filter_value', pollid);

      //card_form.PollQuestionID.setAttribute('filter_column', 'PollID');
      //card_form.PollQuestionID.setAttribute('filter_value', pollid);
      card_form.PollQuestionID.setAttribute('filter_where', 
        " T0.id not in (select pollquestionid from iris_interview_response where interviewid = '"+interviewid+"')"+
        " and T0.pollid = '"+pollid+"'");
    }
  });

  bind_lookup_element(card_form.QuestionID, card_form.ResponseID, 'QuestionID');

	if (card_form._mode.value != 'insert') {
    dc_interview_response_showhide(card_form);
  }

  //Скрыть QuestionID
	var row = card_form.QuestionID.up('.form_row');
  jQuery(row).find('.form_table').hide();
  //this.getField('QuestionID').parents('.form_row').find('.form_table').hide();
}


//После выбора вопроса
function dc_interview_response_showhide(p_form)
{
  //Получить код типа ответа на вопрос, скрыть ненужные типы ответов и показать нужные
  new Ajax.Request(g_path+dc_Interview_Response_ScriptFileName, {
    parameters: {
      '_func': 'GetQuestionInfo', 
      //по PollQuestionID надёжнее
      '_p_pollquestionid': p_form.PollQuestionID.getAttribute('lookup_value'),
      '_p_interviewid': p_form.InterviewID.getAttribute('lookup_value')
    },
    onSuccess: function(transport) {
      var result = transport.responseText.evalJSON();
      var code = result.Params.ResponseTypeCode;
      var selected_yes = '';
      var class_yes = '';
      var selected_no = '';
      var class_no = '';
      var sel_yes = 'no';
      var sel_no = 'no';

      $(p_form.ResponseID.up('.form_row')).style.display = 'none';
      $(p_form.stringvalue.up('.form_row')).style.display = 'none';
      $(p_form.intvalue.up('.form_row')).style.display = 'none';
      $(p_form.floatvalue.up('.form_row')).style.display = 'none';
      $(p_form.datevalue.up('.form_row')).style.display = 'none';
      $(p_form.datetimevalue.up('.form_row')).style.display = 'none';

      if ('String' == code) {
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
      if ('Single' == code) {
        $(p_form.ResponseID.up('.form_row')).style.display = 'table-row';
      }
      
      if ('Multi' == code) {
        var row = $($(p_form.datetimevalue).up('.form_row'));
        
        //Нарисовать дополнительные ответы, чтобы их сохранить программно
        var MultiFields = '';
        result.FieldValues.forEach(function(val) {
          //Код доменного поля "Да-Нет"
          selected_yes = '';
          class_yes = '';
          selected_no = '';
          class_no = '';
          sel_yes = 'no';
          sel_no = 'no';
          if (1 == val.ResponseValue) {
            selected_yes = 'selected=""';
            class_yes = 'rb-selected-f';
            sel_yes = 'yes';
          }
          if (0 == val.ResponseValue) {
            selected_no = 'selected=""';
            class_no = 'rb-selected-l';
            sel_no = 'yes';
          }
          MultiFields +=
            '<tr class="form_row">'+
            '<td class="form_table" align="left" width="1%">'+
              '<nobr><span class="card_elem_caption">'+val.Name+'<br></span></nobr>'+
            '</td>'+
            '<td class="form_table" colspan="1" width="75%">'+
              '<div class="radiobtn-cont">'+
                '<div class="radiobtn-values" have_null="no" onclick="selectRadioButton(event)">'+
                '<table class="rb-table"><tbody><tr>'+
                '<td>'+
                  '<span class="rbelem-f '+class_yes+'" pos="f" value="1" selected="'+sel_yes+'">'+
                  '<span class="rb-caption">Да</span></span>'+
                '</td>'+
                '<td>'+
                  '<span class="rbelem-l '+class_no+'" pos="l" value="0" selected="'+sel_no+'">'+
                  '<span class="rb-caption">Нет</span></span>'+
                '</td>'+
                '</tr></tbody></table>'+
                '</div>'+
              '<select mandatory="yes" class="edtText" is_radio="yes" '+
                'style="width:100%; display: none" id="_multi_'+val.Value+'" '+
                'responseid="'+val.Value+'" '+
                'interviewresponseid="'+val.Caption+'" '+
                'onfocus="this.className = \'edtText_selected\';" '+
                'onblur="this.className = \'edtText\';" elem_type="select">'+
                  '<option value="1" '+selected_yes+'>Да</option>'+
                  '<option value="0" '+selected_no+'>Нет</option>'+
              '</select>'+
              '</div>'+
//              '<input type="hidden" id="#_multi_'+val.Value+'" value="int">'+
            '</td>'+
            '</tr>';
        });
        //Оформим так, чтобы остальные поля оставались выровненными
        MultiFields = 
          '<tr id="multifields"><td colspan=4><table id="_multival_"><tbody>' +
          MultiFields +
          '</tbody></table></td></tr>';
        row.insert({after: MultiFields});

        p_form.mark.setAttribute('disabled', 'disabled');
        //$(p_form.mark.up('.form_row')).style.display = 'none';
        //$(p_form.mark.up('.form_row')).next().style.display = 'none';
/*
        //Если мультивариантный ответ, то будем расчитывать оценку онлайн
        var multi_table = $(p_form.down('#_multival_'));
        var select;
        if (multi_table != null) {
          for (var i = 0; i < multi_table.rows.length; i++) {
            select = $(multi_table.rows[i]).down('select');
            alert(select.value); 
            $(select).observe('change', function() { 
              alert(select.value); 
            } );    
          }
        }
*/
      }
      else {
        //Удалим мультивариантыне ответы, если они были нарисованы
        var mf = $(p_form).down('[id="multifields"]');
        if (mf) {
          mf.remove();
        }
      }
      
      //Если ответы не мульти, то сохраним диапазоны в параметре формы "_response_values"
      if (!p_form._response_values) {
        $(p_form).down('input').insert({ after: '<input type="hidden" id="_response_values" value="">' });
      }
      p_form._response_values.value = Object.toJSON(result.ResponseValues);
    }
  });
}


//После сохранения карточки, но ещё до её закрытия
function dc_interview_response_onAfterSave(p_rec_id, p_mode) 
{
  var wnd_id = arguments[3];
	var form = $(wnd_id).getElementsByTagName("form")[0];

  var values = dc_interview_response_getResponses(wnd_id);
  if (values == null) {
    return;
  }

  // передача их на сервер
  new Ajax.Request(g_path+dc_Interview_Response_ScriptFileName, {
    parameters: {
      '_func': 'UpdateMultiResponse',
      '_p_id': p_rec_id,
      '_p_pollquestionid': form.PollQuestionID.getAttribute('lookup_value'),
      '_p_interviewid': form.InterviewID.getAttribute('lookup_value'),
      '_p_values': Object.toJSON(values)
    },
    onComplete: function(transport) {
      // анализ результата
      try {
        var result = transport.responseText.evalJSON();
        if (result.success != 1) {
          wnd_alert(result.message);
        }
      } catch (e) {
        wnd_alert('Не удалось сохранить информацию об ответах');
      }
    }
  });
}


//По id окна карточки возвращает массив информации о заполненных ответах
function dc_interview_response_getResponses(p_wnd_id) {
	var form = $($(p_wnd_id).getElementsByTagName("form")[0]);
  var multi_table = $(form.down('#_multival_'));
  var values = new Array();
  var select;

  //Если вопрос не предполагает мультивариантный ответ
  if (multi_table == null) {
    return null;
  }

  //Если мультивариантный
  for (var i = 0; i < multi_table.rows.length; i++) {
    select = $(multi_table.rows[i]).down('select');
    var val = new Object();
    val.responseid = select.getAttribute('responseid');
    val.interviewresponseid = select.getAttribute('interviewresponseid');
    if (IsEmptyGUIDValue(select.getAttribute('interviewresponseid'))) {
      val.interviewresponseid = null;
    }
    val.responsevalue = select.value;
    values.push(val);
  }
  
  return values;
}


//Подсчитать оценку в зависимости от ответа
function dc_interview_response_calcmark(p_form, p_value, p_type)
{
  var values = p_form._response_values.value.evalJSON();
  for (var i=0; i<values.length; i++) {
    if ((((p_type == 'string') || (p_type == 'guid')) && values[i].Value == p_value)
    || (p_type == 'int' && parseInt(p_value) <= parseInt(values[i].Value))
    || (p_type == 'float' && parseFloat(p_value) <= parseFloat(values[i].Value))
    || (((p_type == 'date') || (p_type == 'datetime')) && tmp_StrToDate(p_value) <= tmp_StrToDate(values[i].Value))
    ) {
      p_form.mark.value = values[i].ResponseValue;
      return;
    }
  }
  p_form.mark.value = 0;
}

// Перевод строки Дд.Мм.ВвГг в формат даты
function tmp_StrToDate(Dat) {
  var year = parseInt(Dat.split(".")[2]);
  var month = parseInt(Dat.split(".")[1])-1;
  var day = parseInt(Dat.split(".")[0]);
  return new Date(year, month, day);
}