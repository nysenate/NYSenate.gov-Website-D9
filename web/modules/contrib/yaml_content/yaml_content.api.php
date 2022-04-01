<?php

/**
 * @file
 * API functionality for the yaml_content module.
 */

/**
 * Process imported content after an entire file has been parsed and imported.
 *
 * @param string $content_file
 *   The name of the file that has just finished importing content.
 * @param \Drupal\Core\Entity\EntityInterface[] $loaded_content
 *   An array of loaded entity data from the parsed content data.
 * @param array $content_data
 *   The parsed content array loaded from `$content_file`.
 */
function hook_yaml_content_post_import($content_file, array &$loaded_content, array $content_data) {

}
