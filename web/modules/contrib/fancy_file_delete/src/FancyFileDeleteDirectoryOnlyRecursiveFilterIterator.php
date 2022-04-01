<?php

namespace Drupal\fancy_file_delete;

/**
 * Class FancyFileDeleteDirectoryOnlyRecursiveFilterIterator.
 */
class FancyFileDeleteDirectoryOnlyRecursiveFilterIterator extends \RecursiveFilterIterator {

  /**
   * {@inheritdoc}
   */
  public function __construct(\RecursiveIterator $iterator, array $exclude_paths = array()) {
    $this->_exclude_paths = $exclude_paths;
    parent::__construct($iterator);
  }

  /**
   * {@inheritdoc}
   */
  public function accept() {
    if ($this->current()->isReadable()) {
      $filename = $this->current()->getFilename();
      // Skip hidden files and directories.
      if ($filename[0] === '.') {
        return FALSE;
      }

      if (!$this->isDir()) {
        return FALSE;
      }
      $path = $this->current()->getPathname();
      foreach ($this->_exclude_paths as $exclude_path) {
        if (strpos($path, $exclude_path) === 0) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }
}
