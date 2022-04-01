<?php

namespace Drupal\hacked;

/**
 * Base class for the different ways that files can be hashed.
 */
abstract class hackedFileHasher {
  /**
   * Returns a hash of the given filename.
   *
   * Ignores file line endings
   */
  function hash($filename) {
    if (file_exists($filename)) {
      if ($hash = $this->cache_get($filename)) {
        return $hash;
      }
      else {
        $hash = $this->perform_hash($filename);
        $this->cache_set($filename, $hash);
        return $hash;
      }
    }
  }

  function cache_set($filename, $hash) {
    \Drupal::cache(HACKED_CACHE_TABLE)->set($this->cache_key($filename), $hash, strtotime('+7 days'));
  }

  function cache_get($filename) {
    $cache = \Drupal::cache(HACKED_CACHE_TABLE)->get($this->cache_key($filename));
    if (!empty($cache->data)) {
      return $cache->data;
    }
  }

  function cache_key($filename) {
    $key = array(
      'filename' => $filename,
      'mtime' => filemtime($filename),
      'class_name' => get_class($this),
    );
    return sha1(serialize($key));
  }

  /**
   * Compute and return the hash of the given file.
   *
   * @param $filename
   *   A fully-qualified filename to hash.
   *
   * @return string
   *   The computed hash of the given file.
   */
  abstract function perform_hash($filename);

  /**
   * Compute and return the lines of the given file.
   *
   * @param $filename
   *   A fully-qualified filename to return.
   *
   * @return array|bool
   *   The lines of the given filename or FALSE on failure.
   */
  abstract function fetch_lines($filename);
}