/**
 * Скрипт карточки дела
 */

irisControllers.classes.c_Task = IrisCardController.extend({

  events: {
    'field:edited #ContactID, #ObjectID, #ProjectID': 'onChangeLookup',
    'field:edited #IssueID, #BugID, #IncidentID': 'onChangeLookup',
    'field:edited #OfferID, #PactID, #InvoiceID': 'onChangeLookup',
    'field:edited #PaymentID, #FactInvoiceID, #DocumentID': 'onChangeLookup',
    'field:edited #AccountID': 'onChangeAccountID',
    'field:edited #TaskStateID': 'onChangeTaskStateID',
    'field:edited #TaskResultID': 'onChangeTaskResultID',
    'field:edited #TaskTargetID': 'onChangeTaskTargetID',
    'field:edited #NextTaskTargetID': 'onChangeNextTaskTargetID',
    'field:edit #StartDate': 'onChangeStartDate',
    'field:edit #RemindDate': 'onChangeRemindDate',
    'field:edit #IsRemind': 'onChangeIsRemind'
  },

  onChangeLookup: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id);
  },

  onChangeAccountID: function () {
    // Поле Контакт зависит от поля Компания
    this.bindFields('AccountID', 'ContactID');
  },

  onChangeTaskStateID: function() {
    var code = this.getField('TaskStateID')
        .find('[value=' + this.fieldValue('TaskStateID') + ']').attr('code');
    this.showIncubator(code == 'Finished' || code == 'Canceled'
        || this.fieldValue('NextTaskTargetID') != 'null');
  },

  onChangeTaskResultID: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id, 
      null, true);
  },

  onChangeTaskTargetID: function() {
    var task_target_id = this.fieldValue('TaskTargetID');
    var next_target = this.getField('NextTaskTargetID');
    next_target.find('option').each(function() {
      var option = jQuery(this);
      if (!option.data('original-caption')) {
        option.data('original-caption', option.html());
      }
      if (option.val() != 'null' && option.val() == task_target_id) {
        option.html('&rarr; ' + option.html());
      }
      else {
        option.html(option.data('original-caption'));
      }
    });
  },

  onChangeNextTaskTargetID: function(event) {
    c_Common_LinkedField_OnChange($(this.el).down('form'), event.target.id, 
      true, true);
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
    this.bindFields('TaskTypeID', 'TaskResultID');
  
    var p_form = $(this.el).down('form');    

    var cardParams = jQuery.parseJSON(p_form._params.value || '{}');
    if (cardParams.mode == 'addFromCalendar') {
      this.addFromCalendar(p_form, cardParams);
    }

    this.fieldProperty('CreateID', 'readonly', true);
    this.onChangeTaskStateID();
    this.onChangeTaskTargetID();

    if (this.parameter('HaveNextTask') > 0) {
      this.fieldProperty('NextTaskTargetID', 'readonly', true);
      this.fieldProperty('NextStartDate', 'readonly', true);
      this.fieldProperty('_select_next_target', 'readonly', true);
    }
  },

  showIncubator: function(show) {
    if (show == undefined) {
      show = true;
    }
    this.showField('NextTaskTargetID', show);
    this.showField('NextStartDate', show);
    this.showField('next_task_splitter', show);
    this.showField('_select_next_target', show);
  },

  selectNextTarget: function() {
    var self = this;

    this.customGrid({
      parameters: {
        taskid: this.parameter('id'),
        projectid: this.fieldValue('ProjectID'),
        targetid: this.fieldValue('TaskTargetID'),
        nexttargetid: this.fieldValue('NextTaskTargetID')
      },
      onSelect: function(record_id) {
        self.fieldValue('NextTaskTargetID', record_id);
      }
    });
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
