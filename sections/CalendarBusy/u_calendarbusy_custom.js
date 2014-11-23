/**
 * Скрипт раздела Календарь
 */

irisControllers.classes.u_CalendarBusy_custom = irisControllers.classes.u_CalendarBusy.extend({

  onOpen: function() {
    var CALENDAR_ID = 'calendar';
    var self = this;
    g_Prepare_Custom_Section('<div id="' + CALENDAR_ID + '"></div>');

    this.customFilters({
      section: 'Calendar',
      'class': 'u_Calendar',
      method: 'getBusyFiltersHTML',
      containerId: 'filters_area',
      onDraw: function(filters) {
        self.Calendar.setFilters(filters);
        self.Calendar.controller = self;
        self.Calendar.init(CALENDAR_ID);
      },
      onChange: function(filters) {
        // Если установлен конкретный фильтр, то перерисуем весь календарь
        if (this.getConcreteFilterValue(filters)) {
          self.Calendar.destroy();
          this.onDraw(filters);
        }
        else {
          self.Calendar.setFilters(filters);
          self.Calendar.refresh();
        }
      },
      getConcreteFilterValue: function(filters) {
        var value = null;
        _.each(filters, function(elem) {
          // Если установлен фильтр номер 6
          if (elem[0] == 6 && elem[1]) {
            value = elem[1];
            return;
          }
        });
        return value;
      }
    });
  }

});
