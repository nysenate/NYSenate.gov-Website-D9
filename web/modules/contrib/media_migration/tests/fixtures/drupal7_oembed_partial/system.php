<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 9.2.6 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('system')
->fields(array(
  'filename' => 'sites/all/modules/media_oembed/media_oembed.module',
  'name' => 'media_oembed',
  'type' => 'module',
  'owner' => '',
  'status' => '1',
  'bootstrap' => '0',
  'schema_version' => '0',
  'weight' => '0',
  'info' => 'a:13:{s:4:"name";s:13:"Media: oEmbed";s:11:"description";s:42:"Adds oEmbed as a supported media provider.";s:7:"package";s:5:"Media";s:4:"core";s:3:"7.x";s:12:"dependencies";a:1:{i:0;s:14:"media_internet";}s:5:"files";a:2:{i:0;s:37:"includes/MediaOEmbedStreamWrapper.inc";i:1;s:39:"includes/MediaInternetOEmbedHandler.inc";}s:9:"configure";s:31:"admin/config/media/media-oembed";s:7:"version";s:7:"7.x-2.7";s:7:"project";s:12:"media_oembed";s:9:"datestamp";s:10:"1467129893";s:5:"mtime";i:1648601256;s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
))
->execute();
