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
            onclick: "irisControllers.objects.g_Account" + this.el.id +
              ".onPrint('" + this.el.id + "', 'r_AccountHistory');"
          }
        ]
      }
    ], 'iris_Account');
  },

  // Подготовка для печати отчета
  onPrint: function (p_grid_id, p_report_name) {
    var l_ResArray = new Array();
    l_ResArray = GetGridInfo(p_grid_id, 'update');
    var rec_id = l_ResArray['selected_rec_id'];

    if (p_report_name == 'r_AccountHistory') {
      var l_wnd_size = "width=800";
      var NewWin = open(g_path + this.script + "?_p_id=" + rec_id, 
          p_report_name + "_window", 
          l_wnd_size + ",status=no,toolbar=no,menubar=yes,scrollbars=yes");
    }
  }

});
