<?php

namespace Drupal\devel\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel\Form\SwitchUserForm;
use Drupal\devel\SwitchUserListHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for switching users.
 *
 * @Block(
 *   id = "devel_switch_user",
 *   admin_label = @Translation("Switch user"),
 *   category = "Devel"
 * )
 */
class SwitchUserBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The FormBuilder object.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * A helper for creating the user list form.
   */
  protected SwitchUserListHelper $switchUserListHelper;

  /**
   * Constructs a new SwitchUserBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\devel\SwitchUserListHelper $switchUserListHelper
   *   A helper for creating the user list form.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $form_builder,
    SwitchUserListHelper $switchUserListHelper,
    TranslationInterface $string_translation
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->switchUserListHelper = $switchUserListHelper;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('form_builder'),
      $container->get('devel.switch_user_list_helper'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'list_size' => 12,
      'include_anon' => FALSE,
      'show_form' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'switch users');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $anonymous = new AnonymousUserSession();
    $form['list_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of users to display in the list'),
      '#default_value' => $this->configuration['list_size'],
      '#min' => 1,
      '#max' => 50,
    ];
    $form['include_anon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include %anonymous', ['%anonymous' => $anonymous->getDisplayName()]),
      '#default_value' => $this->configuration['include_anon'],
    ];
    $form['show_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow entering any user name'),
      '#default_value' => $this->configuration['show_form'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['list_size'] = $form_state->getValue('list_size');
    $this->configuration['include_anon'] = $form_state->getValue('include_anon');
    $this->configuration['show_form'] = $form_state->getValue('show_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    if ($accounts = $this->switchUserListHelper->getUsers($this->configuration['list_size'], $this->configuration['include_anon'])) {
      $build['devel_links'] = $this->switchUserListHelper->buildUserList($accounts);

      if ($this->configuration['show_form']) {
        $build['devel_form'] = $this->formBuilder->getForm(SwitchUserForm::class);
      }
    }

    return $build;
  }

}
