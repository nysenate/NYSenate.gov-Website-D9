<?php

namespace Drupal\fancy_file_delete;

/**
 * Class FancyFileDeleteDirectoryOnlyRecursiveFilterIterator.
 */
class FancyFileDeleteDirectoryOnlyRecursiveFilterIterator extends \RecursiveFilterIterator {

  /**
   * The excluded paths.
   *
   * @var array
   */
  protected $exclude_paths;

  /**
   * {@inheritdoc}
   */
  public function __construct(\RecursiveIterator $iterator, array $exclude_paths = []) {
    $this->exclude_paths = $exclude_paths;
    parent::__construct($iterator);
  }

  #[\ReturnTypeWillChange]
  public function accept() {
    if ($this->current()->isReadable()) {
      $filename = $this->current()->getFilename();
      // Skip hidden files and directories.
      if ($filename[0] === '.') {
        return FALSE;
      }

      if (!$this->current()->isDir()) {
        return FALSE;
      }
      $path = $this->current()->getPathname();
      foreach ($this->exclude_paths as $exclude_path) {
        if (strpos($path, $exclude_path) === 0) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }
}
