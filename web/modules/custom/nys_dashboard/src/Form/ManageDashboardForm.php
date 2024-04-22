<?php

namespace Drupal\nys_dashboard\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to manage a user's subscribed issues, bills, or committees.
 */
class ManageDashboardForm extends FormBase {

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
   * Constructs a ManageDashboardForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, AccountProxy $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_dashboard_manage_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['type_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter type'),
      '#options' => [
        'All' => '- Any -',
        'bills' => 'Bills',
        'issues' => 'Issues',
        'committees' => 'Committees',
      ],
      '#ajax' => [
        'callback' => [$this, 'applyFormFilter'],
        'wrapper' => 'followed-types-fieldset-wrapper',
      ],
    ];
    $form['followed_types_fieldset'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="followed-types-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Followed bills checkboxes.
    $bill_options = $this->getUsersFlaggedEntitiesByFlagName('follow_this_bill');
    $form['followed_types_fieldset']['bills'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Bills You're Following"),
      '#options' => $bill_options,
      '#default_value' => array_keys($bill_options),
    ];

    // Followed issues checkboxes.
    $issue_options = $this->getUsersFlaggedEntitiesByFlagName('follow_issue');
    $form['followed_types_fieldset']['issues'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Issues You're Following"),
      '#options' => $issue_options,
      '#default_value' => array_keys($issue_options),
    ];

    // Followed committees checkboxes.
    $committee_options = $this->getUsersFlaggedEntitiesByFlagName('follow_committee');
    $form['followed_types_fieldset']['committees'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Committees You're Following"),
      '#options' => $committee_options,
      '#default_value' => array_keys($committee_options),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update my preferences'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * Ajax callback to inject re-rendered fieldset form element.
   */
  public function applyFormFilter(array &$form, FormStateInterface $form_state) {
    $follow_types = ['bills', 'issues', 'committees'];
    $type_filter = $form_state->getValue('type_filter');
    foreach ($follow_types as $type) {
      // Ensure re-rendered fields default to checked.
      foreach ($form['followed_types_fieldset'][$type]['#default_value'] as $checkbox) {
        $form['followed_types_fieldset'][$type][$checkbox]['#checked'] = TRUE;
      }
      // Disable access to all but selected follow types.
      if (!empty($type_filter) && $type_filter !== 'All') {
        if ($type == $type_filter) {
          continue;
        }
        $form['followed_types_fieldset'][$type]['#access'] = FALSE;
      }
    }
    return $form['followed_types_fieldset'];
  }

  /**
   * Gets the current user's flagged entities by the flag machine name.
   *
   * @param string $flag_machine_name
   *   The flag's machine name.
   *
   * @return array
   *   An array of entity ID keys and entity label values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUsersFlaggedEntitiesByFlagName(string $flag_machine_name): array {
    $flag_ids = $this->entityTypeManager
      ->getStorage('flagging')
      ->getQuery()
      ->condition('flag_id', $flag_machine_name)
      ->condition('uid', $this->currentUser->id())
      ->execute();
    $entity_ids_and_labels = [];
    if (!empty($flag_ids)) {
      $flags = $this->entityTypeManager
        ->getStorage('flagging')
        ->loadMultiple($flag_ids);
      foreach ($flags as $flag) {
        $entity_id = $flag->flagged_entity?->referencedEntities()[0]?->id();
        $entity_label = $flag->flagged_entity?->referencedEntities()[0]?->label();
        if (!empty($entity_id) && !empty($entity_label)) {
          $entity_ids_and_labels[$entity_id] = $entity_label;
        }
      }
    }
    return $entity_ids_and_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

}
