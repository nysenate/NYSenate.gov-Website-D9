<?php

namespace Drupal\password_policy\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\password_policy\Entity\PasswordPolicy;

/**
 * Form that lists out the constraints for the policy.
 */
class PasswordPolicyFormEdit extends PasswordPolicyForm {


  /**
   * Role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $storage;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Plugin manager for constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Overridden constructor to load the plugin.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   Plugin manager for constraints.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\user\RoleStorageInterface $storage
   *   Role storage.
   */
  public function __construct(PluginManagerInterface $manager, LanguageManagerInterface $language_manager, FormBuilderInterface $formBuilder, MessengerInterface $messenger, RoleStorageInterface $storage) {
    $this->manager = $manager;
    $this->languageManager = $language_manager;
    $this->formBuilder = $formBuilder;
    $this->messenger = $messenger;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $container->get('plugin.manager.password_policy.password_constraint'),
      $container->get('language_manager'),
      $container->get('form_builder'),
      $container->get('messenger'),
      $entity_type_manager->getStorage('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $constraints = [];
    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      $constraints[$plugin_id] = (string) $definition['title'];
    }
    if (empty($constraints)) {
      $this->messenger->addWarning($this->t("No password constraint available. Enable one of the modules that has password constraints."));
    }
    else {
      $form['constraints_fieldset'] = [
        '#type' => 'fieldset',
      ];
      $form['constraints_fieldset']['add_constraint_title'] = [
        '#markup' => '<h2>' . $this->t('Add Constraint') . '</h2>',
      ];

      $form['constraints_fieldset']['constraint'] = [
        '#type' => 'select',
        '#options' => $constraints,
        '#prefix' => '<table style="width=100%"><tr><td>',
        '#suffix' => '</td>',
      ];
      $form['constraints_fieldset']['add'] = [
        '#type' => 'submit',
        '#name' => 'add',
        '#value' => $this->t('Configure Constraint Settings'),
        '#ajax' => [
          'callback' => [$this, 'add'],
          'event' => 'click',
        ],
        '#prefix' => '<td>',
        '#suffix' => '</td></tr></table>',
      ];
    }

    $form['constraints_fieldset']['constraint_list'] = [
      '#markup' => '<h2>' . $this->t('Policy Constraints') . '</h2>',
    ];

    $form['constraints_fieldset']['items'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="configured-constraints">',
      '#suffix' => '</div>',
      '#theme' => 'table',
      '#header' => [
        'plugin_id' => $this->t('Plugin Id'),
        'summary' => $this->t('Summary'),
        'operations' => $this->t('Operations'),
      ],
      '#rows' => $this->renderRows($this->entity),
      '#empty' => $this->t('No constraints have been configured.'),
    ];

    $options = [];
    foreach ($this->storage->loadMultiple() as $role) {
      $options[$role->id()] = $role->label();
    }

    $form['apply_on'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'roles',
    ];
    $form['apply_on_roles'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'apply_on',
    ];
    unset($options[AccountInterface::ANONYMOUS_ROLE]);
    $form['apply_on_roles']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Apply to Roles'),
      '#description' => $this->t('Select Roles to which this policy applies.'),
      '#options' => $options,
      '#default_value' => $this->entity->getRoles(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status) {
      $this->messenger()->addMessage($this->t('The password policy %label has been added.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The password policy was not saved.'));
    }
  }

  /**
   * Check to validate that the Password Policy name does not already exist.
   *
   * @param string $name
   *   The machine name of the context to validate.
   *
   * @return bool
   *   TRUE on context name already exist, FALSE on context name not exist.
   */
  public function passwordPolicyExists($name) {
    $entity = $this->entityTypeManager->getStorage('password_policy')->loadByProperties(['name' => $name]);

    return (bool) $entity;
  }

  /**
   * Helper function to render the constraint rows for the policy.
   *
   * @param Drupal\password_policy\Entity\PasswordPolicy $policy
   *   The policy entity.
   *
   * @return array
   *   Constraint rows rendered for the policy.
   */
  public function renderRows(PasswordPolicy $policy) {
    $configured_conditions = [];
    foreach ($policy->getConstraints() as $row => $constraint) {
      /** @var \Drupal\password_policy\PasswordConstraintInterface $instance */
      $instance = $this->manager->createInstance($constraint['id'], $constraint);

      $operations = $this->getOperations('entity.password_policy.constraint', [
        'password_policy_id' => $this->entity->id(),
        'machine_name' => $constraint['id'],
        'constraint_id' => $row,
      ]);

      $build = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $configured_conditions[] = [
        'plugin_id' => $instance->getPluginId(),
        'summary' => $instance->getSummary(),
        'operations' => [
          'data' => $build,
        ],
      ];
    }
    return $configured_conditions;
  }

  /**
   * Helper function to load edit operations for a constraint.
   *
   * @param string $route_name_base
   *   String representing the base of the route name for the constraints.
   * @param array $route_parameters
   *   Passing route parameter context to the helper function.
   *
   * @return array
   *   Set of operations associated with a constraint.
   */
  protected function getOperations($route_name_base, array $route_parameters = []) {
    $edit_url = new Url($route_name_base . '.edit', $route_parameters);
    $route_parameters['id'] = $route_parameters['constraint_id'];
    unset($route_parameters['constraint_id']);
    $delete_url = new Url($route_name_base . '.delete', $route_parameters);
    $operations = [];

    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'url' => $edit_url,
      'weight' => 10,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];
    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'url' => $delete_url,
      'weight' => 100,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];
    return $operations;
  }

  /**
   * Ajax callback that manages adding a constraint.
   *
   * @param array $form
   *   Form definition of parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns the valid Ajax response from a modal window.
   */
  public function add(array &$form, FormStateInterface $form_state) {
    $constraint = $form_state->getValue('constraint');
    $content = $this->formBuilder->getForm(ConstraintEdit::class, $constraint, $form_state->getValue('id'));
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $url = Url::fromRoute('entity.password_policy.constraint.add', [
      'machine_name' => $this->entity->id(),
      'constraint_id' => $constraint,
    ], ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]]);
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = $url->toString();
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Configure Required Context'), $content, ['width' => '700']));
    return $response;
  }

}
