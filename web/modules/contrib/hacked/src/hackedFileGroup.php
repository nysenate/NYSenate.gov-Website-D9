<?php

namespace Drupal\hacked;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Represents a group of files on the local filesystem.
 */
class hackedFileGroup {
  use StringTranslationTrait;

  var $base_path = '';
  var $files = array();
  var $files_hashes = array();
  var $file_mtimes = array();

  var $hasher;

  /**
   * Constructor.
   */
  function __construct($base_path) {
    $this->base_path = $base_path;
    $this->hasher = hacked_get_file_hasher();
  }

  /**
   * Return a new hackedFileGroup listing all files inside the given $path.
   */
  static function fromDirectory($path) {
    $filegroup = new hackedFileGroup($path);
    // Find all the files in the path, and add them to the file group.
    $filegroup->scan_base_path();
    return $filegroup;
  }

  /**
   * Return a new hackedFileGroup listing all files specified.
   */
  static function fromList($path, $files) {
    $filegroup = new hackedFileGroup($path);
    // Find all the files in the path, and add them to the file group.
    $filegroup->files = $files;
    return $filegroup;
  }

  /**
   * Locate all sensible files at the base path of the file group.
   */
  function scan_base_path() {
    $files = hacked_file_scan_directory($this->base_path, '/.*/', array(
      '.',
      '..',
      'CVS',
      '.svn',
      '.git'
    ));
    foreach ($files as $file) {
      $filename = str_replace($this->base_path . '/', '', $file->filename);
      $this->files[] = $filename;
    }
  }

  /**
   * Hash all files listed in the file group.
   */
  function compute_hashes() {
    foreach ($this->files as $filename) {
      $this->files_hashes[$filename] = $this->hasher->hash($this->base_path . '/' . $filename);
    }
  }

  /**
   * Determine if the given file is readable.
   */
  function is_readable($file) {
    return is_readable($this->base_path . '/' . $file);
  }

  /**
   * Determine if a file exists.
   */
  function file_exists($file) {
    return file_exists($this->base_path . '/' . $file);
  }

  /**
   * Determine if the given file is binary.
   */
  function is_not_binary($file) {
    return is_readable($this->base_path . '/' . $file) && !hacked_file_is_binary($this->base_path . '/' . $file);
  }

  function file_get_location($file) {
    return $this->base_path . '/' . $file;
  }

}