/**
 * Скрипт карточки компании
 */

irisControllers.classes.c_Account = IrisCardController.extend({

  events: {
      'field:edited #CountryID': 'onChangeCountryID',
      'field:edited #RegionID': 'onChangeRegionID',
      'field:edited #CityID': 'onChangeCityID',
      'click #EmailLink': 'onClickEmailLink'
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

  onClickEmailLink: function () {
    // Email to primary contact
    var contactid = this.fieldValue('PrimaryContactID');
    if (contactid != null) {
      var table = 'iris_Contact';
      var recordname = this.fieldDisplayValue('PrimaryContactID');
      var email = '';
      var params = table + '#;' + contactid + '#;' + 
          recordname + '#;' + email + '#;';

      openCard('grid', 'Email', '', params);
    }
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
  }

});