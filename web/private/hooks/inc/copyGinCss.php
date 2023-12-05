<?php

/**
 * @file
 * Copies a version controlled css file into the Drupal filesystem.
 *
 * See https://docs.pantheon.io/guides/quicksilver.
 */

$gin_css = __DIR__ . '/../../../themes/custom/nysenate_theme/gin_css/gin-custom.css';
if (file_exists($gin_css)) {
  $content = file_get_contents($gin_css);
  $destination = __DIR__ . '/../../../sites/default/files/gin-custom.css';
  file_put_contents($destination, $content);
}
