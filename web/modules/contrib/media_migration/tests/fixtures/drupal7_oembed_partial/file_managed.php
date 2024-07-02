<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 9.2.10 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('file_managed')
  ->fields([
    'fid' => '83863',
    'uid' => '1',
    'filename' => 'ACSF',
    'uri' => 'oembed://https%3A//player.vimeo.com/video/268828727',
    'filemime' => 'video/oembed',
    'filesize' => '0',
    'status' => '1',
    'timestamp' => '1648447404',
    'type' => 'remote_video',
    'type' => 'video',
  ])
  ->execute();
