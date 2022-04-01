<?php

namespace Drupal\simple_sitemap_views\Plugin\views\display_extender;

use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\simple_sitemap\SimplesitemapManager;
use Drupal\simple_sitemap\Form\FormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * Simple XML Sitemap display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "simple_sitemap_display_extender",
 *   title = @Translation("Simple XML Sitemap"),
 *   help = @Translation("Simple XML Sitemap settings for this view."),
 *   no_ui = FALSE
 * )
 */
class SimpleSitemapDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * Simple XML Sitemap form helper.
   *
   * @var \Drupal\simple_sitemap\Form\FormHelper
   */
  protected $formHelper;

  /**
   * Simple XML Sitemap manager.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $sitemapManager;

  /**
   * The sitemap variants.
   *
   * @var array
   */
  protected $variants = [];

  /**
   * Constructs the plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   *   Simple XML Sitemap form helper.
   * @param \Drupal\simple_sitemap\SimplesitemapManager $sitemap_manager
   *   Simple XML Sitemap manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormHelper $form_helper, SimplesitemapManager $sitemap_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formHelper = $form_helper;
    $this->sitemapManager = $sitemap_manager;
    $this->variants = $sitemap_manager->getSitemapVariants(NULL, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.form_helper'),
      $container->get('simple_sitemap.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    if (!$this->hasSitemapSettings()) {
      $this->options = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['variants'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($this->hasSitemapSettings() && $form_state->get('section') == 'simple_sitemap') {
      $has_required_arguments = $this->hasRequiredArguments();
      $arguments_options = $this->getArgumentsOptions();

      $form['variants'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];

      foreach ($this->variants as $variant => $definition) {
        $settings = $this->getSitemapSettings($variant);
        $variant_form = &$form['variants'][$variant];

        $variant_form = [
          '#type' => 'details',
          '#title' => '<em>' . $definition['label'] . '</em>',
          '#open' => (bool) $settings['index'],
        ];

        $variant_form['index'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Index this display in variant <em>@variant_label</em>', [
            '@variant_label' => $definition['label'],
          ]),
          '#default_value' => $settings['index'],
        ];

        $states = [
          'visible' => [
            ':input[name="variants[' . $variant . '][index]"]' => ['checked' => TRUE],
          ],
        ];

        // The sitemap priority.
        $variant_form['priority'] = [
          '#type' => 'select',
          '#title' => $this->t('Priority'),
          '#description' => $this->t('The priority this display will have in the eyes of search engine bots.'),
          '#default_value' => $settings['priority'],
          '#options' => $this->formHelper->getPrioritySelectValues(),
          '#states' => $states,
        ];

        // The sitemap change frequency.
        $variant_form['changefreq'] = [
          '#type' => 'select',
          '#title' => $this->t('Change frequency'),
          '#description' => $this->t('The frequency with which this display changes. Search engine bots may take this as an indication of how often to index it.'),
          '#default_value' => $settings['changefreq'],
          '#options' => $this->formHelper->getChangefreqSelectValues(),
          '#states' => $states,
        ];

        // Arguments to index.
        $variant_form['arguments'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Indexed arguments'),
          '#options' => $arguments_options,
          '#default_value' => $settings['arguments'],
          '#attributes' => ['class' => ['indexed-arguments']],
          '#access' => !empty($arguments_options),
          '#states' => $states,
        ];

        // Required arguments are always indexed.
        foreach ($this->getRequiredArguments() as $argument_id) {
          $variant_form['arguments'][$argument_id]['#disabled'] = TRUE;
        }

        // Max links with arguments.
        $variant_form['max_links'] = [
          '#type' => 'number',
          '#title' => $this->t('Maximum display variations'),
          '#description' => $this->t('The maximum number of link variations to be indexed for this display. If left blank, each argument will create link variations for this display. Use with caution, as a large number of argument values​can significantly increase the number of sitemap links.'),
          '#default_value' => $settings['max_links'],
          '#min' => 1,
          '#access' => !empty($arguments_options) || $has_required_arguments,
          '#states' => $states,
        ];
      }

      $form['#title'] .= $this->t('Simple XML Sitemap settings for this display');
      $form['#attached']['library'][] = 'simple_sitemap_views/viewsUi';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if ($this->hasSitemapSettings() && $form_state->get('section') == 'simple_sitemap') {
      $required_arguments = $this->getRequiredArguments();

      foreach (array_keys($this->variants) as $variant) {
        $arguments = &$form_state->getValue(['variants', $variant, 'arguments'], []);
        $arguments = array_merge($arguments, $required_arguments);
        $errors = $this->validateIndexedArguments($arguments);

        foreach ($errors as $message) {
          $form_state->setError($form['variants'][$variant]['arguments'], $message);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($this->hasSitemapSettings() && $form_state->get('section') == 'simple_sitemap') {
      $variants = $form_state->getValue('variants');
      $this->options['variants'] = [];

      // Save settings for each variant.
      foreach (array_keys($this->variants) as $variant) {
        $settings = $variants[$variant] + $this->getSitemapSettings($variant);

        if ($settings['index']) {
          $settings['arguments'] = array_filter($settings['arguments']);
          $this->options['variants'][$variant] = $settings;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = [parent::validate()];

    // Validate the argument options relative to the
    // current state of the view argument handlers.
    if ($this->hasSitemapSettings()) {
      foreach (array_keys($this->variants) as $variant) {
        $settings = $this->getSitemapSettings($variant);
        $errors[] = $this->validateIndexedArguments($settings['arguments']);
      }
    }

    return array_merge([], ...$errors);
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    if ($this->hasSitemapSettings()) {
      $categories['simple_sitemap'] = [
        'title' => $this->t('Simple XML Sitemap'),
        'column' => 'second',
      ];

      $included_variants = [];
      foreach (array_keys($this->variants) as $variant) {
        $settings = $this->getSitemapSettings($variant);

        if ($settings['index']) {
          $included_variants[] = $variant;
        }
      }

      $options['simple_sitemap'] = [
        'title' => NULL,
        'category' => 'simple_sitemap',
        'value' => $included_variants ? $this->t('Included in sitemap variants: @variants', [
          '@variants' => implode(', ', $included_variants),
        ]) : $this->t('Excluded from all sitemap variants'),
      ];
    }
  }

  /**
   * Gets the sitemap settings.
   *
   * @param string $variant
   *   The name of the sitemap variant.
   *
   * @return array
   *   The sitemap settings.
   */
  public function getSitemapSettings($variant) {
    $settings = [
      'index' => 0,
      'priority' => 0.5,
      'changefreq' => '',
      'arguments' => [],
      'max_links' => 100,
    ];

    if (isset($this->options['variants'][$variant])) {
      $settings = $this->options['variants'][$variant] + $settings;
    }

    if (empty($this->displayHandler->getHandlers('argument'))) {
      $settings['arguments'] = [];
    }
    else {
      $required_arguments = $this->getRequiredArguments();
      $settings['arguments'] = array_merge($settings['arguments'], $required_arguments);
    }
    return $settings;
  }

  /**
   * Identify whether or not the current display has sitemap settings.
   *
   * @return bool
   *   Has sitemap settings (TRUE) or not (FALSE).
   */
  public function hasSitemapSettings() {
    return $this->displayHandler instanceof DisplayRouterInterface;
  }

  /**
   * Gets required view arguments (presented in the path).
   *
   * @return array
   *   View arguments IDs.
   */
  public function getRequiredArguments() {
    $arguments = $this->displayHandler->getHandlers('argument');

    if (!empty($arguments)) {
      $bits = explode('/', $this->displayHandler->getPath());
      $arg_counter = 0;

      foreach ($bits as $bit) {
        if ($bit == '%' || strpos($bit, '%') === 0) {
          $arg_counter++;
        }
      }

      if ($arg_counter > 0) {
        $arguments = array_slice(array_keys($arguments), 0, $arg_counter);
        return array_combine($arguments, $arguments);
      }
    }
    return [];
  }

  /**
   * Determines if the view path contains required arguments.
   *
   * @return bool
   *   TRUE if the path contains required arguments, FALSE if not.
   */
  public function hasRequiredArguments() {
    $bits = explode('/', $this->displayHandler->getPath());

    foreach ($bits as $bit) {
      if ($bit == '%' || strpos($bit, '%') === 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns available view arguments options.
   *
   * @return array
   *   View arguments labels keyed by argument ID.
   */
  protected function getArgumentsOptions() {
    $arguments = $this->displayHandler->getHandlers('argument');
    $arguments_options = [];

    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($arguments as $id => $argument) {
      $arguments_options[$id] = $argument->adminLabel();
    }
    return $arguments_options;
  }

  /**
   * Validate indexed arguments.
   *
   * @param array $indexed_arguments
   *   Indexed arguments array.
   *
   * @return array
   *   An array of error strings. This will be empty if there are no validation
   *   errors.
   */
  protected function validateIndexedArguments(array $indexed_arguments) {
    $arguments = $this->displayHandler->getHandlers('argument');
    $arguments = array_fill_keys(array_keys($arguments), 0);
    $arguments = array_merge($arguments, $indexed_arguments);
    reset($arguments);

    $errors = [];
    while (($argument = current($arguments)) !== FALSE) {
      $next_argument = next($arguments);
      if (empty($argument) && !empty($next_argument)) {
        $errors[] = $this->t('To enable indexing of an argument, you must enable indexing of all previous arguments.');
        break;
      }
    }
    return $errors;
  }

}
