/**
 * Скрипт карточки цены продукта
 */

irisControllers.classes.dg_Product_Price = IrisGridController.extend({

  onTest: function(msg) {
    alert(msg);
  },

  onOpen: function () {
    // Кнопка печать...
    g_InsertUserButtons(this.el.id, [
      {
        name: T.t('Список') + '&hellip;', 
        buttons: [
          {
            name: T.t('Пункт 1'), 
            onclick: "irisControllers.objects.dg_Product_Price" + this.el.id +
              ".onTest('Пункт № 1');"
          },
          {
            name: T.t('Пункт 2'), 
            onclick: "irisControllers.objects.dg_Product_Price" + this.el.id +
              ".onTest('Пункт № 2');"
          }
        ]
      },
      {
        name: T.t('Просто кнопка'), 
        onclick: "irisControllers.objects.dg_Product_Price" + this.el.id +
          ".onTest('Кнопка');"
      }
    ], 'iris_Product_Price');
  }

});
