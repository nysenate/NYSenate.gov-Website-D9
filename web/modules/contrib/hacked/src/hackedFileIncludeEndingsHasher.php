<?php

namespace Drupal\hacked;

/**
 * This is a much faster, but potentially less useful file hasher.
 */
class hackedFileIncludeEndingsHasher extends hackedFileHasher {
  function perform_hash($filename) {
    return sha1_file($filename);
  }

  function fetch_lines($filename) {
    return file($filename);
  }
}
