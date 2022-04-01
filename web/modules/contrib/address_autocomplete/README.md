<pre>
  ┌───────┐
  │       │
  │  a:o  │  acolono.com
  │       │
  └───────┘
</pre>

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Installation
 * Configuration
 * Developers


INTRODUCTION
------------

This module enhances the Address module with autocomplete functionality.

When you start to type the street, a list of matching addresses will be shown. When you pick one all relevant address fields (like street, city, postcode, country) will be populated.

Multiple provider can be configured.

Currently available:

 * [Swiss Post API](https://developer.post.ch/en/address-web-services-rest)
 * [Google Maps](https://developers.google.com/maps/documentation/geocoding/start)
 * [Mapbox Geocoding](https://docs.mapbox.com/api/search/geocoding/)


FEATURES
------------

 * new `Address Autocomplete` Widget included, which adds Drupal's `#autocomplete` functionality to the `address` field
 * contains extendable AddressProvider plugin system, so it's possible to add new Provider plugins easily & switch between them


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

 * Configuration page is located here: admin/config/address-autocomplete
 * Enable API provider you want to use, configure it by clicking on the Settings button
 * Create a new Address field (or just use the old one) and switch its Widget to 'Address autocomplete'
 * That's all :)


DEVELOPERS
--------------

If you would like to add a new API provider, just simply extend `AddressProviderBase`, and add your API request implementation. Don't forget to contribute it :)


by acolono GmbH
---------------

~~we build your websites~~
we build your business

hello@acolono.com

www.acolono.com
www.twitter.com/acolono
www.drupal.org/acolono-gmbh
