<?php

namespace Drupal\semanticviews\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in a slideshow.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "semanticviews_style",
 *   title = @Translation("Semantic Views Style"),
 *   help = @Translation("Displays rows one after another."),
 *   theme = "semanticviews_style",
 *   display_types = {"normal"}
 * )
 */
class SemanticViewsStyle extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Group option definition.
    $options['group'] = [
      'contains' => [
        'element_type' => ['default' => 'h3'],
        'attributes' => ['default' => 'class|title'],
      ],
    ];

    // List option definition.
    $options['list'] = [
      'contains' => [
        'element_type' => ['default' => ''],
        'attributes' => ['default' => ''],
      ],
    ];

    // Row option definition.
    $options['row'] = [
      'contains' => [
        'attributes' => ['default' => "class|"],
        'element_type' => ['default' => 'div'],
        'first_class' => ['default' => 'first'],
        'last_class' => ['default' => 'last'],
        'last_every_nth' => ['default' => '0'],
        'striping_classes' => ['default' => 'odd even'],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Group options.
    $form['group'] = [
      '#type' => 'fieldset',
      '#title' => t('Grouping title'),
      '#description' => t('If using groups, the view will insert the grouping&rsquo;s title field.'),
      '#attributes' => [
        'class' => ['clearfix'],
      ],
    ];
    $form['group']['element_type'] = [
      '#prefix' => '<div class="views-left-30">',
      '#suffix' => '</div>',
      '#title' => t('Element'),
      '#type' => 'textfield',
      '#size' => '10',
      '#default_value' => $this->options['group']['element_type'],
    ];
    $form['group']['attributes'] = [
      '#prefix' => '<div class="views-right-70">',
      '#suffix' => '</div>',
      '#title' => t('Element attributes'),
      '#type' => 'textarea',
      '#rows' => '5',
      '#default_value' => $this->options['group']['attributes'],
      '#description' => t('Enter one value per line, in the format attribute|value.'),
    ];

    // List options.
    $form['list'] = [
      '#type' => 'fieldset',
      '#title' => t('List'),
      '#description' => t('If the output should be a HTML list, select the element and class attribute. The row element should also be set to %li.', ['%li' => 'li']),
      '#attributes' => [
        'class' => ['clearfix'],
      ],
    ];
    $form['list']['element_type'] = [
      '#prefix' => '<div class="views-left-30">',
      '#suffix' => '</div>',
      '#type' => 'radios',
      '#title' => t('List type'),
      '#options' => [
        '' => t('None'),
        'ul' => t('Unordered list'),
        'ol' => t('Ordered list'),
        'dl' => t('Definition list'),
        'div' => t('Division <div>'),
      ],
      '#default_value' => $this->options['list']['element_type'],
    ];
    $form['list']['attributes'] = [
      '#prefix' => '<div class="views-right-70">',
      '#suffix' => '</div>',
      '#title' => t('Element attributes'),
      '#type' => 'textarea',
      '#rows' => '5',
      '#default_value' => $this->options['list']['attributes'],
      '#description' => t('Enter one value per line, in the format attribute|value.'),
    ];

    // Row options.
    $form['row'] = [
      '#type' => 'fieldset',
      '#title' => t('Row'),
      '#attributes' => [
        'class' => ['clearfix'],
      ],
    ];
    $form['row']['element_type'] = [
      '#prefix' => '<div class="clearfix"><div class="views-left-30">',
      '#suffix' => '</div>',
      '#title' => t('Element'),
      '#type' => 'textfield',
      '#size' => '10',
      '#default_value' => $this->options['row']['element_type'],
    ];
    $form['row']['attributes'] = [
      '#prefix' => '<div class="views-right-70">',
      '#suffix' => '</div>',
      '#title' => t('Element attributes'),
      '#type' => 'textarea',
      '#rows' => '5',
      '#default_value' => $this->options['row']['attributes'],
      '#description' => t('Enter one value per line, in the format attribute|value. Insert %row-enumeration where you want row enumeration with start 0.', ['%row-enumeration' => '{{ row_index }}']),
    ];

    // First and last class options.
    $form['row']['first_last'] = [
      '#type' => 'fieldset',
      '#title' => t('First and last classes'),
      '#parents' => ['style_options', 'row'],
      '#description' => t('If the %last_every_nth option is empty or zero, the %first_class and %last_class are added once to only the first and last rows in the pager set. If this option is greater than 1, these classes are added to every n<sup>th</sup> row, which may be useful for grid layouts where the initial and final unit&rsquo;s lateral margins must be 0.', [
        '%last_every_nth' => 'FIRST/LAST every nth',
        '%first_class' => 'FIRST class attribute',
        '%last_class' => 'LAST class attribute',
      ]),
      '#attributes' => [
        'class' => ['clearfix'],
      ],
    ];
    $form['row']['first_last']['last_every_nth'] = [
      '#type' => 'textfield',
      '#size' => '10',
      '#title' => t('FIRST/LAST every n<sup>th</sup>'),
      '#default_value' => $this->options['row']['last_every_nth'],
    ];
    $form['row']['first_last']['first_class'] = [
      '#prefix' => '<div class="views-left-50">',
      '#suffix' => '</div>',
      '#title' => t('FIRST class attribute'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['row']['first_class'],
    ];
    $form['row']['first_last']['last_class'] = [
      '#prefix' => '<div class="views-right-50">',
      '#suffix' => '</div>',
      '#title' => t('LAST class attribute'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['row']['last_class'],
    ];

    // Striping class options.
    $form['row']['striping_classes'] = [
      '#title' => t('Striping class attributes'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['row']['striping_classes'],
      '#description' => t('One striping class attribute is applied to each row. Separate multiple attributes with a space.'),
    ];

  }

  /**
   * Take a value and apply token replacement logic to it.
   */
  public function tokenizeValue($value, $row_index) {
    if (!isset($this->view->build_info['substitutions']) || !is_array($this->view->build_info['substitutions'])) {
      // Set an array.
      $this->view->build_info['substitutions'] = [];
    }

    // Add row_index token.
    $this->view->build_info['substitutions']['{{ row_index }}'] = $row_index;

    return parent::tokenizeValue($value, $row_index);
  }

}
