CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Example
 * Compatibility

INTRODUCTION
------------

The Views exclude previous module provides a views filter that excludes nodes
that have already been loaded/displayed on the current page.

This is very useful when you have pages with several embedded views,
and you want to make sure that a given node only appears once in the page.

The advantage of using Views exclude previous rather than manually
excluding nodes is that:

- Each view does not need to know which context it will be displayed in
- Each view can be displayed in a different context
(different page, different order) without needing to change the filter
- Only the nodes that are actually displayed are taken into consideration
  - not those that would fall beyond the first page of the pager

Example
-------

Say you have a view A which displays the 5 most recent nodes
tagged with 'Carrot' ;
and a view B which displays the 5 most recent nodes tagged with 'Freedom'.
Both views are displayed on the same page (A before B) ;
and both have the Views Exclude Previous filter.
Then, given a node N tagged with both 'Carrot' and 'Freedom' :

- If node N is in the 5 most recent entries of both A and B, then it will
  be displayed for A only
- If node N is in the 5 most recent entries of B, but not in the five most
  recent entries of A then it will be displayed for B

Compatibility
-------------

There is no reliable way within Drupal to know when a node has been displayed.
Views exclude previous does it's best by hooking into various relevant parts
of the system.

At the time of writting, Views exclude previous will be able to track nodes
that have been displayed using:

The Views module, with the 'Row style' set to 'Node'
The Views module, with the 'Row style' set to 'Field'
and using the default 'Unformatted', 'List', 'Grid' or 'Table' styles.
It will also track nodes that are loaded/prepared for viewing using the nodeapi.
If you know of any module that embeds nodes/lists/etc in pages,
please report whether they work/don't work with Views exclude
previous so that the compatibility list can be kept up to date. 
