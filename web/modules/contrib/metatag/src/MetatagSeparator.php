<?php

namespace Drupal\metatag;

/**
 * Separator logic used elsewhere.
 */
trait MetatagSeparator {

  /**
   * The default separator to use when one is not defined through configuration.
   *
   * @var string
   */
  public static $defaultSeparator = ',';

  /**
   * Returns the multiple value separator for this site.
   *
   * This is the character used to explode multiple values. It defaults to a
   * comma but can be set to any other character or string.
   *
   * @return string
   *   The correct separator.
   */
  public function getSeparator(): string {
    $separator = '';

    // Load the separator saved in configuration.
    $config = $this->configFactory->get('metatag.settings');

    // @todo This extra check shouldn't be needed.
    if (!empty($config)) {
      $separator = $config->get('separator');
    }

    // By default the separator setting has a blank value, so use the default
    // value defined above.
    if (is_null($separator) || $separator == '') {
      $separator = $this::$defaultSeparator;
    }

    return $separator;
  }

}
