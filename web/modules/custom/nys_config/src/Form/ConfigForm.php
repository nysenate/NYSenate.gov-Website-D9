<?php

namespace Drupal\nys_config\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class for nys config.
 */
class ConfigForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static($container->get('config.factory'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_config.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('nys_config.settings');

    /*
     * GOVERNOR.
     */
    $form['governor'] = [
      '#type' => 'fieldset',
      '#title' => t('Current Governor'),
      '#description' => $this->t("Variations for displaying the current governor's name."),
    ];

    $form['governor']['governor_full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#default_value' => $config->get('governor_full_name'),
      '#description' => $this->t("Governor's full name. (e.g. \"Franklin D. Roosevelt\" or \"Nelson Rockefeller\")"),
    ];

    $form['governor']['governor_last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $config->get('governor_last_name'),
      '#description' => $this->t("Governor's last name. (e.g. \"Roosevelt\" or \"Rockefeller\")"),
    ];

    /*
     * Previous Session.
     */
    $form['previous_session'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Previous Session Query Cache config'),
    ];
    $form['previous_session']['nys_access_permissions_prev_query_ttl'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache Lifetime'),
      '#options' => [
        '+1 hour' => $this->t('1 hour'),
        '+2 hours' => $this->t('2 hours'),
        '+4 hours' => $this->t('4 hours'),
        '+6 hours' => $this->t('6 hours'),
        '+12 hours' => $this->t('12 hours'),
        '+24 hours' => $this->t('24 hours'),
        '+3 days' => $this->t('3 days'),
        '+1 week' => $this->t('1 week'),
      ],
      '#empty_option' => $this->t('-- Select Lifetime --'),
      '#description' => $this->t('Select the Previous Session Query Cache lifetime. This determines how long the cache for queries for Previous Session is valid for.'),
      '#default_value' => $config->get('nys_access_permissions_prev_query_ttl'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nys_config.settings');
    $config->set('governor_full_name', $form_state->getValue('governor_full_name'));
    $config->set('governor_last_name', $form_state->getValue('governor_last_name'));
    $config->set('nys_access_permissions_prev_query_ttl', $form_state->getValue('nys_access_permissions_prev_query_ttl'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
