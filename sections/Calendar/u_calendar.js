var Calendar = new function() {
  var self = this;
  var SCRIPT_URL = 'config/sections/Calendar/u_calendar.php';
  var calendar = null;

  this.init = function() {
    g_Prepare_Custom_Section('<div id="calendar"></div>');
    initCalendar();
    initResizeEvent();
  };

  var getCalendarLanguage = function() {
    // TODO: более корректно проверять язык
    return (T.language || {}).name == 'English' ? 'en' : 'ru';
  };

  var updateEventWithJSONData = function(event, data) {
    // TODO: найти корректный способ перевода JSON в Event object
    data.start = jQuery.fullCalendar.moment.utc(data.start);
    data.end = jQuery.fullCalendar.moment.utc(data.end);

    for (var prop in data) {
      if (!data.hasOwnProperty(prop)) {
        continue;
      }
      event[prop] = data[prop];
    }

    if (!data.color) {
      delete event.color;
    }
    if (!data.textColor) {
      delete event.textColor;
    }
  };

  var initCalendar = function() {
    calendar = jQuery('#calendar').fullCalendar({
      lang: getCalendarLanguage(),
      height: calculateCalendarHeight(),
      header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaWeek,agendaDay'
      },
      defaultView: 'agendaWeek',
      slotEventOverlap: false,
      defaultDate: moment().format('YYYY-MM-DD'),

      editable: true,
      selectable: true,
      selectHelper: true,

      events: {
        url: SCRIPT_URL,
        type: 'POST',
        data: {
          _func: 'getEvents'
        },
        error: function() {
          growler.growl('there was an error while fetching events!');
        }
      },

      loading: function(bool) {
        jQuery('#calendar').css('opacity', bool ? 0.5 : 1);
      },

      // move
      eventDrop: function(event, revertFunc) {
        jQuery.ajax({
          url: SCRIPT_URL,
          type: 'POST',
          dataType: "json",
          data: {
            _func: 'moveEvent',
            id: event.id,
            start: event.start.format()
          },
          success: function(data, textStatus) {
            if (!data.isOk) {
              revertFunc();
            }
          },
          error: function() {
            revertFunc();
          }
        });

      },

      // resize
      eventResize: function(event, revertFunc) {
        jQuery.ajax({
          url: SCRIPT_URL,
          type: 'POST',
          dataType: "json",
          data: {
            _func: 'resizeEvent',
            id: event.id,
            end: event.end.format()
          },
          success: function(data, textStatus) {
            if (!data.isOk) {
              revertFunc();
            }
          },
          error: function() {
            revertFunc();
          }
        });
      },

      // edit
      eventClick: function(event, element) {
        openCard({
          source_name: 'Task',
          rec_id: event.id,
          ondestroy: function() {
            jQuery.ajax({
              url: SCRIPT_URL,
              type: 'POST',
              dataType: "json",
              data: {
                _func: 'getEventById',
                id: event.id
              },
              success: function(data, textStatus) {
                if (data.id) {
                  updateEventWithJSONData(event, data);
                  calendar.fullCalendar('updateEvent', event);
                }
              }
            });
          }
        });
      },

      // add new
      select: function(start, end) {
        var title = 'new event';
        var eventData = {
          title: title,
          start: start,
          end: end
        };

        jQuery.ajax({
          url: SCRIPT_URL,
          type: 'POST',
          dataType: "json",
          data: {
            _func: 'generateEventId'
          },
          success: function(data, textStatus) {
            if (!data.id) {
              calendar.fullCalendar('unselect');
              return;
            }

            openCard({
              source_name: 'Task',
              card_params: Object.toJSON({
                mode: 'addFromCalendar',
                id: data.id,
                start: start,
                end: end
              }),
              ondestroy: function() {
                jQuery.ajax({
                  url: SCRIPT_URL,
                  type: 'POST',
                  dataType: "json",
                  data: {
                    _func: 'getEventById',
                    id: data.id
                  },
                  success: function(data, textStatus) {
                    if ((data || {}).id) {
                      calendar.fullCalendar('renderEvent', data, false);
                    }
                  }
                });

              }
            });
          }
        });
      },

    });
  };

  // TODO: use stadart core function
  var calculateCalendarHeight = function() {
    //return jQuery('#filters_area').height();

    var height = jQuery('body').height();
    height -= jQuery('#menu_panel').outerHeight(true);
    height -= jQuery('.header').outerHeight(true);
    if (jQuery('.h_div').length > 0) {
      height -= jQuery('.h_div').outerHeight(true);
    }
    height -= jQuery('#dock').outerHeight(true);
    // TODO: IE
    if (!jQuery.support.boxModel) {
      height -= 4;
    }

    return height;
  };

  var initResizeEvent = function() {
    // TODO: using jQuery method corrupts calendar's events resizing
    //jQuery(window).resize(function() {
    Event.observe(window, "resize", function() {
      calendar.fullCalendar('option', 'height', calculateCalendarHeight());
    });
  };

};
