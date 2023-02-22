## Description

This lightweight module will add to functionality for views filtering.

This module will provide two additional filters to allow database fields to be
used in a filter query.

The first filter allows two single valued fields to be compared using a
string or numeric comparison with a range of operators such as "equal",
"not equal", "greater than" or "less than".  If either of the fields have
multiple values, then only the first value will be used.

The second filter allows for the value of the left field (single value) to
be checked whether it is contained in the set of values from the right
field (multi-valued field). The filter can pass values with are included in
the set, or which are NOT included in the set.

The left and right fields for each of the filters can be from different
entity types, but the comparison should be meaningful (ie both string, or
numeric, or entity reference, etc). The comparison is done via SQL so type
casting of values is limited.

Note that these filters will only work on views that are using fields,
and the required fields must be included in the view.  They can be hidden
if they are not required in the display.


## REQUIREMENTS
This module requires:
* [Views](https://www.drupal.org/project/views)


## Usage
1. Install the module in the usual way.
2. When configuring a view, selct the "Field comparison" or "Field
   contained" filters from the "Global" category.
4. Select the left and right fields and the required operation.


## MAINTAINERS

Current maintainers:
* James Scott (jlscott) - https://www.drupal.org/u/jlscott


James Scott (jlscott)
7 Sep 2022
