<?php

/**
 * @file
 * Documentation for prepopulate API.
 */

/**
 * Alter whitelisted element types.
 *
 * @param array &$whitelisted_types
 *   Whitelisted element types.
 */
function hook_prepopulate_whitelist_alter(array &$whitelisted_types) {
  // Adds 'my_custom_element'to the list of allowed elements that can be
  // prepopulated.
  $whitelisted_types[] = 'my_custom_element';
}
