<?php

namespace Drupal\charts\Theme;

/**
 * Provides an interface defining Charts constants.
 */
interface ChartsInterface {

  /**
   * Used to define a single axis.
   *
   * Constant used in chartsTypeInfo() to declare chart types with a
   * single axis. For example a pie chart only has a single dimension.
   */
  const CHARTS_SINGLE_AXIS = 'y_only';

  /**
   * Used to define a dual axis.
   *
   * Constant used in chartsTypeInfo() to declare chart types with a dual
   * axes. Most charts use this type of data, meaning multiple categories each
   * have multiple values. This type of data is usually represented as a table.
   */
  const CHARTS_DUAL_AXIS = 'xy';

}
