<?php

declare(strict_types=1);

namespace Drupal\nysenate\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class UrlTransform provides twig extension.
 *
 * @package Drupal\nysenate
 */
class JsonDecode extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $filters = [
      new TwigFilter('json_decode', [$this, 'jsonDecodeString']),
    ];
    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'json_decode';
  }

  /**
   * {@inheritdoc}
   */
  public static function jsonDecodeString($json_string) {
    $array = json_decode($json_string, TRUE);
    return $array;
  }

}
