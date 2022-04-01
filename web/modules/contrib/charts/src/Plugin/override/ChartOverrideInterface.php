<?php


namespace Drupal\charts\Plugin\override;

/**
 * Defines an interface for ChartSettings plugins.
 */
interface ChartOverrideInterface {

  /**
   * Return the name of the reusable chart setting plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Builds an array of Chart Settings with key value pairs
   *
   * @param $settings array
   *
   * @return array
   */
  public function chartOverrides(array $settings = []);

}
