<?php

namespace Drupal\nys_dashboard\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Your issues filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("your_issues_filter")
 */
class YourIssuesFilter extends FilterPluginBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public $entityTypeManager;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  public $currentUser;

  /**
   * Constructs a YourSenaterFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager, AccountProxy $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
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
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $follow_issue_flag_ids = $this->entityTypeManager
      ->getStorage('flagging')
      ->getQuery()
      ->condition('flag_id', 'follow_issue')
      ->condition('uid', $this->currentUser->id())
      ->execute();
    $options = [];
    if (!empty($follow_issue_flag_ids)) {
      $follow_issue_flags = $this->entityTypeManager
        ->getStorage('flagging')
        ->loadMultiple($follow_issue_flag_ids);
      foreach ($follow_issue_flags as $flag) {
        $entity_id = $flag->flagged_entity?->referencedEntities()[0]?->id();
        $entity_label = $flag->flagged_entity?->referencedEntities()[0]?->label();
        if (!empty($entity_id) && !empty($entity_label)) {
          $options[$entity_id] = $entity_label;
        }
      }
    }
    $form['value'] = [
      '#type' => 'select',
      '#title' => 'Your issues',
      '#options' => $options,
    ];
  }

}
