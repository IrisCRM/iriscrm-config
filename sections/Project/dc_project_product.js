/**
 * Раздел "Проекты". Вкладка "Продукты". Карточка.
 */
irisControllers.classes.dc_Project_Product = IrisCardController.extend({

  events: {
    'keyup #Price, #Cost, #Discount': 'onChangeEvent',
    'change #Count, #TimeUnit, #Duration, #UnitID': 'onChangeEvent',
    'change #StartDate': 'onChangeEvent',
    'lookup:changed #ProductID': 'onChangeEvent'
  },

  onOpen: function () {
    this.getField('CostAmount').attr('readonly', 'readonly');
    this.getField('PriceAmount').attr('readonly', 'readonly');
    this.getField('Amount').attr('readonly', 'readonly');
  },

});
