CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Use
 * Views support
 * Examples
 * Maintainers


INTRODUCTION
------------

Calendar links provides two Twig functions for generating links for various
calendaring services.


REQUIREMENTS
------------

This module does not require any additional modules outside of Drupal core.

This module uses the 
[spatie/calendar-links](https://packagist.org/packages/spatie/calendar-links)
library as its foundation. Use composer to install the module (see INSTALLATION)
or run `composer require spatie/calendar-links` separately.


INSTALLATION
------------

 * Install with composer to ensure dependencies are also installed: 
   `composer require 'drupal/calendar_link'`

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

There is no configuration available for this module.


USE
---

This module provides two new Twig functions for generating calendar link URLs:

1. `calendar_link`

Returns a string link for a specific calendar type. Available types are:

*. Apple iCal/Microsoft Outlook (`ics`)
*. Google calendar (`google`)
*. Outlook.com (`webOutlook`)
*. Yahoo! calendar (`yahoo`)

2. `calendar_links`

Returns an array of links for all available calendar types. Each array element
has the following keys/data:

*. `type_key`: The calendar type key (`ics`, `google`, etc.)
*. `type_name`: The calendar type name ("iCal", "Google", etc.)
*. `url`: The URL for the calendar item.


VIEWS SUPPORT
-------------

When using values from Views results only the default formatter for date fields
is supported. Most other date field formatters do not provide necessary timezone
data in rendered results to ensure correctness of the generated calendar links.
See #3249457: Views support for further details and discussion.


EXAMPLES
--------

Assume an example "Event" node with the extras fields:

*. Title (string `title`)
*. Start date/time (datetime `field_start`)
*. End date/time (datetime `field_end`)
*. All day event (boolean `field_all_day`)
*. Description (text_format `body`)
*. Location (string `field_location`)

In a twig template, the following code with generate a link to the event to a 
Google calendar:

```twig
{% set link = calendar_link('google', 
  node.title,
  node.field_start,
  node.field_end,
  node.field_all_day,
  node.body,
  node.field_location
)
%}
<a href="{{ link }}">Add to Google</a>
```

Or, to create a list of links for each service:

```twig
{% set links = calendar_links(
  node.title,
  node.field_start,
  node.field_end,
  node.field_all_day,
  node.body,
  node.field_location
)
%}
<ul>
{% for link in links %}
  <li>
    <a href="{{ link.url }}" 
       class="calendar-link-{{ link.type_key }}">{{ link.type_name }}</a>
  </li>
{% endfor %}
</ul>
```


MAINTAINERS
-----------

Current maintainers:

 * [Christopher C. Wells (wells)](https://www.drupal.org/u/wells)

Development is sponsored by:

 * [Cascade Public Media](https://www.drupal.org/cascade-public-media)
