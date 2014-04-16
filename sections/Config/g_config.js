//*******************************************************************
// Раздел "Конфигуратор". Таблица.                                         
//*******************************************************************

var g_Config_ScriptFileName = '/config/sections/Config/g_config.php';


// Инициализация грида
function g_config_init(p_grid_id) {
  // Добавление кнопок на панель грида
  g_InsertUserButtons(p_grid_id, [
    {
      name: T.t('Загрузить из файла...'), 
      onclick: "config_load_from_file();"
    },
    {
      name: T.t('Сохранить в файл...'), 
      onclick: "config_save_to_file('" + p_grid_id + "');"
    }
  ], 'iris_Config');
}


//Обработчик нажатия кнопки загрузки конфигурации из файла
function config_load_from_file() 
{
  var wnd_id = "wnd"+(Math.random()+"").slice(3); // id будущего окна. должно быть случайное, без символа _ !!!
  var win = new Window({
    id: wnd_id, 
    className: "iris_win", 
    title: "Добавление описания конфигурации из файла", 
    width: 450, 
    height: 250
  }); 
  $(win).setConstraint(true, {
    left: 5, 
    right: 5, 
    top: 5, 
    bottom: 5
  });

  var data = {
    content: '<p>Укажите название файла, путь указывайте относительно каталога config. ' +
        'Возможные варианты:</p>' +
        '<p><code>sections/<название раздела>/structure.xml<br>' +
        'common/Sections/<название раздела>/detail.xml<br>' +
        'dictionary/<название справочника>.xml<br></code></p>' +
        '<p>Указывайте базовый файл, например, structure.xml. ' +
        'Если в каталоге есть переопределённый файл, например, ' +
        'structure_custom.xml, то автоматически будет загружен он.</p>' +
        '<table class="form_table"><tr class="form_row">' +
        GetElementHTMLCode('string', 'Файл (xml)', 'filenamexml', '') +
        '</tr></table> <br>',
    buttons: [
      {
        name: 'ОК',
        onclick: "config_load_from_file_ok('" + wnd_id + "')"
      }, 
      {
        name: 'Отмена',
        onclick: "Windows.close(get_window_id(this))"
      }
    ]
  };
  var win_html = _.template(jQuery('#dialog').html(), {data: data});


  $(win).getContent().update(win_html);
  
  $(win).setDestroyOnClose();
  $(win).toFront();
  $(win).setZIndex(Windows.maxZIndex + 1); // для исправления глюка IE с просвечиванием списков
  $(win).showCenter(0);
}


//ОК - Загружаем конфигурацию из файла (подтверждение)
function config_load_from_file_ok(p_wnd_id)
{
  var form = document.getElementById(p_wnd_id).getElementsByTagName("form")[0];
  var filename = c_Common_GetElementValue(form.filenamexml);
  if (!IsEmptyValue(filename)) {
    Dialog.confirm('Вы уверены, что хотите загрузить конфигурацию из данного файла?', {
      onOk: function() {
        config_load_from_file_start(filename); 
        Dialog.closeInfo();
      }, 
      className: "iris_win", 
      width: 300, 
      height: null, 
      buttonClass: "button", 
      okLabel: "Да", 
      cancelLabel: "Нет"
    });
    return;
  }

  wnd_alert('Нужно выбрать файл');
}


//Загружаем конфигурацию - запрос на сервер
function config_load_from_file_start(p_filename)
{
  new Ajax.Request(g_path + g_Config_ScriptFileName, {
    parameters: {
      '_func': 'LoadFromFile', 
      '_filename': p_filename
    }, 
    onSuccess: function(transport) {
      var result = transport.responseText;
      var messageHTML = '';
      if (result.isJSON() == true) {
        result = result.evalJSON();
        messageHTML = result.message;
        //TODO: В случае успеха надо обновить данные в таблице раздела
        if (IsEmptyValue(messageHTML)) {
          messageHTML = 'Загрузка выполнена успешно';
        }
      }
      else {
        messageHTML = 'Возникла ошибка при загрузки конфигурации из файла';
      }
      wnd_alert(messageHTML);
    },
    onFailure: function () {
      wnd_alert('Возникла ошибка при обращении к серверу для загрузки конфигурации из файла');
    }
  });
}

//Обработчик нажатия кнопки сохранения конфигурации в файл
function config_save_to_file(p_grid_id) 
{
  // id будущего окна. должно быть случайное, без символа _ !!!
  var wnd_id = "wnd"+(Math.random()+"").slice(3); 
  var win = new Window({
    id: wnd_id, 
    className: "iris_win", 
    title: "Сохранение описания конфигурации в файл", 
    width: 450, 
    height: 100
  }); 
  $(win).setConstraint(true, {
    left: 5, 
    right: 5, 
    top: 5, 
    bottom: 5
  });

  var data = {
    content: '<p>Сохранение в файл...<br></p>',
    buttons: [
      {
        name: 'ОК',
        onclick: "Windows.close(get_window_id(this))"
      }
    ]
  };
  var win_html = _.template(jQuery('#dialog').html(), {data: data});


  $(win).getContent().update(win_html);
  
  $(win).setDestroyOnClose();
  $(win).toFront();
  $(win).setZIndex(Windows.maxZIndex + 1); // для исправления глюка IE с просвечиванием списков
  $(win).showCenter(0);

  
  var config_id = getGridSelectedID(p_grid_id);

  new Ajax.Request(g_path + g_Config_ScriptFileName, {
    parameters: {
      '_func': 'SaveToFile', 
      '_configid': config_id
    }, 
    onSuccess: function(transport) {
      var result = transport.responseText;
      var messageHTML = '';
      if (result.isJSON() == true) {
        result = result.evalJSON();
        messageHTML = result.message;
        if (IsEmptyValue(messageHTML)) {
          messageHTML = 'Сохранение раздела конфигурации в XML файл выполнено успешно';
        }
      }
      else {
        messageHTML = 'Возникла ошибка при сохранении конфигурации в файл';
      }
      Windows.close(wnd_id);
      wnd_alert(messageHTML);
    },
    onFailure: function () {
      Windows.close(wnd_id);
      wnd_alert('Возникла ошибка при обращении к серверу для сохранения конфигурации в файл');
    }
  });

}