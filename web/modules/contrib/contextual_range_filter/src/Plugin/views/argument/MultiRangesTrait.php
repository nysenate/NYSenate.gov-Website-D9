<?php

namespace Drupal\contextual_range_filter\Plugin\views\argument;

/**
 * Common member functions for Views contextual range argument plugins.
 */
trait MultiRangesTrait {

  /**
   * Break xfrom--xto+yfrom--yto+zfrom--zto into an array of ranges.
   *
   * @param string $str
   *   The string to parse.
   */
  protected function breakPhraseRange($str) {
    if (empty($str)) {
      return;
    }
    $this->value = preg_split('/[+ ]/', $str);
    $this->operator = 'or';
    // Keep an 'error' value if invalid ranges were given.
    // A single non-empty value is ok, but a plus sign without values is not.
    if (count($this->value) > 1 && (empty($this->value[0]) || empty($this->value[1]))) {
      $this->value = FALSE;
    }
  }

}
