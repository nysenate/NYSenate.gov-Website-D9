
PREPOPULATE MODULE
==================
By ea.Farris, based on an idea from chx.
Maintained by Addison Berry (add1sun).
Drupal 8 Version by Sumit Madan (sumitmadan) and Ajit (ajits) and Lucas Hedding
(heddn)

Prepopulate is an attempt to solve the problem that resulted from
the discussion at http://www.drupal.org/node/27155 where the $node object,
it was (correctly, I believe) decided, should
not be prefilled from the $_GET variables, and instead, the power of the
FormsAPI should be used to modify the #default_value of the form
elements themselves.

This functionality will make things like bookmarklets easier to write,
since it basically allows forms to be prefilled from the URL, using a
syntax like:

`http://www.example.com/node/add/page?edit[title][widget][0][value]=simple%20title&edit[body][widget][0][value]=hello%20world&edit[field_entity_reference][widget][0][target_id]=123`

Refer to the USAGE.md file for more examples.

Please report any bugs or feature requests to the Prepopulate issue queue:
http://drupal.org/project/issues/prepopulate
