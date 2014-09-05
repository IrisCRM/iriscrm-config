/**
 * Раздел "Компании". Таблица.
 */
irisControllers.classes.g_Account = IrisGridController.extend({

  script: '/config/sections/Account/r_accounthistory.php',

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
      }
    ], 'iris_Account');
  },

  // Подготовка для печати отчета
  onPrintHistory: function (p_report_name) {
    if (this.getSelectedId() != null) {
      open(g_path + this.script + "?_p_id=" + this.getSelectedId(), 
          "r_AccountHistory_window", 
          "width=800,status=no,toolbar=no,menubar=yes,scrollbars=yes");
    }
  }

});
