USING PREPOPULATE MODULE
========================

See README.md for a description of this module.
See the tests for complete Form and Field examples: 
https://cgit.drupalcode.org/prepopulate/tree/tests/src/Functional?h=8.x-2.x

Simple Usage
------------

Pre-populate the title field on a node creation form:
`http://www.example.com/node/add/page?edit[title][widget][0][value]=simple%20title`

Pre-populate the body field:
`http://www.example.com/node/add/page?edit[body][widget][0][value]=hello%20world`

Pre-populate an entity reference field:
`http://www.example.com/node/add/page?edit[field_entity_reference][widget][0][target_id]=123`

How to find what variable to set
--------------------------------

This can be tricky, but there are a few things to keep in mind that
should help.

Prepopulate.module is quite simple. It looks through the form, looking
for a variable that matches the name given on the URL, and puts the
value in when it finds a match. Drupal keeps HTML form entities in an
edit[] array structure. All your variables will be contained within the
edit[] array.

A good starting point is to look at the HTML code of a rendered Drupal
form. Once you find the appropriate <input /> (or <textarea>...</textarea>
tag, use the value of the name attribute in your URL, contained in the
edit array. For example, if the <input /> tag looks like this:

    <input id="edit-title" class="form-text required" type="text" value=""
    size="60" name="title" maxlength="128"/>

then try this URL:
`http://www.example.com/node/add/page?edit[title][widget][0][value]=Automatic%20filled%20in%20title`

Notice the pattern of `[widget][0][value]` in many of these URLs? That is how
Drupal builds out the Field API and render arrays. So if you know the render
array structure, then you know what needs to be all the query parameters in the
URL.

Field API  fields will vary a bit depending on how the input widgets are
configured and what is the array element you are trying to populate.

Multiple fields
---------------

Prepopulate can handle pre-filling multiple fields from one URL. Just
separate the edit variables with an ampersand:

`http://www.example.com/node/add/page?edit[title][widget][0][value]=simple%20title&edit[body][widget][0][value]=hello%20world&edit[field_entity_reference][widget][0][target_id]=123`

Escaping special characters
---------------------------

Some characters can't be put into URLs. Spaces, for example, work
mostly, but occasionally they'll have to be replaced with the string %20.
This is known as "percent encoding." Wikipedia has a partial list of
percent codes at:
  http://en.wikipedia.org/wiki/Percent-encoding

If you're having trouble getting content into field names, or are
getting 'page not found' errors from Drupal, you should check to ensure
that illegal characters are properly encoded.


Happy prepopulating!
