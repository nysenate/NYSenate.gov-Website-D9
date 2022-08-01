ADDRESS MAP LINK
================

INTRODUCTION
------------

The Address Map Link module adds additional field formatter settings that allow
 [Address](https://www.drupal.org/project/address) fields to be linked to an
 external map site. Some supported mapping sites have the ability to open the
 their map with the get directions form filled out with the address as the
 destination.

Supported mapping sites:
* Apple Maps
 (Note: Apple Maps will redirect to Google Maps if user is not using iOS.)
* Bing Maps
* Google Maps
* Google Maps - Directions
* HERE WeGo maps - Directions
* Mapquest
* OpenStreetMaps
* Yandex maps
* Waze - Directions
* Waze - Navigate (Immediately starts navigating)

REQUIREMENTS
------------
This module requires the following modules:

* [Address](https://www.drupal.org/project/address)

RECOMMENDED MODULES
-------------------

* [Token](https://www.drupal.org/project/token): When enabled the map link text
 can leverage tokens.

INSTALLATION
------------

This module can be installed and enabled like any other Drupal module. However,
 because the [Address module](https://www.drupal.org/project/address) requires
 installation via composer, it is recommended for this module as well.

   ```sh
   composer require drupal/address_map_link:^1.0
   ```

CONFIGURATION
-------------

1. Go to the "Manage display" tab of the content type (or entity bundle) and
 click the settings button next to the address field you would like to link.

2. Select the "Link Address to Map" checkbox to link that address field.

3. Further configure this link by adjusting the Map Link type, the position,
 link text, and the option to open the link in a new window.

### Adding Additional Map Providers

Additional mapping sites can be defined by adding MapLink plugins. Reference
 one of the MapLink plugins defined in `Drupal\address_map_link\Plugin\MapLink`

OTHER USAGE
-----------

There may be times you want to use Address Map Link manually to create a map
 link that is completely separate from your address field display.

Address Map Link has a service called `plugin.manager.map_link` that can be
 used to create map urls. The following exposes a new variable for use in a
 node template. This code would get added to a custom module.

```
<?php

/**
 * @file
 * Your .module file.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Implements hook_ENTITY_TYPE_view().
 */
function hook_node_view(
  array &$build,
  EntityInterface $entity,
  EntityViewDisplayInterface $display,
  $view_mode) {
  // Build a link to Google using address_map_link module.
  if (!$entity->field_address->isEmpty()) {
    // field_address is the Address Field attached to the content type.
    $address = $entity->field_address->first();
    // Call the Address Map Link plugin.manager.map_link service.
    $mapLinkManager = Drupal::service('plugin.manager.map_link');
    // Specify the map link plugin type to use.
    $mapLinkType = $mapLinkManager->createInstance('google_maps_directions');
    // Pass Address to getAddressUrl and create a URL based on the address.
    // directions_url is now available for use in the node template.
    $build['directions_url']['#markup'] = $mapLinkType
      ->getAddressUrl($address)->toString();
  }
}

```

MAINTAINERS
-----------

Current maintainers:

* Chris Snyder (chrissnyder) - https://www.drupal.org/u/chrissnyder
