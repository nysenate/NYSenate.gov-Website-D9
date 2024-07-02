Other View Filter
---------------------

About
---------------------
Frequently two views, or two displays of one view, occur on the page
together. One common example is a listing of content with a few curated
items selected via more specific criteria. It's simple enough to create
two displays and show the curated one above the general listing. This
presents a problem: content that appears in the curated list needs to
somehow be excluded from the general listing.

The filter provided by this module exclude nodes (or another content)
from a view, by excluding the results of one view from another one.
This allows you to filter a view results by another view.

Basic usage
---------------------
 1. Install the module under sites/all/modules/contrib or sites/all/modules
 2. Enable the module
 3. In the view from which you wish exclude items, add filter
      "Other view result"
 4. In filter settings choose "view & display" whose output you want to
      exclude from the current view
 5. Save the view

Hint
---------------------
To minimize the cost to performance of running multiple views queries, the
number of instances of this filter on the same display should be kept to a
minimum and caching should be employed liberally.
Using more than 1 view for filtering will strongly decrease your site
performance. To minimize performance decreasing use simple and cached views.
Views Content cache and Cache Actions are helpful for this.

Requirements
---------------------
 * Views

Installation
---------------------
 1. Install the module under sites/all/modules/contrib or sites/all/modules
 2. Enable the module
