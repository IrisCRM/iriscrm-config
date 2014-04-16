/**
 * Скрипт карточки контакта
 */

irisControllers.classes.c_Contact = IrisCardController.extend({

  events: {
    'keyup #Name': 'onChangeName',
    'lookup:changed #CountryID': 'onChangeCountryID',
    'lookup:changed #RegionID': 'onChangeRegionID',
    'lookup:changed #CityID': 'onChangeCityID',
    'lookup:changed #AccountID, #ObjectID': 'onChangeLookup',
    'mouseover #_SkypeIcon': 'onMouseOverSkype',
    'mouseout #_SkypeIcon': 'onMouseOutSkype'
  },

  onChangeName: function () {
    var p_form = $(this.el).down('form');
    // Помещаем в массив слова, разделенные пробелом
    var l_array = p_form.Name.value.split(' ');

    var l_array_mod = new Array();
    var l_first = '';
    
    //n = 0;
    for (i = 0, n = 0; i < l_array.length; i++) {
      if (l_array[i] != '') {
        if (n != 0) {
          l_array_mod[n++ - 1] = l_array[i]; // убираем лишние пробелы
        }
        else {
          l_first = l_array[i];
          n++;
        }
      }
    }

    if (l_array_mod.length == 0) {
      p_form.SpeakName.value = l_first;
      return;
    }

    if (l_array_mod.length > 0) {
      // Если только одно слово, то вывести его
      p_form.SpeakName.value = l_array_mod[0];
    }
    
    // Выводим остальные слова
    for (i = 1; i < l_array_mod.length; i++) { 
      p_form.SpeakName.value += ' ' + l_array_mod[i];
    }
  },

  onChangeCountryID: function () {
    common_filtercity(this.el.id, 'c');
  },

  onChangeRegionID: function (event) {
    common_filtercity(this.el, 'r');
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, true);
  },

  onChangeCityID: function (event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, true);
  },

  onChangeLookup: function (event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, true, undefined, true);
  },

  onMouseOverSkype: function () {
    var skype = this.$el.find('#Skype').val();
    if (skype != '') {
      this.$el.find('#_SkypeIconLink').attr('href', "skype:" + skype);  
    }
  },

  onMouseOutSkype: function () {
    this.$el.find('#_SkypeIconLink').attr('href', "#");
  },


  onOpen: function() {
    // Задизаблим
    this.getField('balance').attr('readonly', true);

    // Нарисуем иконку skype
    this.getField('Skype').parent().after(
        '<td style="width: 21px;"><a id="_SkypeIconLink" href="#">' +
        '<div id="_SkypeIcon" class="skype_img"></div></a></td>');

    // Если редактируется запись
    if (this.parameter('mode') != 'insert') {
      // Кнопка печать...
      printform_createCardHeaderButton(this.el, 'top', T.t('Печать')+'&hellip;');    

      /*
      // Если администратор, то нарисуем кнопку "Сменить ответственного"
      if (g_session_values['userrolecode'] == 'admin') {
        c_common_drawChownBtn('contact', p_wnd_id);
      }

      // Применить доступ
      applyaccess_drawButton(p_wnd_id, 'iris_contact');
      */
    }

    common_filtercity(this.el.id, '');
  }
});
