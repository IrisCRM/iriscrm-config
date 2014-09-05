/**
 * Скрипт карточки дела
 */

irisControllers.classes.c_Task_custom = irisControllers.classes.c_Task.extend({

  onChangeContactID_custom: function(event) {
    this.onChangeEvent(event, {
      disableEvents: true,
      rewriteValues: true,
      letClearValues: true
    });
  },

  onOpen: function () {
    // Родительский обработчик
    irisControllers.classes.c_Task_custom.__super__.onOpen.call(this);
    delete this.events['field:edited #ContactID, #ObjectID, #ProjectID'];
    this.events['field:edited #ObjectID, #ProjectID'] = 'onChangeLookup';
    this.events['field:edited #ContactID'] = 'onChangeContactID_custom';
    this.events['field:edited #clientname, #Phone, #phone2, #clientemail'] = 
        'updateClientListGrid';
    this.delegateEvents(this.events);
  },

  updateClientListGrid: function(event) {
    var self = this;

    this.customGrid({
      method: 'getGridWithContactList',
      destination: 'none',
      hideFooter: true,
      parameters: {
        name: this.fieldValue('clientname'),
        phone1: this.fieldValue('Phone'),
        phone2: this.fieldValue('phone2'),
        email: this.fieldValue('clientemail')
      },
      onGet: function(res) {
        var list = self.$el.find('.clientlist');
        if (!list.length) {
          self.getField('clients').parents('.form_row').addClass('clientlist');
          list = self.$el.find('.clientlist');
          console.log(res.GridId);
          jQuery('#' + res.GridId).addClass('no-resize');
        }
        list.html('<td colspan="4">' + res.Card + '</td>');
      }
    });
  }

});
