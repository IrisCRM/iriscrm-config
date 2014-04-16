/**********************************************************************
Раздел "Конфигуратор". Карточка.
**********************************************************************/

var c_config = {

  //Инициализация карточки
  on_init: function (p_wnd_id) {
    //Форма
    var form = $(p_wnd_id).down('form');

    var thisobj = this;

    //Обработчики
    form.down(lc('#type')).observe('radiobutton:changed', function() {
      thisobj.on_type_change(form);
    });

    form.down(lc('#sectiontype')).observe('radiobutton:changed', function() {
      thisobj.on_sectiontype_change(form);
    });

    //Вызовем обработчики при инициализации карточки
    this.on_type_change(form);
  },

  //Изменили тип конфигурации
  on_type_change: function (p_form) {
    var type = c_Common_GetElementValue(p_form.down(lc('#type')));
    if (type == 1) {
      IrisCard.showField(p_form, 'sectiontype');
      IrisCard.showField(p_form, 'Name');
      IrisCard.showField(p_form, 'showaccessdetail');
      IrisCard.showTab(p_form, 1);
      IrisCard.showTab(p_form, 2);
      this.on_sectiontype_change(p_form);
    }
    else {
      IrisCard.hideField(p_form, 'sectiontype');
      IrisCard.hideField(p_form, 'Name');
      IrisCard.hideField(p_form, 'showaccessdetail');
      IrisCard.showTab(p_form, 1);
      IrisCard.hideTab(p_form, 2);
    }
  },

  //Изменили тип раздела
  on_sectiontype_change: function (p_form) {
    var sectiontype = c_Common_GetElementValue(p_form.down(lc('#sectiontype')));
    if (sectiontype == 1) {
      IrisCard.showTab(p_form, 1);
      IrisCard.hideTab(p_form, 2);
    }
    else
    if (sectiontype == 2) {
      IrisCard.hideTab(p_form, 1);
      IrisCard.showTab(p_form, 2);
    }
    else {
      IrisCard.hideTab(p_form, 1);
      IrisCard.hideTab(p_form, 2);
    }
  }
}