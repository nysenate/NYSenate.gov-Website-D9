## INTRODUCTION

The [Drupal 7 Location module][1] allows users to store locations in a
`location` field (`location_cck` submodule), and also directly for `node`,
`taxonomy_term` and `user` entities (`location_node`, `location_taxonomy` and
`location_user` submodules).

With `location_email`, `location_fax`, `location_phone` and `location_www`
submodules, the data stored for a location can be extended with additional
_email_, _fax number_, _telephone number_ and _www address_ properties.

__The Location Migration module provides migration path for these data.__

### HOW IT WORKS

For location data stored in a field,
* The original `location` field type gets mapped to `address` field, and every
  _address-like_ location property is migrated into this address field.
* Non-empty geographical coordinates are migrated into a new `geolocation`
  field with `_geoloc` field name suffix.
* When `location_email` is enabled on the source site, the email addresses
  stored for a location will be migrated into a new `email` field with `_email`
  field name suffix. The `email` field type is available in Drupal 8/9 by
  default.
* When `location_fax` is enabled on the source site AND the `telephone` field
  type is available on the destination site (core _Telephone_ module), the fax
  number of a location will be migrated into a new `telephone` field with `_fax`
  field name suffix.
* If `location_phone` is enabled on the source site AND the `telephone` field
  type is available on the destination site, the telephone number stored for a
  location will be migrated into a new `telephone` field (`_phone` field name
  suffix).
* When `location_www` is enabled on the source site AND the `link` field type is
  available on the destination site (core _Link_ module), the www address stored
  for a location will be migrated into a new `link` field (`_url` field name
  suffix).

For location data stored directly for `node`, `taxonomy_term` and `user`
entities, Location Migration basically repeats the same what it does for the
location fields, but the name of the (this time new) address field, and the base
name of the additional fields will be `location_node`, `location_taxonomy_term`
and `location_user`, accordingly. If this _entity location_ was configured to
store multiple locations, the new field's name will have an additional
`_<cardinality>` suffix as well.

__Location Migration wants to migrate as much data as possible__. If you don't
need any of the additional fields, you can delete them __after the migration was
executed__.

## REQUIREMENTS

This module depends on the following modules:

* Address (https://www.drupal.org/project/address)
* Geolocation (https://www.drupal.org/project/geolocation)
* Migrate Drupal (included in Drupal core)

## INSTALLATION

You can install Location Migration as you would normally install a contributed
Drupal 8 or 9 module.

## CONFIGURATION

This module does not have any configuration option.

## MAINTAINERS

Current maintainer:
* Zoltán Horváth (huzooka) - https://www.drupal.org/user/54136

This project has been sponsored by [Acquia](https://www.acquia.com/)

[1]: https://www.drupal.org/node/18723
