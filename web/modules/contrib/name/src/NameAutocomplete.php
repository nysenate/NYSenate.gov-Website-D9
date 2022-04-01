<?php

namespace Drupal\name;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines a helper class to get name field autocompletion results.
 */
class NameAutocomplete {

  /**
   * Name options provider.
   *
   * @var NameOptionsProvider
   */
  protected $optionsProvider;

  /**
   * Name field components.
   *
   * @var array
   */
  protected $allComponents = [
    'given',
    'middle',
    'family',
    'title',
    'credentials',
    'generational',
  ];

  /**
   * Constructor for the NameAutocomplete class.
   *
   * @param NameOptionsProvider $options_provider
   *   Name options provider.
   */
  public function __construct(NameOptionsProvider $options_provider) {
    $this->optionsProvider = $options_provider;
  }

  /**
   * Get matches for the autocompletion of name components.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field definition.
   * @param string $target
   *   The name field component.
   * @param string $string
   *   The string to match for the name field component.
   *
   * @return array
   *   An array containing the matching values.
   */
  public function getMatches(FieldDefinitionInterface $field, $target, $string) {
    $matches = [];
    $limit = 10;

    if (empty($string)) {
      return $matches;
    }

    $settings = $field->getSettings();
    foreach ($this->allComponents as $component) {
      if (!isset($settings['autocomplete_source'][$component])) {
        $settings['autocomplete_source'][$component] = [];
      }
      $settings['autocomplete_source'][$component] = array_filter($settings['autocomplete_source'][$component]);
    }

    $action = [];
    switch ($target) {
      case 'name':
        $action['components'] = $this->mapAssoc(['given', 'middle', 'family']);
        break;

      case 'name-all':
        $action['components'] = $this->mapAssoc($this->allComponents);
        break;

      case 'title':
      case 'given':
      case 'middle':
      case 'family':
      case 'credentials':
      case 'generational':
        $action['components'] = [$target => $target];
        break;

      default:
        $action['components'] = [];
        foreach (explode('-', $target) as $component) {
          if (in_array($component, _name_component_keys())) {
            $action['components'][$component] = $component;
          }
        }
        break;

    }

    $action['source'] = [
      'title' => [],
      'generational' => [],
    ];

    $action['separater'] = '';

    foreach ($action['components'] as $component) {
      if (empty($settings['autocomplete_source'][$component])) {
        unset($action['components'][$component]);
      }
      else {
        $sep = (string) $settings['autocomplete_separator'][$component];
        if (strlen($sep) === 0) {
          $sep = ' ';
        }
        for ($i = 0; $i < strlen($sep); $i++) {
          if (strpos($action['separater'], $sep[$i]) === FALSE) {
            $action['separater'] .= $sep[$i];
          }
        }
        $found_source = FALSE;

        foreach ((array) $settings['autocomplete_source'][$component] as $src) {
          if ($src == 'data' && !$field) {
            continue;
          }
          if ($src == 'title' || $src == 'generational') {
            if (!$field || $component != $src) {
              continue;
            }
          }
          $found_source = TRUE;
          $action['source'][$src][] = $component;
        }

        if (!$found_source) {
          unset($action['components'][$component]);
        }
      }
    }

    // @todo: preg_split fails with a notice if $action['separater'] == ' '.
    @$pieces = preg_split('/[' . preg_quote($action['separater']) . ']+/', $string);

    // We should have nice clean parameters to query.
    if (!empty($pieces) && !empty($action['components'])) {
      $test_string = mb_strtolower(array_pop($pieces));
      $base_string = mb_substr($string, 0, mb_strlen($string) - mb_strlen($test_string));

      if ($limit > 0 && count($action['source']['title'])) {
        $options = $this->optionsProvider->getOptions($field, 'title');
        foreach ($options as $key => $option) {
          if (strpos(mb_strtolower($key), $test_string) === 0 || strpos(mb_strtolower($option), $test_string) === 0) {
            $matches[$base_string . $key] = $key;
            $limit--;
          }
        }
      }

      if ($limit > 0 && count($action['source']['generational'])) {
        $options = $this->optionsProvider->getOptions($field, 'generational');
        foreach ($options as $key => $option) {
          if (strpos(mb_strtolower($key), $test_string) === 0 || strpos(mb_strtolower($option), $test_string) === 0) {
            $matches[$base_string . $key] = $key;
            $limit--;
          }
        }
      }
    }

    return $matches;
  }

  /**
   * Helper function to combine values.
   *
   * @param array $values
   *   Array values to combine.
   *
   * @return array
   *   Combined array values.
   */
  public function mapAssoc(array $values) {
    return array_combine($values, $values);
  }

}
