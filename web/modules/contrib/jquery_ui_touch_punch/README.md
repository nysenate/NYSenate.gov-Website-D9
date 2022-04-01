# jQuery UI Touch Punch

Drupal 8 includes jQuery UI in core, however it is no longer actively
maintained and has been marked deprecated. This module provides the
jQuery UI Touch Punch library for any themes and modules that require it.

  - jQuery UI [Touch Punch documentation](http://touchpunch.furf.com/)
  - jQuery UI [Touch Punch API
    documentation](https://github.com/furf/jquery-ui-touch-punch)

**Caution**: jQuery UI was deprecated from core because it is no longer
actively maintained, and has been marked “End of Life” by the OpenJS
Foundation. It is not recommended to depend on jQuery UI in your own
code, and instead to select a replacement solution as soon as possible.

## Instructions

1.  Download this module and the jQuery UI module.
2.  [Download Jquery Touch Punch](https://github.com/furf/jquery-ui-touch-punch) library and place it in the libraries folder (<drupal root>/libraries). You need the full library which is easily available from the Github repo (the full path to the required js file should be: <drupal root>/libraries/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js).
3.  Install module the [usual way](https://www.drupal.org/documentation/install/modules-themes/modules-8).
4.  Change any references in your theme or module from
    `core/jquery.ui.touch-punch` to `jquery_ui_touch_punch/touch-punch`

### Requirements

  - [jQuery UI](https://www.drupal.org/project/jquery_ui)

### Related modules

  - [jQuery UI Accordion](https://www.drupal.org/project/jquery_ui_accordion)
  - [jQuery UI Button](https://www.drupal.org/project/jquery_ui_button)
  - [jQuery UI Checkboxradio](https://www.drupal.org/project/jquery_ui_checkboxradio)
  - [jQuery UI Controlgroup](https://www.drupal.org/project/jquery_ui_controlgroup)
  - [jQuery UI Draggable](https://www.drupal.org/project/jquery_ui_draggable)
  - [jQuery UI Droppable](https://www.drupal.org/project/jquery_ui_droppable)
  - [jQuery UI Effects](https://www.drupal.org/project/jquery_ui_effects)
  - [jQuery UI Menu](https://www.drupal.org/project/jquery_ui_menu)
  - [jQuery UI Progressbar](https://www.drupal.org/project/jquery_ui_progressbar)
  - [jQuery UI Selectable](https://www.drupal.org/project/jquery_ui_selectable)
  - [jQuery UI Selectmenu](https://www.drupal.org/project/jquery_ui_selectmenu)
  - [jQuery UI Slider](https://www.drupal.org/project/jquery_ui_slider)
  - [jQuery UI Spinner](https://www.drupal.org/project/jquery_ui_spinner)
  - [jQuery UI DatePicker](https://www.drupal.org/project/jquery_ui_datepicker)
