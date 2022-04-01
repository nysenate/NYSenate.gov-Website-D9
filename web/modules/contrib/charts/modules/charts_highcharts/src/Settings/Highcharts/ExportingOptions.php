<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Class ExportingOptions.
 */
class ExportingOptions implements \JsonSerializable {

  /**
   * Is exporting enabled.
   *
   * @var bool
   */
  private $enabled = TRUE;

  /**
   * ExportingOptions constructor.
   *
   * @param array $array
   *   (optional) A keyed array defining the exporting settings.
   */
  public function __construct(array $array = []) {
    foreach ($array as $key => $value) {
      switch ($key) {
        case 'enabled':
          $this->setEnabled($value);
          break;
      }
    }
  }

  /**
   * Get enabled.
   *
   * @return bool
   *   Whether exporting is enabled.
   */
  public function getEnabled() {
    return $this->enabled;
  }

  /**
   * Set enabled.
   *
   * @param bool $enabled
   *   Enabled if TRUE, disabled otherwise.
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
  }

  /**
   * Json Serialize.
   *
   * @return array
   *   Json Serialize.
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
