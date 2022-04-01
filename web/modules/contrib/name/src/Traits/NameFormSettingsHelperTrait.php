<?php

namespace Drupal\name\Traits;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\name\NameOptionsProvider;

/**
 * Name settings trait.
 *
 * Shared methods to assist handling the field element setting forms.
 */
trait NameFormSettingsHelperTrait {

  /**
   * Themes up the field settings into a table.
   */
  public function fieldSettingsFormPreRender($form) {
    $components = _name_translations();
    // This provides the base layout for the fields, for both field and form
    // display.
    $excluded_components = [];
    if (!empty($form['#excluded_components'])) {
      $excluded_components = $form['#excluded_components'];
    }

    $form = [
      'top' => [],
      'hidden' => ['#access' => FALSE],
      'name_settings' => [
        '#type' => 'container',
        'table' => [
          '#prefix' => '<table>',
          '#suffix' => '</table>',
          '#weight' => -2,
          'thead' => [
            '#prefix' => '<thead><tr><th>' . t('Field') . '</th>',
            '#suffix' => '</tr></thead>',
            '#weight' => -3,
          ],
          'tbody' => [
            '#prefix' => '<tbody>',
            '#suffix' => '</tbody>',
            '#weight' => -2,
          ],
        ],
      ] + (isset($form['name_settings']) ? $form['name_settings'] : []),
    ] + $form;
    foreach ($components as $key => $title) {
      if (!empty($excluded_components[$key])) {
        continue;
      }
      $form['name_settings']['table']['thead'][$key] = [
        '#markup' => $title,
        '#prefix' => '<th>',
        '#suffix' => '</th>',
      ];
    }

    $help_footer_notes = [];
    $footer_notes_counter = 0;
    foreach (Element::children($form) as $child) {
      if ($child == 'name_settings' || $child == 'top' || $child == 'hidden') {
        continue;
      }

      if (!empty($form[$child]['#table_group'])) {
        if ($form[$child]['#table_group'] == 'none') {
          continue;
        }
        if ($form[$child]['#table_group'] == 'above') {
          $form['top'][$child] = $form[$child];
          unset($form[$child]);
        }
        else {
          $target_key = $form[$child]['#table_group'];
          $form['name_settings']['table']['tbody'][$target_key]['elements'][$child] = $form[$child];
          unset($form[$child]);
        }
      }
      elseif (!empty($form[$child]['#indent_row'])) {
        $form['name_settings']['table']['tbody'][$child] = [
          '#prefix' => '<tr><td>&nbsp;</td>',
          '#suffix' => '</tr>',
          'elements' => [
            '#prefix' => '<td colspan="' . (6 - count($excluded_components)) . '">',
            '#suffix' => '</td>',
          ] + $form[$child],
        ];
        unset($form[$child]);
      }
      else {
        $footnote_sup = '';
        if (!empty($form[$child]['#description'])) {
          $footnote_sup = $this->t('<sup>@number</sup>', ['@number' => ++$footer_notes_counter]);
          $help_footer_notes[] = $form[$child]['#description'];
          unset($form[$child]['#description']);
        }
        if (isset($form[$child]['#title'])) {
          $form['name_settings']['table']['tbody'][$child] = [
            '#prefix' => '<tr><th>' . $form[$child]['#title'] . $footnote_sup . '</th>',
            '#suffix' => '</tr>',
          ];
        }
        foreach (array_keys($components) as $weight => $key) {
          if (!empty($excluded_components[$key]) && isset($form[$child][$key])) {
            $form[$child][$key]['#access'] = FALSE;
            $form['hidden'][$child][$key] = $form[$child][$key];
          }
          else {
            if (isset($form[$child][$key])) {
              $form[$child][$key]['#attributes']['title'] = $form[$child][$key]['#title'];
              if (isset($form[$child][$key]['#type'])) {
                switch ($form[$child][$key]['#type']) {
                  case 'checkbox':
                    $form[$child][$key]['#title_display'] = 'invisible';
                    break;
                }
              }
              $form['name_settings']['table']['tbody'][$child][$key] = [
                '#prefix' => '<td>',
                '#suffix' => '</td>',
                '#weight' => $weight,
              ] + $form[$child][$key];
              // Elements with components are dependant on the component
              // checkbox being selected.
              if ($child != 'components') {
                $form['name_settings']['table']['tbody'][$child][$key]['#states'] = [
                  'visible' => [
                    ':input[name$="[components][' . $key . ']"]' => [
                      'checked' => TRUE,
                    ],
                  ],
                ];
              }
            }
            else {
              $form['name_settings']['table']['tbody'][$child][$key] = [
                '#prefix' => '<td>',
                '#suffix' => '</td>',
                '#markup' => "&nbsp;",
                '#weight' => $weight,
              ];
            }
          }
        }
        unset($form[$child]);
      }
    }
    if ($help_footer_notes) {
      $form['name_settings']['footnotes'] = [
        '#type' => 'details',
        '#title' => t('Footnotes'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#parents' => [],
        '#weight' => -1,
        'help_items' => [
          '#theme' => 'item_list',
          '#list_type' => 'ol',
          '#items' => $help_footer_notes,
        ],
      ];
    }
    $form['#sorted'] = FALSE;

    return $form;
  }

  /**
   * Helper function to validate minimum components.
   *
   * @param array $element
   *   Element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed $values
   *   Values to check.
   * @param int $max_length
   *   The max length.
   */
  protected static function validateOptions($element, FormStateInterface $form_state, $values, $max_length) {
    $label = $element['#title'];

    $long_options = [];
    $valid_options = [];
    $default_options = [];
    foreach ($values as $value) {
      $value = trim($value);
      // Blank option - anything goes!
      if (strpos($value, '--') === 0) {
        $default_options[] = $value;
      }
      // Simple checks on the taxonomy includes.
      elseif (preg_match(NameOptionsProvider::vocabularyRegExp, $value, $matches)) {
        if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
          $form_state->setError($element, t("The taxonomy module must be enabled before using the '%tag' tag in %label.", [
            '%tag' => $matches[0],
            '%label' => $label,
          ]));
        }
        elseif ($value !== $matches[0]) {
          $form_state->setError($element, t("The '%tag' tag in %label should be on a line by itself.", [
            '%tag' => $matches[0],
            '%label' => $label,
          ]));
        }
        else {
          $vocabulary = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($matches[1]);
          if ($vocabulary) {
            $valid_options[] = $value;
          }
          else {
            $form_state->setError($element, t("The vocabulary '%tag' in %label could not be found.", [
              '%tag' => $matches[1],
              '%label' => $label,
            ]));
          }
        }
      }
      elseif (mb_strlen($value) > $max_length) {
        $long_options[] = $value;
      }
      elseif (!empty($value)) {
        $valid_options[] = $value;
      }
    }
    if (count($long_options)) {
      $form_state->setError($element, t('The following options exceed the maximum allowed %label length: %options', [
        '%options' => implode(', ', $long_options),
        '%label' => $label,
      ]));
    }
    elseif (empty($valid_options)) {
      $form_state->setError($element, t('%label are required.', [
        '%label' => $label,
      ]));
    }
    elseif (count($default_options) > 1) {
      $form_state->setError($element, t('%label can only have one blank value assigned to it.', [
        '%label' => $label,
      ]));
    }

    $form_state->setValueForElement($element, array_merge($default_options, $valid_options));
  }

  /**
   * Helper function to get the allowed values.
   *
   * @param string $string
   *   The string to parse.
   *
   * @return array
   *   The parsed values.
   */
  protected static function extractAllowedValues($string) {
    return array_filter(array_map('trim', explode("\n", $string)));
  }

}
