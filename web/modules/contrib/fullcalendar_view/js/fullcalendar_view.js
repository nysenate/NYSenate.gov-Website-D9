/**
 * @file
 * Fullcalendar View plugin JavaScript file.
 */

// Jquery wrapper for drupal to avoid conflicts between libraries.
(function ($) {
  var initialLocaleCode = 'en';
  // Dialog index.
  var dialogIndex = 0;
  // Dialog objects.
  var dialogs = [];
  // Date entry clicked.
  var slotDate;

  /**
   * Event render handler
   */
  function eventRender (info) {
    let viewIndex = parseInt(this.el.getAttribute("data-calendar-view-index"));
    let viewSettings = drupalSettings.fullCalendarView[viewIndex];
    // Event title html markup.
    let eventTitleEle = info.el.getElementsByClassName('fc-title');
    if(eventTitleEle.length > 0) {
      eventTitleEle[0].innerHTML = info.event.title;
    }
    // Event list tile html markup.
    let eventListTitleEle = info.el.getElementsByClassName('fc-list-item-title');
    if(eventListTitleEle.length > 0) {
      if (info.event.url) {
        eventListTitleEle[0].innerHTML = '<a href="' + info.event.url + '">' + info.event.title + '</a>';
      }
      else {
        eventListTitleEle[0].innerHTML = info.event.title;
      }
    }
    // Modal popup
    if (viewSettings.dialogModal) {
      if ($(info.el).is('a')) {
        $(info.el).addClass('use-ajax');
        if (!viewSettings.dialogCanvas) {
          $(info.el).attr('data-dialog-type', 'modal');
        }
        else {
          $(info.el).attr('data-dialog-type', 'dialog');
          $(info.el).attr('data-dialog-renderer', 'off_canvas');
        }
        $(info.el).attr('data-dialog-options', viewSettings.dialog_modal_options);
        $(info.el).attr('href', $(info.el).attr('href').replaceAll('&amp;', '&'));
      }
      else {
        $(info.el).find('a').each(function(){
          if (!viewSettings.dialogCanvas) {
            $(this).attr('data-dialog-type', 'modal');
          }
          else {
            $(this).attr('data-dialog-type', 'dialog');
            $(this).attr('data-dialog-renderer', 'off_canvas');
          }
          $(this).attr('data-dialog-options', viewSettings.dialog_modal_options);
          $(this).addClass('use-ajax');
          $(this).attr('href', $(this).attr('href').replaceAll('&amp;', '&'));
        });
      }
    }
  }
  /**
   * Event resize handler
   */
  function eventResize(info) {
    const end = info.event.end;
    const start = info.event.start;
    let strEnd = '';
    let strStart = '';
    let viewIndex = parseInt(this.el.getAttribute("data-calendar-view-index"));
    let viewSettings = drupalSettings.fullCalendarView[viewIndex];
    const formatSettings = {
        month: '2-digit',
        year: 'numeric',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: 'UTC',
        locale: 'sv-SE'
      };
    // define the end date string in 'YYYY-MM-DD' format.
    if (end) {
      // The end date of an all-day event is exclusive.
      // For example, the end of 2018-09-03
      // will appear to 2018-09-02 in the calendar.
      // So we need one day subtract
      // to ensure the day stored in Drupal
      // is the same as when it appears in
      // the calendar.
      if (end.getHours() == 0 && end.getMinutes() == 0 && end.getSeconds() == 0) {
        end.setDate(end.getDate() - 1);
      }
      // String of the end date.
      strEnd = FullCalendar.formatDate(end, formatSettings);
    }
    // define the start date string in 'YYYY-MM-DD' format.
    if (start) {
      strStart = FullCalendar.formatDate(start, formatSettings);
    }
    const title = info.event.title.replace(/(<([^>]+)>)/ig,"");;
    const msg = Drupal.t('@title end is now @event_end. Do you want to save this change?', {
      '@title': title,
      '@event_end': strEnd
    });

    if (
        viewSettings.updateConfirm === 1 &&
        !confirm(msg)
    ) {
      info.revert();
    }
    else {
      /**
       * Perform ajax call for event update in database.
       */
      jQuery
        .post(
          drupalSettings.path.baseUrl +
            "fullcalendar-view-event-update",
          {
            eid: info.event.extendedProps.eid,
            entity_type: viewSettings.entityType,
            start: strStart,
            end: strEnd,
            start_field: viewSettings.startField,
            end_field: viewSettings.endField,
            token: viewSettings.token
          }
        )
        .done(function(data) {
          if (data !== '1') {
            alert("Error: " + data);
            info.revert();
          }
        });
    }
  }

  // Day entry click call back function.
  function dayClickCallback(info) {
    slotDate = info.dateStr;
  }

  // Event click call back function.
  function eventClick(info) {
    slotDate = null;
    info.jsEvent.preventDefault();
    let thisEvent = info.event;
    let viewIndex = parseInt(this.el.getAttribute("data-calendar-view-index"));
    let viewSettings = drupalSettings.fullCalendarView[viewIndex];
    let des = thisEvent.extendedProps.des;
    // Show the event detail in a pop up dialog.
    if (viewSettings.dialogWindow) {
      let dataDialogOptionsDetails = {};
      if ( des == '') {
        return false;
      }

      const jsFrame = new JSFrame({
        parentElement:info.el,//Set the parent element to which the jsFrame is attached here
      });
      // Position offset.
      let posOffset = dialogIndex * 20;
      // Dialog options.
      let dialogOptions = JSON.parse(viewSettings.dialog_options);
      dialogOptions.left += posOffset + info.jsEvent.pageX;
      dialogOptions.top += posOffset + info.jsEvent.pageY;
      dialogOptions.title = dialogOptions.title ? dialogOptions.title : thisEvent.title.replace(/(<([^>]+)>)/ig,"");
      dialogOptions.html = des;
      //Create window
      dialogs[dialogIndex] = jsFrame.create(dialogOptions);

      dialogs[dialogIndex].show();
      dialogIndex++;

      return false;
    }
    // Open a new window to show the details of the event.
    if (thisEvent.url) {
      let eventURL = new URL(thisEvent.url, location.origin);
      if (eventURL.origin === "null") {
        // Invalid URL.
        return false;
      }
      if (viewSettings.openEntityInNewTab) {
        // Open a new window to show the details of the event.
       window.open(thisEvent.url);
       return false;
      }
      else {
        // Open in same window
        window.location.href = thisEvent.url;
        return false;
      }
    }

    return false;
  }

  // Event drop call back function.
  function eventDrop(info) {
    const end = info.event.end;
    const start = info.event.start;
    let strEnd = '';
    let strStart = '';
    let viewIndex = parseInt(this.el.getAttribute("data-calendar-view-index"));
    let viewSettings = drupalSettings.fullCalendarView[viewIndex];
    const formatSettings = {
        month: '2-digit',
        year: 'numeric',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: 'UTC',
        locale: 'sv-SE'
      };
    // define the end date string in 'YYYY-MM-DD' format.
    if (end) {
      // The end date of an all-day event is exclusive.
      // For example, the end of 2018-09-03
      // will appear to 2018-09-02 in the calendar.
      // So we need one day subtract
      // to ensure the day stored in Drupal
      // is the same as when it appears in
      // the calendar.
      if (end.getHours() == 0 && end.getMinutes() == 0 && end.getSeconds() == 0) {
        end.setDate(end.getDate() - 1);
      }
      // String of the end date.
      strEnd = FullCalendar.formatDate(end, formatSettings);
    }
    // define the start date string in 'YYYY-MM-DD' format.
    if (start) {
      strStart = FullCalendar.formatDate(start, formatSettings);
    }
    const title = info.event.title.replace(/(<([^>]+)>)/ig,"");;
    const msg = Drupal.t('@title start is now @event_start and end is now @event_end - Do you want to save this change?', {
      '@title': title,
      '@event_start': strStart,
      '@event_end': strEnd
    });

    if (
        viewSettings.updateConfirm === 1 &&
        !confirm(msg)
    ) {
      info.revert();
    }
    else {
      /**
       * Perform ajax call for event update in database.
       */
      jQuery
        .post(
          drupalSettings.path.baseUrl +
            "fullcalendar-view-event-update",
          {
            eid: info.event.extendedProps.eid,
            entity_type: viewSettings.entityType,
            start: strStart,
            end: strEnd,
            start_field: viewSettings.startField,
            end_field: viewSettings.endField,
            token: viewSettings.token
          }
        )
        .done(function(data) {
          if (data !== '1') {
            alert("Error: " + data);
            info.revert();
          }
        });

    }
  }

  function datesRender (info) {
    Drupal.attachBehaviors(info.el);
  }
  function datesDestroy (info) {
    Drupal.detachBehaviors(info.el);
  }

  // Build the calendar objects.
  function buildCalendars() {
    $('.js-drupal-fullcalendar')
    .each(function() {
      let calendarEl = this;
      let viewIndex = parseInt(calendarEl.getAttribute("data-calendar-view-index"));
      let viewSettings = drupalSettings.fullCalendarView[viewIndex];
      var calendarOptions = JSON.parse(viewSettings.calendar_options);
      // Switch default view at mobile widths.
      if (calendarOptions.mobileWidth !== undefined && calendarOptions.defaultMobileView !== undefined && $(window).width() <= calendarOptions.mobileWidth) {
       calendarOptions.defaultView = calendarOptions.defaultMobileView;
      }
      // Bind the render event handler.
      calendarOptions.eventRender = eventRender;
      // Bind the resize event handler.
      calendarOptions.eventResize = eventResize;
      // Bind the day click handler.
      calendarOptions.dateClick = dayClickCallback;
      // Bind the event click handler.
      calendarOptions.eventClick = eventClick;
      // Bind the drop event handler.
      calendarOptions.eventDrop = eventDrop;
      // Trigger Drupal behaviors when calendar events are updated.
      calendarOptions.datesRender = datesRender;
      // Trigger Drupal behaviors when calendar events are destroyed.
      calendarOptions.datesDestroy = datesDestroy;
      // Language select element.
      var localeSelectorEl = document.getElementById('locale-selector-' + viewIndex);
      // Allow passing a default date via query string.
      const params = (new URL(document.location)).searchParams;
      const initialDate = params.get('initialDate')
      if (initialDate) {
        calendarOptions.defaultDate = initialDate;
      }
      // Initial the calendar.
      if (calendarEl) {
        if (drupalSettings.calendar) {
          drupalSettings.calendar[viewIndex] = new FullCalendar.Calendar(calendarEl, calendarOptions);
        }
        else {
          drupalSettings.calendar = [];
          drupalSettings.calendar[viewIndex] = new FullCalendar.Calendar(calendarEl, calendarOptions);
        }
        let calendarObj = drupalSettings.calendar[viewIndex];
        calendarObj.render();
        // Language dropdown box.
        if (viewSettings.languageSelector) {
          // build the locale selector's options
          calendarObj.getAvailableLocaleCodes().forEach(function(localeCode) {
            var optionEl = document.createElement('option');
            optionEl.value = localeCode;
            optionEl.selected = localeCode == calendarOptions.locale;
            optionEl.innerText = localeCode;
            localeSelectorEl.appendChild(optionEl);
          });
          // when the selected option changes, dynamically change the calendar option
          localeSelectorEl.addEventListener('change', function() {
            if (this.value) {
              let viewIndex = parseInt(this.getAttribute("data-calendar-view-index"));
              drupalSettings.calendar[viewIndex].setOption('locale', this.value);
            }
          });
        }
        else if (localeSelectorEl){
          localeSelectorEl.style.display = "none";
        }

        // Double click event.
        calendarEl.addEventListener('dblclick' , function(e) {
          let viewIndex = parseInt(this.getAttribute("data-calendar-view-index"));
          let viewSettings = drupalSettings.fullCalendarView[viewIndex];
          // New event window can be open if following conditions match.
          // * The new event content type are specified.
          // * Allow to create a new event by double click.
          // * User has the permission to create a new event.
          // * The add form for the new event type is known.
          if (
              slotDate &&
              viewSettings.eventBundleType &&
              viewSettings.dblClickToCreate &&
              viewSettings.addForm !== ""
            ) {
              // Open a new window to create a new event (content).
              window.open(
                  drupalSettings.path.baseUrl +
                  viewSettings.addForm +
                  "?start=" +
                  slotDate +
                  "&start_field=" +
                  viewSettings.startField +
                  "&destination=" + window.location.pathname,
                "_blank"
              );
            }

        });
      }
    });
  }

  // document.ready event does not work with BigPipe.
  // The workaround is to ckeck the document state
  // every 100 milliseconds until it is completed.
  // @see https://www.drupal.org/project/drupal/issues/2794099#comment-13274828
  var checkReadyState = setInterval(function() {
    if (
        document.readyState === "complete" &&
        $('.js-drupal-fullcalendar').length > 0
        ) {
      clearInterval(checkReadyState);
      // Build calendar objects.
      buildCalendars();
    }
  }, 100);

  // After an Ajax call, the calendar objects need to rebuild,
  // to reflect the changes, such as Ajax filter.
  $( document ).ajaxComplete(function( event, request, settings ) {
    // Remove the existing calendars except updating Ajax events.
    if (
        drupalSettings.calendar &&
        settings.url !== '/fullcalendar-view-event-update' &&
        settings.url.indexOf('_wrapper_format=drupal_modal') < 0
        ) {
      // Rebuild the calendars.
      drupalSettings.calendar.forEach(function(calendar) {
        calendar.destroy();
      });
      //Re-build calendars.
      buildCalendars();
    }
  });

})(jQuery, Drupal);
