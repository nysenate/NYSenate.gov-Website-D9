<?php

namespace Drupal\webform_ui\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for webform UI.
 */
class WebformUiPathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ((strpos($path, '/webform/') === FALSE)
      || is_null($request)
      || is_null($request->getQueryString())
    ) {
      return $path;
    }

    if (strpos($request->getQueryString(), '_wrapper_format=') === FALSE) {
      return $path;
    }

    $query = [];
    parse_str($request->getQueryString(), $query);
    if (empty($query['destination'])) {
      return $path;
    }

    $destination = $query['destination'];
    $options['query']['destination'] = $destination;
    return $path;
  }

}
