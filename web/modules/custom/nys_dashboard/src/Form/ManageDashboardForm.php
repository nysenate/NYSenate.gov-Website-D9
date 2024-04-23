<?php

namespace Drupal\nys_dashboard\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\flag\FlagService;
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
   * Flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  public $flagService;

  /**
   * Mapping of field names to flag types.
   *
   * @var array
   */
  public array $fieldNamesToFlagTypes = [
    'bills' => 'follow_this_bill',
    'issues' => 'follow_issue',
    'committees' => 'follow_committee',
  ];

  /**
   * Constructs a ManageDashboardForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   * @param \Drupal\flag\FlagService $flagService
   *   Flag service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, AccountProxy $currentUser, FlagService $flagService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->flagService = $flagService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('flag'),
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

    foreach ($this->fieldNamesToFlagTypes as $field_name => $flag_type) {
      $options = $this->getUsersFlaggedEntitiesByFlagName($flag_type);
      $form['followed_types_fieldset'][$field_name] = [
        '#type' => 'checkboxes',
        '#title' => ucfirst($field_name) . " You're Following",
        '#description' => 'To stop following, uncheck ' . $field_name . ' and click Update My Preferences',
        '#description_display' => 'before',
        '#options' => $options,
        '#default_value' => array_keys($options),
      ];
    }

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
    $type_filter = $form_state->getValue('type_filter');
    foreach ($this->fieldNamesToFlagTypes as $field_name => $flag_type) {
      // Ensure ajax re-rendered fields default to checked.
      foreach ($form['followed_types_fieldset'][$field_name]['#default_value'] as $flagged_entity_id) {
        $form['followed_types_fieldset'][$field_name][$flagged_entity_id]['#checked'] = TRUE;
      }
      // Disable access to all but selected follow types.
      if (!empty($type_filter) && $type_filter !== 'All') {
        if ($field_name == $type_filter) {
          continue;
        }
        $form['followed_types_fieldset'][$field_name]['#access'] = FALSE;
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $content_to_unfollow = [];
    $user_input = $form_state->getUserInput();
    if (!empty($user_input)) {
      foreach ($this->fieldNamesToFlagTypes as $field_name => $flag_type) {
        // Only record input that matches type_filter.
        if ($user_input['type_filter'] !== 'All' && $user_input['type_filter'] !== $field_name) {
          continue;
        }
        $user_unchecked_entity_ids = array_diff(
          array_keys($form['followed_types_fieldset'][$field_name]['#options']),
          array_values($user_input[$field_name])
        );
        foreach ($user_unchecked_entity_ids as $flagged_entity_id) {
          $content_to_unfollow[] = [
            'flag_type' => $flag_type,
            'flagged_entity_id' => $flagged_entity_id,
          ];
        }
      }
    }
    if (!empty($content_to_unfollow)) {
      $unfollow_content_list = '';
      foreach ($content_to_unfollow as $key => $unfollow_content) {
        $flag = $this->flagService->getFlagById($unfollow_content['flag_type']);
        $flagged_entity_type_id = ($unfollow_content['flag_type'] !== 'follow_this_bill') ? 'taxonomy_term' : 'node';
        $flagged_entity = $this->entityTypeManager
          ->getStorage($flagged_entity_type_id)
          ->load($unfollow_content['flagged_entity_id']);
        try {
          $this->flagService->unflag($flag, $flagged_entity, $this->currentUser);
        }
        catch (\Exception $error) {
          $this->messenger()->addError('There was an error unfollowing chosen content. Please try again later.');
          continue;
        }
        $list_separator = ($key === array_key_last($content_to_unfollow)) ? '.' : ', ';
        $unfollow_content_list .= $flagged_entity->label() . $list_separator;
      }
      if (!empty($unfollow_content_list)) {
        $this->messenger()->addStatus(
          'Successfully unfollowed the followed bills, issues, or committees: '
          . $unfollow_content_list
        );
      }
      return;
    }
    $this->messenger()->addWarning('Nothing chosen to unfollow.');
  }

}
