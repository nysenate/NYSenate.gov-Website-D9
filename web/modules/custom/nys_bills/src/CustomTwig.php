<?php

namespace Drupal\nys_bills;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Custom twig functions.
 */
class CustomTwig extends AbstractExtension {

  /**
   * Register custom twig functions.
   */
  public function getFunctions() {
    return [
      new TwigFunction('get_readmore_parts', [$this, 'getReadMoreParts']),
    ];
  }

  /**
   * Process the text into parts.
   */
  public function getReadMoreParts($text, $delimiter = '\n', $position = 3) {
    $data['show_expander'] = preg_match_all('/' . $delimiter . '/', $text) > 50;
    return array_merge($data, $this->strSplitAtNth($text, $delimiter, $position));
  }

  /**
   * Split the text at nth occurance.
   */
  public function strSplitAtNth($haystack, $needle, $nth) {
    $max = strlen($haystack);
    $n = 0;
    for ($i = 0; $i < $max; $i++) {
      if ($haystack[$i] == $needle) {
        $n++;
        if ($n > $nth) {
          break;
        }
      }
    }

    $output['part_1'] = substr($haystack, 0, $i);
    $output['part_2'] = substr($haystack, $i + 1, $max);

    $output['extra_line_count'] = preg_match_all('/\n/', $output['part_2']) + 1;

    return $output;
  }

}
