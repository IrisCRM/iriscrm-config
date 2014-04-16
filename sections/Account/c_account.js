/**
 * Скрипт карточки компании
 */

irisControllers.classes.c_Account = IrisCardController.extend({

  events: {
      'field:edit #CountryID': 'onChangeCountryID',
      'field:edit #RegionID': 'onChangeRegionID',
      'field:edit #CityID': 'onChangeCityID',
      'click #EmailLink': 'onChangeEmailLink'
  },
  onChangeCountryID: function () {
    common_filtercity(this.el, 'c');
  },

  onChangeRegionID: function (event) {
    common_filtercity(this.el.id, 'r');
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, true);
  },

  onChangeCityID: function (event) {
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), event.target.id, 
      null, true);
  },

  onChangeEmailLink: function () {
    this.emailToPrimaryContact(this.el);
  },

  /**
   * Инициализация карточки
   */
  onOpen: function () {
    // Фильтрация для поля основной контакт
    this.getField('PrimaryContactID').attr('filter_where',
        "t0.accountid='" + this.fieldValue('_id') + "'");

    // Нарисуем конвертик полю основной контакт
    this.getField('PrimaryContactID').parent().after(
        '<td style="width: 20px;">' + 
        '<div id="EmailLink" class="email_img"></div></td>');
    
    /*
    // Если запись редактируется
    if (this.getField('_mode').val() != 'insert') {

      // Если администратор, то нарисуем кнопку "Сменить ответственного"
      if (g_session_values['userrolecode'] == 'admin') {
        c_common_drawChownBtn('account', p_wnd_id);
      }

      // Применить доступ
      applyaccess_drawButton(p_wnd_id, 'iris_account');
    }
    */

    common_filtercity(this.el, '');
  },

  emailToPrimaryContact: function (p_wnd_id) {
    var form = $(p_wnd_id).getElementsByTagName("form")[0];
    var contactid = c_Common_GetElementValue(form.PrimaryContactID);
    
    if (contactid != null) {
      var table = 'iris_Contact';
      var recordid = contactid;
      var recordname = form.PrimaryContactID.value;
      var email = '';//form.Email.value;
      var params = table + '#;' + recordid + '#;' + 
          recordname + '#;' + email + '#;';
      
      openCard('grid', 'Email', '', params);
    }
  }
});