<?php

namespace Drupal\node_revision_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node_revision_delete\NodeRevisionDeleteBatchInterface;

/**
 * Provide a settings form for default node revision delete settings.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The node revision delete plugin manager.
   *
   * @var \Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager
   */
  protected NodeRevisionDeletePluginManager $pluginManager;

  /**
   * The batch helper service.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteBatchInterface
   */
  protected NodeRevisionDeleteBatchInterface $nodeRevisionDeleteBatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static($container->get('config.factory'));
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pluginManager = $container->get('plugin.manager.node_revision_delete');
    $instance->nodeRevisionDeleteBatch = $container->get('node_revision_delete.batch');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'node_revision_delete.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'node_revision_delete_admin_settings';
  }

  /**
   * Retrieve an overview of settings for this content type.
   *
   * @param string $node_type_id
   *   The node type id.
   *
   * @return string
   *   The text.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSettingsSummary(string $node_type_id): string {
    $settings = $this->pluginManager->getSettingsNodeType($node_type_id);
    // @todo for 'overridden' node types, we could also show a short
    //   summary of the settings. This should be a method on each plugin.
    //   However, we want to be careful not to clutter the screen.
    return 'overridden' === $settings['status'] ? $this->t('Overridden') : $this->t('Default');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Table header.
    $header = [
      $this->t('Content type'),
      [
        'data' => $this->t('Machine name'),
        // Hide the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Settings'),
        // Hide the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      $this->t('Operations'),
    ];

    // Table rows.
    $rows = [];
    // Looking for all the content types.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    // Return to the same page after save the content type.
    $destination = Url::fromRoute('node_revision_delete.admin_settings')->toString();
    $destination_options = [
      'query' => ['destination' => $destination],
      'fragment' => 'edit-workflow',
    ];

    foreach ($content_types as $content_type) {
      // Getting the content type machine name.
      $content_type_machine_name = $content_type->id();
      $route_parameters = ['node_type' => $content_type_machine_name];

      $settings = $this->getSettingsSummary($content_type_machine_name);

      // Operations dropbutton.
      $dropbutton = [
        '#type' => 'dropbutton',
        '#links' => [
          // Action to edit the content type.
          'edit' => [
            'title' => $this->t('Configure'),
            'url' => Url::fromRoute('entity.node_type.edit_form', $route_parameters, $destination_options),
          ],
        ],
      ];

      // Setting the row values.
      $rows[] = [
        $content_type->label(),
        $content_type_machine_name,
        $settings,
        [
          'data' => $dropbutton,
        ],
      ];
    }

    // Sort the rows by content type name.
    usort($rows, function ($a, $b) {
      return ($a[0] <=> $b[0]);
    });

    // Table with current configuration.
    $form['current_configuration'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];

    // Table with current configuration.
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Below are the defaults for each plugin, which will be applied to all node types above which do not have overridden settings.'),
    ];

    // Add default configurations for plugins.
    $plugin_definitions = $this->pluginManager->getDefinitions();
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $settings = $this->pluginManager->getDefaultPluginSettings($plugin_id);
      $plugin = $this->pluginManager->getPlugin($plugin_id);
      $form[$plugin_id] = [
        '#type' => 'details',
        '#title' => $plugin_definition['label'],
        '#open' => TRUE,
        // We need the #tree key for subforms to work.
        // @see https://www.drupal.org/project/drupal/issues/3053890
        '#tree' => TRUE,
      ];
      $form[$plugin_id]['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $settings['status'] ?? 0,
      ];
      $form[$plugin_id]['settings'] = [
        '#type' => 'fieldgroup',
        '#states' => [
          'visible' => [
            ':input[name="' . $plugin_id . '[status]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $plugin_form_state = SubformState::createForSubform($form[$plugin_id]['settings'], $form, $form_state);
      $form[$plugin_id]['settings'] = $plugin->buildConfigurationForm($form[$plugin_id]['settings'], $plugin_form_state);
    }

    $form['queue_nodes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Queue all content to delete revisions in the background.'),
    ];

    $config = $this->config('node_revision_delete.settings');

    $form['disable_automatic_queueing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable automatic queueing'),
      '#description' => $this->t('If checked, nodes will not be automatically added to the queue for processing when a new revision is added. To add nodes to the queue, use the Drush command "drush node-revision-delete:queue".'),
      '#default_value' => $config->get('disable_automatic_queueing') ?? FALSE,
    ];

    $form['deletion_log'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Verbose Logging'),
      '#collapsible' => TRUE,
    ];

    $form['deletion_log']['verbose_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate a node deletion log.'),
      '#description' => $this->t('Generates a log of deleted nodes.'),
      '#default_value' => $config->get('verbose_log'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('node_revision_delete.settings');
    $plugin_definitions = $this->pluginManager->getDefinitions();
    $defaults = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $plugin = $this->pluginManager->getPlugin($plugin_id);
      if ($plugin instanceof PluginFormInterface) {
        $sub_form_state = SubformState::createForSubform($form[$plugin_id], $form, $form_state);
        $defaults[$plugin_id] = $sub_form_state->getValues();
      }
    }
    $config->set('defaults', $defaults);
    $config->set('disable_automatic_queueing', $form_state->getValue('disable_automatic_queueing'));
    $config->set('verbose_log', $form_state->getValue('verbose_log'));
    $config->save();
    parent::submitForm($form, $form_state);

    if ($form_state->getValue('queue_nodes')) {
      $this->nodeRevisionDeleteBatch->queueBatch();
    }

  }

}
