<?php

namespace Drupal\name;

use Drupal\Component\Utility\Unicode;

/**
 * Provides custom Unicode-related extension methods.
 *
 * @ingroup utility
 */
class NameUnicodeExtras extends Unicode {

  /**
   * Split each word in a UTF-8 string.
   *
   * @param string $text
   *   The text that will be converted.
   *
   * @return array
   *   The input $text as an array of words.
   */
  public static function explode($text) {
    $regex = '/(^|[' . static::PREG_CLASS_WORD_BOUNDARY . '])/u';
    $words = preg_split($regex, $text, -1, PREG_SPLIT_NO_EMPTY);
    return $words;
  }

  /**
   * Generate the initials of all first characters in a string.
   *
   * Note that this is case-insensitive, camel case words are treated as a
   * single word.
   *
   * @param string $text
   *   The text that will be converted.
   * @param string $delimitor
   *   An optional string to separate each character.
   *
   * @return string
   *   The input $text with first letters of each word capitalized.
   */
  public static function initials($text, $delimitor = '') {
    $text = mb_strtolower($text);
    $results = [];
    foreach (array_filter(self::explode($text)) as $word) {
      $results[] = mb_substr($word, 0, 1);
    }
    $text = implode($delimitor, $results);
    $text = mb_strtoupper($text);
    return $text ? $text . $delimitor : '';
  }

}
