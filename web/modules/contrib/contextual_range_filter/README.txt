
CONTEXTUAL RANGE FILTER (D8/9)
==============================

This is a simple plugin for Views that adds the option to contextually filter
a view not just by a single value, but also by RANGE.

Just like normal contextual filters, contextual range filters may be set by
appending filter "arguments" to the URL. Examples follow below.

Integer, float, string and date ranges are supported.

Node and taxonomy term ids etc are special cases of integers so will work also.

You may use the OR ('+' to use multiple ranges). You can also the negate the
range(s), that is "exclude" rather than "include". You negate by ticking the 
"Exclude" box on the Views Contextual filter configuration panel, in the "More"
section.

To create a contextual range filter, first create a plain contextual filter as
per normal. I.e. in the Views UI open the "Advanced" field set (upper right) and
click "add" next to the heading "Contextual filters". On the next panel select
the field or property that needs to be contextually filtered and "Apply". Fill
out the configuration panel as you see fit, press "Apply" and "Save" the view.

Now visit the Contextual Range Filter configuration page,
admin/config/content/contextual-range-filter, find your contextual filter name
and tick the box left of it to turn the filter into a contextual range filter.
Press "Save configuration".

You apply contextual filters by appending "arguments" to the view's URL.
Using the double-hyphen '--' as a range separator, you can filter your view
output like so:

  http://yoursite.com/yourview/100--199.99  (numeric range)
  http://yoursite.com/yourotherview/k--qzz  (alphabetical range)
  http://yoursite.com/yourthirdview/2020-01-01--2020-06-30 (date range)
  http://yoursite.com/somebodysview/3--6    (list range, using list keys)

All ranges are inclusive of "from" and "to" values.

Wen using a date range you have the option to use "relative dates" as an
alternative to the strict YYYY-MM-DD format. For this tick the checkbox at the
bottom of the Contextual Filter pane in the Views UI. 
Not only does "relative dates" support phrases like "tomorrow" or "10 days ago",
it also supports more colloquial absolute date specifications, such as "20 Nov"
(this year).

You may omit the start or end values to specify open-ended filter ranges:

  http://yoursite.com/yourview/100--

Strings will be CASE-INSENSITIVE, unless your database defaults otherwise. In
your database's alphabet, numbers and special characters (@ # $ % etc.)
generally come before letters , e.g. "42nd Street" comes before "Fifth Avenue"
and also before "5th Avenue". The first printable ASCII character is the space
(%20). The last printable ASCII character is the tilde '~'. So to make sure
everything from "!Hello" and "@the Races" up to and including anything starting
with the letter r is returned, use " --r~".

Multiple contextual filters (e.g. Title followed by Price) are fine and if you
ticked "Allow multiple ranges" also, you can use the plus sign to OR 
filter-ranges like this:

  http://yoursite.com/yourotherview/a--e~+k--r~/--500
  
The above means a--e~ OR k--r~

Or, if your view has "Glossary mode" is on, so that only the first letter
matters, the above becomes:

  http://yoursite.com/yourotherview/a--e+k--r/--500

You may use a colon ':' instead of the double hyphen.
Use either '--', ':' or 'all' to return all View results for the associated
filter:

  http://yoursite.com/yourotherview/all/-100--999.99

You can opt to have URL arguments validated as numeric ranges in the Views UI
fieldset titled "When the filter value IS in the URL ...". Tick the "Specify
validation criteria" box and select the "Numeric Range" validator from the
drop-down. Just like core's "Numeric" validator, the "Numeric Range" validator
must not be selected if the "Allow multiple numeric ranges" box is ticked.
Instead select "-Basic Validation-".


Default Contextual Filter via PHP Code
--------------------------------------
Using a PHP Code snippet to produce a default contextual filter value comes in
very handy when you want to create a side bar of "related content" that is 
based on a range, rather than a single value, such as a taxonomy term.
Examples:
o Nearby locations
o Similarly priced products
o Blog posts published around the same time
For full examples, see:
https://medium.com/@rikdeboer/new-ways-to-display-related-content-with-views-in-drupal-8-9-2444a7a889e3


ASCII AND UTF CHARACTER ORDERING
o http://en.wikipedia.org/wiki/UTF-8
