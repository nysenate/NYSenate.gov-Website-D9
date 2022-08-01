<?php

namespace Drupal\eva\Plugin\views\display;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eva\ViewDisplays;

/**
 * The plugin that handles an EVA display in views.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "entity_view",
 *   title = @Translation("EVA"),
 *   admin = @Translation("EVA"),
 *   help = @Translation("Attach a view to an entity"),
 *   theme = "eva_display_entity_view",
 *   uses_menu_links = FALSE,
 *   uses_hook_entity_view = TRUE,
 * )
 */
class Eva extends DisplayPluginBase {

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current path stack service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * EVA utiilities.
   *
   * @var \Drupal\eva\ViewDisplays
   */
  protected $evaViewDisplays;

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   *   TRUE if the display can use attachments, or FALSE otherwise.
   */
  protected $usesAttachments = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $bundleInfo, CurrentPathStack $currentPathStack, ViewDisplays $evaViewDisplays) {
    parent::__construct([], $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundleInfo;
    $this->currentPathStack = $currentPathStack;
    $this->evaViewDisplays = $evaViewDisplays;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('path.current'),
      $container->get('eva.view_displays')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['entity_type']['default'] = '';
    $options['bundles']['default'] = [];
    $options['argument_mode']['default'] = 'id';
    $options['default_argument']['default'] = '';

    $options['title']['default'] = '';
    $options['defaults']['default']['title'] = FALSE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['entity_view'] = [
      'title' => $this->t('Entity content settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    if ($entity_type = $this->getOption('entity_type')) {
      $entity_info = $this->entityTypeManager->getDefinition($entity_type);
      $type_name = $entity_info->get('label');

      $bundle_names = [];
      $bundle_info = $this->bundleInfo->getBundleInfo($entity_type);
      foreach ($this->getOption('bundles') as $bundle) {
        $bundle_names[] = $bundle_info[$bundle]['label'];
      }
    }

    $options['entity_type'] = [
      'category' => 'entity_view',
      'title' => $this->t('Entity type'),
      'value' => empty($type_name) ? $this->t('None') : $type_name,
    ];

    $options['bundles'] = [
      'category' => 'entity_view',
      'title' => $this->t('Bundles'),
      'value' => empty($bundle_names) ? $this->t('All') : implode(', ', $bundle_names),
    ];

    $argument_mode = $this->getOption('argument_mode');
    $options['arguments'] = [
      'category' => 'entity_view',
      'title' => $this->t('Arguments'),
      'value' => empty($argument_mode) ? $this->t('None') : Html::escape($argument_mode),
    ];

    $options['show_title'] = [
      'category' => 'entity_view',
      'title' => $this->t('Show title'),
      'value' => $this->getOption('show_title') ? $this->t('Yes') : $this->t('No'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_info = $this->entityTypeManager->getDefinitions();
    $entity_type = $this->getOption('entity_type');

    switch ($form_state->get('section')) {
      case 'entity_type':
        foreach ($entity_info as $type => $info) {
          // Is this a content/front-facing entity?
          if ($info instanceof ContentEntityType) {
            $entity_names[$type] = $info->get('label');
          }
        }

        $form['#title'] .= $this->t('Entity type');
        $form['entity_type'] = [
          '#type' => 'radios',
          '#required' => TRUE,
          '#validated' => TRUE,
          '#title' => $this->t('Attach this display to the following entity type'),
          '#options' => $entity_names,
          '#default_value' => $this->getOption('entity_type'),
        ];
        break;

      case 'bundles':
        $options = [];
        foreach ($this->bundleInfo->getBundleInfo($entity_type) as $bundle => $info) {
          $options[$bundle] = $info['label'];
        }
        $form['#title'] .= $this->t('Bundles');
        $form['bundles'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Attach this display to the following bundles.  If no bundles are selected, the display will be attached to all.'),
          '#options' => $options,
          '#default_value' => $this->getOption('bundles'),
        ];
        break;

      case 'arguments':
        $form['#title'] .= $this->t('Arguments');
        $default = $this->getOption('argument_mode');
        $options = [
          'None' => $this->t("No special handling"),
          'id' => $this->t("Use the ID of the entity the view is attached to"),
          'token' => $this->t("Use tokens from the entity the view is attached to"),
        ];

        $form['argument_mode'] = [
          '#type' => 'radios',
          '#title' => $this->t("How should this display populate the view's arguments?"),
          '#options' => $options,
          '#default_value' => $default,
        ];

        $form['token'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Token replacement'),
          '#collapsible' => TRUE,
          '#states' => [
            'visible' => [
              ':input[name=argument_mode]' => ['value' => 'token'],
            ],
          ],
        ];

        $form['token']['default_argument'] = [
          '#title' => $this->t('Arguments'),
          '#type' => 'textfield',
          '#default_value' => $this->getOption('default_argument'),
          '#description' => $this->t('You may use token replacement to provide arguments based on the current entity. Separate arguments with "/".'),
        ];

        // Add a token browser.
        if (\Drupal::service('module_handler')->moduleExists('token')) {
          $token_types = [$entity_type => $entity_type];
          $token_mapper = \Drupal::service('token.entity_mapper');
          if (!empty($token_types)) {
            $token_types = array_map(function ($type) use ($token_mapper) {
              return $token_mapper->getTokenTypeForEntityType($type);
            }, (array) $token_types);
          }
          $form['token']['browser'] = [
            '#theme' => 'token_tree_link',
            '#token_types' => $token_types,
            '#global_types' => TRUE,
            '#show_nested' => FALSE,
          ];
        }
        break;

      case 'show_title':
        $form['#title'] .= $this->t('Show title');
        $form['show_title'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show the title of the view above the attached view.'),
          '#default_value' => $this->getOption('show_title'),
        ];
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    switch ($form_state->get('section')) {
      case 'entity_type':
        if (empty($form_state->getValue('entity_type'))) {
          $form_state->setError($form['entity_type'], $this->t('Must select an entity'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = [];
    if (empty($this->getOption('entity_type'))) {
      $errors[] = $this->t('Display "@display" must be attached to an entity.', ['@display' => $this->display['display_title']]);
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function remove() {
    // Clean up display configs before the display disappears.
    $longname = $this->view->storage->get('id') . '_' . $this->display['id'];
    $this->evaViewDisplays->clearDetached($longname);

    parent::remove();
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'entity_type':
        $new_entity = $form_state->getValue('entity_type');
        $old_entity = $this->getOption('entity_type');
        $this->setOption('entity_type', $new_entity);

        if ($new_entity != $old_entity) {
          // Each entity has its own list of bundles and view modes. If there's
          // only one on the new type, we can select it automatically. Otherwise
          // we need to wipe the options and start over.
          $new_bundles_keys = $this->bundleInfo->getBundleInfo($new_entity);
          $new_bundles = [];
          if (count($new_bundles_keys) == 1) {
            $new_bundles[] = $new_bundles_keys[0];
          }
          $this->setOption('bundles', $new_bundles);
        }
        break;

      case 'bundles':
        $this->setOption('bundles', array_values(array_filter($form_state->getValue('bundles'))));
        break;

      case 'arguments':
        $this->setOption('argument_mode', $form_state->getValue('argument_mode'));
        if ($form_state->getValue('argument_mode') == 'token') {
          $this->setOption('default_argument', $form_state->getValue('default_argument'));
        }
        else {
          $this->setOption('default_argument', NULL);
        }
        break;

      case 'show_title':
        $this->setOption('show_title', $form_state->getValue('show_title'));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    if (isset($this->view->current_entity)) {
      /** @var \Drupal\Core\Entity\EntityInterface $current_entity */
      $current_entity = $this->view->current_entity;

      /** @var \Drupal\Core\Url $uri */
      if ($current_entity->hasLinkTemplate('canonical')) {
        $uri = $current_entity->toUrl('canonical');
        if ($uri) {
          $uri->setAbsolute(TRUE);
          return $uri->toUriString();
        }
      }
    }

    return parent::getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    if (!isset($this->view->override_path)) {
      $this->view->override_path = $this->currentPathStack->getPath();
    }

    $element = $this->view->render();
    if (!empty($this->view->result) || $this->getOption('empty') || !empty($this->view->style_plugin->definition['even empty'])) {
      return $element;
    }

    return [];
  }

}
