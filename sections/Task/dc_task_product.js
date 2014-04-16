/**
 * Скрипт карточки платежа
 */

irisControllers.classes.dc_Task_Product = IrisCardController.extend({

  script: '/config/common/Sections/dc_product.php',

  events: {
    'keyup #Count, #Price': 'onChangeAmounts',
    'lookup:changed #ProductID': 'onChangeProductID'
  },

  onChangeAmounts: function() {
    var p_form = $(this.el.id).down('form');
    var count = parseFloat(p_form.Count.value);
    var price = parseFloat(p_form.Price.value);

    if ((count.toString() == "NaN") || (price.toString() == 'NaN')) {
        p_form.Amount.value = '';
    }
    else {
        p_form.Amount.value = (count * price).toFixed(2);
    }
  },

  onChangeProductID: function() {
    var self = this;
    c_Common_LinkedField_OnChange($(this.el.id).down('form'), 'ProductID', 
      this.script, true, function() { self.onChangeAmounts(); } );
  }

});
