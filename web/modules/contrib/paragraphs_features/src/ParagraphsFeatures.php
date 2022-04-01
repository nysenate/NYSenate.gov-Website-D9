<?php

namespace Drupal\paragraphs_features;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\paragraphs_features\Ajax\ScrollToElementCommand;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Paragraphs features class.
 */
class ParagraphsFeatures {

  /**
   * List of available paragraphs features.
   *
   * @var array
   */
  public static $availableFeatures = [
    'add_in_between',
    'delete_confirmation',
    'split_text',
  ];

  /**
   * Getting paragraphs widget wrapper ID.
   *
   * Logic is copied from paragraphs module.
   *
   * @param array $parents
   *   List of parents for widget.
   * @param string $field_name
   *   Widget field name.
   *
   * @return string
   *   Returns widget wrapper ID.
   */
  public static function getWrapperId(array $parents, $field_name) {
    return Html::getId(implode('-', array_merge($parents, [$field_name])) . '-add-more-wrapper');
  }

  /**
   * Register features for paragraphs field widget.
   *
   * @param array $elements
   *   Render array for the field widget.
   * @param \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget $widget
   *   Field widget object.
   * @param string $fieldWrapperId
   *   Field Wrapper ID, usually provided by ::getWrapperId().
   */
  public static function registerFormWidgetFeatures(array &$elements, ParagraphsWidget $widget, $fieldWrapperId) {
    foreach (static::$availableFeatures as $feature) {
      if ($widget->getThirdPartySetting('paragraphs_features', $feature)) {
        $elements['add_more']['#attached']['library'][] = 'paragraphs_features/drupal.paragraphs_features.' . $feature;
        $elements['add_more']['#attached']['drupalSettings']['paragraphs_features'][$feature][$fieldWrapperId] = ['wrapperId' => $fieldWrapperId];
      }
      if ($feature === 'add_in_between') {
        $elements['add_more']['#attached']['drupalSettings']['paragraphs_features'][$feature][$fieldWrapperId]['linkCount'] =
          $widget->getThirdPartySetting('paragraphs_features', 'add_in_between_link_count');
      }
      // Set module path for split_text feature.
      $elements['add_more']['#attached']['drupalSettings']['paragraphs_features']['_path'] = drupal_get_path('module', 'paragraphs_features');
    }

    $elements['add_more']['#attached']['library'][] = 'paragraphs_features/drupal.paragraphs_features.scroll_to_element';
    foreach (Element::children($elements['add_more']) as $button) {
      $elements['add_more'][$button]['#ajax']['callback'] = [
        static::class, 'addMoreAjax',
      ];
    }
    // This feature is not part of of the foreach above, since it is not a
    // javascript feature, it is a direct modification of the form. If the
    // feature is not set, it defaults back to paragraphs behavior.
    if (!empty($elements['header_actions']['dropdown_actions']['dragdrop_mode'])) {
      $elements['header_actions']['dropdown_actions']['dragdrop_mode']['#access'] = (bool) $widget->getThirdPartySetting('paragraphs_features', 'show_drag_and_drop', TRUE);
    }
  }

  /**
   * Adds a scroll event to the ajax response.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response with the paragraph to add.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $element = ParagraphsWidget::addMoreAjax($form, $form_state);

    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $element));
    $response->addCommand(new ScrollToElementCommand($element[$element['#max_delta']]['#attributes']['data-drupal-selector'], $element['#attributes']['data-drupal-selector']));
    return $response;
  }

  /**
   * Get 3rd party setting form for paragraphs features.
   *
   * @param \Drupal\Core\Field\WidgetInterface $plugin
   *   Widget plugin.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   Returns 3rd party form elements.
   */
  public static function getThirdPartyForm(WidgetInterface $plugin, $field_name) {
    $elements = [];

    $elements['delete_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable confirmation on paragraphs remove'),
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_features', 'delete_confirmation'),
      '#attributes' => ['class' => ['paragraphs-features__delete-confirmation__option']],
    ];

    // Define rule for enabling/disabling options that depend on modal add mode.
    $modal_related_options_rule = [
      ':input[name="fields[' . $field_name . '][settings_edit_form][settings][add_mode]"]' => [
        'value' => 'modal',
      ],
    ];

    $elements['add_in_between'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable add in between buttons'),
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_features', 'add_in_between'),
      '#attributes' => ['class' => ['paragraphs-features__add-in-between__option']],
      '#states' => [
        'enabled' => $modal_related_options_rule,
        'visible' => $modal_related_options_rule,
      ],
    ];

    $elements['add_in_between_link_count'] = [
      '#type' => 'number',
      '#title' => t('Number of add in between links', [], ['context' => 'Paragraphs Editor Enhancements']),
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_features', 'add_in_between_link_count', 3),
      '#min' => 0,
      '#attributes' => ['class' => ['paragraphs-features__add-in-between__option']],
      '#states' => [
        'enabled' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][third_party_settings][paragraphs_features][add_in_between]"]' => [
            'checked' => TRUE,
          ],
        ],
        'visible' => $modal_related_options_rule,
      ],
      '#description' => t('Set the number of buttons available to directly add a paragraph.'),
    ];

    $elements['split_text'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable split text for text paragraphs'),
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_features', 'split_text'),
      '#attributes' => ['class' => ['paragraphs-features__split-text__option']],
      '#states' => [
        'enabled' => $modal_related_options_rule,
        'visible' => $modal_related_options_rule,
      ],
    ];

    // Only show the drag & drop feature if we can find the sortable library.
    $library_discovery = \Drupal::service('library.discovery');
    $library = $library_discovery->getLibraryByName('paragraphs', 'paragraphs-dragdrop');

    $elements['show_drag_and_drop'] = [
      '#type' => 'checkbox',
      '#title' => t('Show drag & drop button'),
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_features', 'show_drag_and_drop', TRUE),
      '#access' => !empty($library),
    ];

    return $elements;
  }

}
