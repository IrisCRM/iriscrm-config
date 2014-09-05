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
    this.delegateEvents(this.events);
  }

});
