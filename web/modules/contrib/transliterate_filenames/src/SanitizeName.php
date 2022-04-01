<?php

namespace Drupal\transliterate_filenames;

use Drupal\Component\Transliteration\TransliterationInterface;

/**
 * Class SanitizeName.
 *
 * @package Drupal\transliterate_filenames
 */
class SanitizeName {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * SanitizeName constructor.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

  /**
   * Sanitize the file name.
   *
   * @param string $filename
   *   The file name that will be sanitized.
   *
   * @return string
   *   Sanitized file name.
   */
  public function sanitizeFilename($filename) {
    $filename = $this->transliteration->transliterate($filename);
    // Replace whitespace.
    $filename = str_replace(' ', '-', $filename);
    // Remove remaining unsafe characters.
    $filename = preg_replace('![^0-9A-Za-z_.-]!', '', $filename);
    // Remove multiple consecutive non-alphabetical characters.
    $filename = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $filename);
    // Force lowercase to prevent issues on case-insensitive file systems.
    $filename = strtolower($filename);

    return $filename;
  }

}
