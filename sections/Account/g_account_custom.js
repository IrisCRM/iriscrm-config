/**
 * Раздел "Компании". Таблица.
 */
irisControllers.classes.g_Account_custom = irisControllers.classes.g_Account.extend({

  // Инициализация таблицы
  onOpen: function () {
    // Кнопка печать...
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Печать') + '&hellip;', 
        buttons: [
          {
            name: T.t('История'), 
            onclick: this.instanceName() + ".onPrintHistory();"
          }
        ]
      },
      {
        name: T.t('Произвольная карточка'), 
        onclick: this.instanceName() + ".onCustomCard();"
      }
    ], 'iris_Account');
  },

  onCustomCard: function() {
    this.customCard({
      'class': 'c_Account',
      method: 'renderMonthCard',
      properties: {
        width: 700
      },
      parameters: {
        date1: '20140101',
        date2: '20141201',
      },
      id: 201411,
      onGet: function(param) {
        console.log(param);
      },
      onClose: function(param) {
        console.log(param);
      }
    });
  }

});
