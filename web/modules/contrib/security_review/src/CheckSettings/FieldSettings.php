<?php

namespace Drupal\security_review\CheckSettings;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\security_review\CheckSettings;

/**
 * Provides the settings form for the Field check.
 */
class FieldSettings extends CheckSettings {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = [];
    $known_risky_fields = $this->get('known_risky_fields', []);
    $known_risky_fields = $this->allowedValuesString($known_risky_fields);
    $form['known_risky_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hashes'),
      '#description' => $this->t('SHA-256 hashes of entity_type, entity_id, field_name and field content to be skipped in future runs. Enter one value per line, in the format hash|reason.'),
      '#default_value' => $known_risky_fields,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array $values) {
    $known_risky_fields = $values['known_risky_fields'];
    $known_risky_fields = static::extractAllowedValues($known_risky_fields);

    $this->set('known_risky_fields', $known_risky_fields);
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   *   Parts of code copied from
   *   Drupal\options\Plugin\Field\FieldType\ListItemBase::extractAllowedValues().
   *
   * @see \Drupal\security_review\CheckSettings\FieldSettings::allowedValuesString()
   */
  protected static function extractAllowedValues($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $values[$key] = $value;
      }
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of 'allowed values'.
   *
   * This string format is suitable for edition in a textarea.
   *
   * Copied from
   * Drupal\options\Plugin\Field\FieldType\ListItemBase::allowedValuesString().
   *
   * @param array $values
   *   An array of values, where array keys are values and array values are
   *   labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "value|label" or "value".
   */
  protected function allowedValuesString(array $values): string {
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

}
