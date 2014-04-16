/**
 * Скрипт карточки дела
 */

irisControllers.classes.c_Task = IrisCardController.extend({

  events: {
    'lookup:changed #ContactID, #ObjectID, #ProjectID': 'onChangeLookup',
    'lookup:changed #IssueID, #BugID, #IncidentID': 'onChangeLookup',
    'lookup:changed #OfferID, #PactID, #InvoiceID': 'onChangeLookup',
    'lookup:changed #PaymentID, #FactInvoiceID, #DocumentID': 'onChangeLookup',
    'lookup:changed #AccountID': 'onChangeAccountID',
    'change #TaskResultID': 'onChangeTaskResultID',
    'change #StartDate': 'onChangeStartDate',
    'change #RemindDate': 'onChangeRemindDate',
    'change #IsRemind': 'onChangeIsRemind'
  },

  onChangeLookup: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id);
  },

  onChangeAccountID: function () {
    // Поле Контакт зависит от поля Компания
    this.bindFields('AccountID', 'ContactID');
  },

  onChangeTaskResultID: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id, 
      null, true);
  },

  onChangeStartDate: function() {
    //Коррекция поля "Завершение" = "Начало" + 2 часа
    var p_form = $(this.el).down('form');
    var l_date = new Date(Date.parseFormattedString(p_form.StartDate.value));
    if (l_date != 'Invalid Date') {
      var l_date_end = new Date(l_date);
      l_date_end.setMinutes(l_date.getMinutes() + 120);
      p_form.FinishDate.value = l_date_end.toFormattedString(true);

      //Скорректируем время напоминания
      c_Common_IsRemind_OnChange(p_form, Array('StartDate'), 15);
    }
  },

  onChangeIsRemind: function() {
    c_Common_IsRemind_OnChange($(this.el).down('form'), 
        Array('StartDate'), 15);
  },

  onChangeRemindDate: function() {
    c_Common_RemindDate_OnChange($(this.el).down('form'));
  },

  onOpen: function () {
    // Поле Контакт зависит от поля Компания
    this.bindFields('AccountID', 'ContactID');
  
    var p_form = $(this.el).down('form');    

    bind_select_element(p_form.TaskTypeID, p_form.TaskResultID, 'TaskTypeID');

    var cardParams = jQuery.parseJSON(p_form._params.value || '{}');
    if (cardParams.mode == 'addFromCalendar') {
      this.addFromCalendar(p_form, cardParams);
    }
  },

  addFromCalendar: function(form, params) {
    var formatDateStr = function(date) {
      return moment.utc(date).format('DD.MM.YYYY HH:mm');
    };

    form._id.value = params.id;
    form.StartDate.value = formatDateStr(params.start);
    form.FinishDate.value = formatDateStr(params.end);
  }

});
