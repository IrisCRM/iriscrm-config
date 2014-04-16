/**
 * Раздел "Дела". Таблица.
 */
irisControllers.classes.g_Task = IrisGridController.extend({

  scriptCalendar: '/config/sections/Task/r_task_calendar.php',
  
  scriptActual: '/config/sections/Task/r_task_actual.php',

  // Инициализация таблицы
  onOpen: function () {
    //Кнопка печать...
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Печать') + '&hellip;', 
        buttons: [
          {
            name: T.t('Календарь'), 
            onclick: "irisControllers.objects.g_Task" + this.el.id + ".calendarShowFilters();"
          },
          {
            name: T.t('Актуальные дела'), 
            onclick: "irisControllers.objects.g_Task" + this.el.id + ".actualShowFilters();"
          }
        ]
      }
    ], 'iris_Task');
  },

  calendarShowFilters: function() {
    var win_id = "wnd"+(Math.random()+"").slice(3); // id будущего окна. должно быть случайное, без символа _ !!!
    var win = new Window({
      id: win_id, 
      className: "iris_win", 
      title: "Создание отчета", 
      width: 350, 
      height: 110
    }); 
    $(win).setConstraint(true, {
      left: 5, 
      right: 5, 
      top: 5, 
      bottom: 5
    });

    var WIN_HTML  = '<form><table class="form_table" style="width: 100%;"><tbody>';

    //Дата с
    WIN_HTML += '<tr class="form_row">';
    WIN_HTML += GetElementHTMLCode('date', 'Дата с', 'dts', '');
    WIN_HTML += '</tr>';

    //Дата по
    WIN_HTML += '<tr class="form_row">';
    WIN_HTML += GetElementHTMLCode('date', 'Дата по', 'dte', '');
    WIN_HTML += '</tr>';

    //Ответственный
    WIN_HTML += '<tr class="form_row">';
    WIN_HTML += GetElementHTMLCode('lookup', 'Ответственный', 'OwnerID', '', 'Contact', '', 'T0.contacttypeid', '8e45d724-079e-4b67-b1e9-4c195d3625f5');
    WIN_HTML += '</tr>';
    
    //Кнопки
    WIN_HTML += '<tr>';
    WIN_HTML += '<table class="form_table_buttons_panel"><tbody><tr><td style="vertical-align: middle;"/> ';
    WIN_HTML += '<td align="right">';
    WIN_HTML += '<input type="button" ' +
        'onclick="irisControllers.objects.g_Task' + this.el.id + 
        '.calendarDrawReport(\'' + win_id + '\');" ' +
        'value="Создать" style="width: 70px;" class="button" id="btn_ok"/>';
    WIN_HTML += '<input type="button" onclick="Windows.close(get_window_id(this))" value="Закрыть" style="width: 70px;" class="button" id="btn_cancel"/>';
    WIN_HTML += '</td></tr></tbody></table>';
    WIN_HTML += '</tr>';
    WIN_HTML += '</tbody></table></form>';
    
    
    $(win).getContent().update(WIN_HTML);
    
    $(win).setDestroyOnClose(); 
    $(win).toFront();
    $(win).setZIndex(Windows.maxZIndex + 1);// для исправления глюка IE с просвечиванием списков
    $(win).showCenter(0);
    
    //Значения фильтров по умолчанию
    var l_form = document.getElementById(win_id).getElementsByTagName("form")[0];
    c_Common_SetDefaultValues(l_form, this.scriptCalendar);
  },

  calendarDrawReport: function(p_wnd_id) {
    var form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];
    var StartDate = c_Common_GetElementValue($(form).dts);
    var EndDate = c_Common_GetElementValue($(form).dte);
    var OwnerID = c_Common_GetElementValue($(form).OwnerID);

    var l_wnd_size = "width=800";
    var NewWin = open(g_path+this.scriptCalendar+"?_p_startdate="+StartDate+"&_p_enddate="+EndDate+"&_p_ownerid="+OwnerID, 
      "r_Task_Calendar_Filters_window", l_wnd_size+",status=no,toolbar=no,menubar=yes,scrollbars=yes");
  },

  actualShowFilters: function() {
    var win_id = "wnd"+(Math.random()+"").slice(3); // id будущего окна. должно быть случайное, без символа _ !!!
    var win = new Window({
      id: win_id, 
      className: "iris_win", 
      title: "Создание отчета", 
      width: 350, 
      height: 60
    }); 
    $(win).setConstraint(true, {
      left: 5, 
      right: 5, 
      top: 5, 
      bottom: 5
    });

    var WIN_HTML  = '<form><table class="form_table" style="width: 100%;"><tbody>';

    //Ответственный
    WIN_HTML += '<tr class="form_row">';
    WIN_HTML += GetElementHTMLCode('lookup', 'Ответственный', 'OwnerID', '', 'Contact', '', 'T0.contacttypeid', '8e45d724-079e-4b67-b1e9-4c195d3625f5');
    WIN_HTML += '</tr>';
    
    //Кнопки
    WIN_HTML += '<tr>';
    WIN_HTML += '<table class="form_table_buttons_panel"><tbody><tr><td style="vertical-align: middle;"/> ';
    WIN_HTML += '<td align="right">';
    WIN_HTML += '<input type="button" onclick="irisControllers.objects.g_Task' + this.el.id + 
        '.actualDrawReport(\'' + win_id + '\');" value="Создать" style="width: 70px;" class="button" id="btn_ok"/>';
    WIN_HTML += '<input type="button" onclick="Windows.close(get_window_id(this))" value="Закрыть" style="width: 70px;" class="button" id="btn_cancel"/>';
    WIN_HTML += '</td></tr></tbody></table>';
    WIN_HTML += '</tr>';
    WIN_HTML += '</tbody></table></form>';
    
    
    $(win).getContent().update(WIN_HTML);
    
    $(win).setDestroyOnClose(); 
    $(win).toFront();
    $(win).setZIndex(Windows.maxZIndex + 1);// для исправления глюка IE с просвечиванием списков
    $(win).showCenter(0);
    
    //Значения фильтров по умолчанию
    var l_form = document.getElementById(win_id).getElementsByTagName("form")[0];
    c_Common_SetDefaultValues(l_form, this.scriptActual);
  },

  actualDrawReport: function(p_wnd_id) {
    var form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];
    var OwnerID = c_Common_GetElementValue(form.OwnerID);

    var l_wnd_size = "width=800";
    var NewWin = open(g_path+this.scriptActual+"?_p_ownerid="+OwnerID, 
      "r_Task_Actual_Filters_window", l_wnd_size+",status=no,toolbar=no,menubar=yes,scrollbars=yes");
  }

});
