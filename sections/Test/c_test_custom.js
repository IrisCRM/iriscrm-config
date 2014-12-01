/**
 * Скрипт карточки компании
 */
irisControllers.classes.c_Test_custom = IrisCardController.extend({
  events: {
    'click #btn_ok': 'onOk'
  },

  onOk: function() {
  	this.notify('onOk');
  },

  onOpen: function() {
    console.log('onOpen');
    this.fieldProperty('btn_ok', 'onclick', '');
  }

});