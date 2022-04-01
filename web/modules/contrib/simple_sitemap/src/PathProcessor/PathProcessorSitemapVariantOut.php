<?php

namespace Drupal\simple_sitemap\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Class PathProcessorSitemapVariantOut
 * @package Drupal\simple_sitemap\PathProcessor
 */
class PathProcessorSitemapVariantOut implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $args = explode('/', $path);
    if (count($args) === 4 && $args[3] === 'sitemap.xml') {
      $path = '/' . $args[2] . '/sitemap.xml';
    }

    return $path;
  }

}
