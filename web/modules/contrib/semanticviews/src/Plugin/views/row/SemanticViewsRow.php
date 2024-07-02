<?php

namespace Drupal\semanticviews\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * The layout_plugin_views 'fields' row plugin.
 *
 * This displays fields in a panel.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "semanticviews_row",
 *   title = @Translation("Semantic Views Row"),
 *   help = @Translation("Displays the fields with an optional template."),
 *   theme = "semanticviews_row",
 *   display_types = {"normal"}
 * )
 */
class SemanticViewsRow extends RowPluginBase {

  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['inline'] = ['default' => []];
    $options['separator'] = ['default' => ''];
    $options['hide_empty'] = ['default' => FALSE];
    $options['default_field_elements'] = ['default' => TRUE];
    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['semantic_html'] = [
      '#tree' => TRUE,
    ];

    $form['semantic_html']['help'] = [
      '#markup' => t('These settings will override each Fields "Style Settings".'),
    ];

    foreach ($this->displayHandler->getHandlers('field') as $field => $handler) {
      if (!$handler->exclude) {
        $default_value = (isset($this->options['semantic_html'][$field]) && is_array($this->options['semantic_html'][$field])) ? $this->options['semantic_html'][$field] : [
          'element_type' => 'div',
          'attributes' => '',
          'label_element_type' => 'label',
          'label_attributes' => '',
        ];

        $form['semantic_html'][$field] = [
          '#title' => $handler->definition['title'],
          '#type' => 'fieldset',
          '#attributes' => [
            'class' => ['clearfix'],
          ],
        ];
        $form['semantic_html'][$field]['element_type'] = [
          '#prefix' => '<div class="views-left-30">',
          '#suffix' => '</div>',
          '#title' => 'Element',
          '#type' => 'textfield',
          '#size' => '10',
          '#default_value' => $default_value['element_type'],
        ];
        $form['semantic_html'][$field]['attributes'] = [
          '#prefix' => '<div class="views-right-70">',
          '#suffix' => '</div>',
          '#title' => 'Element attributes',
          '#type' => 'textarea',
          '#rows' => '5',
          '#default_value' => $default_value['attributes'],
          '#description' => t('Enter one value per line, in the format attribute|value.'),
        ];

        if (!empty($handler->label())) {
          $form['semantic_html'][$field]['label_element_type'] = [
            '#prefix' => '<div class="views-left-30">',
            '#suffix' => '</div>',
            '#title' => 'Label element',
            '#type' => 'textfield',
            '#size' => '10',
            '#default_value' => $default_value['label_element_type'],
          ];
          $form['semantic_html'][$field]['label_attributes'] = [
            '#prefix' => '<div class="views-right-70">',
            '#suffix' => '</div>',
            '#title' => 'Label attributes',
            '#type' => 'textarea',
            '#rows' => '5',
            '#default_value' => $default_value['label_attributes'],
            '#description' => t('Enter one value per line, in the format attribute|value.'),
          ];
        }
      }

    }
    $form['skip_blank'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->options['skip_blank'],
      '#title' => t('Skip empty fields'),
      '#description' => t('Do not output anything when a field has no content. This has the same outcome as enabling the <em>Hide if empty</em> option for every field in this display.'),
    ];
  }

}
