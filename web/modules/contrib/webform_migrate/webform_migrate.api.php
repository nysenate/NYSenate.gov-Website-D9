<?php

/**
 * @file
 * Hooks related to Webfor migrate module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the markup of webform element during migration from Drupal 7.
 *
 * @See \Drupal\webform_migrate\Plugin\migrate\source\d7\D7Webform::buildFormElements()
 *
 * @param string $markup
 *   Webform element yaml markup string.
 * @param string $indent
 *   Webform element yaml markup indentation string.
 * @param array $element
 *   Prepared array of webform element from migration source, keyed on
 *   the machine-readable element name.
 */
function hook_webform_migrate_d7_webform_element_ELEMENT_TYPE_alter(&$markup, $indent, array $element) {
  // Define custom webform element type from contrib or custom module.
  $markup .= "$indent  '#type': your_custom_type\n";

  // Alter existing webform element type markup.
  $markup = str_replace('[from]', '[to]', $markup);
}

/**
 * Alters the markup of webform element during migration from Drupal 6.
 *
 * @See \Drupal\webform_migrate\Plugin\migrate\source\d6\D6Webform::buildFormElements()
 *
 * @param string $markup
 *   Webform element yaml markup string.
 * @param string $indent
 *   Webform element yaml markup indentation string.
 * @param array $element
 *   Prepared array of webform element from migration source, keyed on
 *   the machine-readable element name.
 */
function hook_webform_migrate_d6_webform_element_ELEMENT_TYPE_alter(&$markup, $indent, array $element) {
  // Define custom webform element type from contrib or custom module.
  $markup .= "$indent  '#type': your_custom_type\n";

  // Alter existing webform element type markup.
  $markup = str_replace('[from]', '[to]', $markup);
}

/**
 * @} End of "addtogroup hooks".
 */
