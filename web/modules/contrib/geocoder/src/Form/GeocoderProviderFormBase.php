<?php

declare(strict_types = 1);

namespace Drupal\geocoder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\geocoder\Entity\GeocoderProvider;

/**
 * Base class for forms dealing with Geocoder provider entities.
 */
abstract class GeocoderProviderFormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\geocoder\GeocoderProviderInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the Geocoder provider.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this provider. It must only contain lowercase letters, numbers and underscores.'),
      '#machine_name' => [
        'exists' => GeocoderProvider::class . '::load',
      ],
    ];
    $form['plugin'] = [
      '#type' => 'value',
      '#value' => $this->entity->get('plugin'),
    ];

    $plugin = $this->entity->getPlugin();
    if ($plugin && $plugin instanceof PluginFormInterface) {
      $form += $plugin->buildConfigurationForm($form, $form_state);
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $plugin = $this->entity->getPlugin();
    if ($plugin && $plugin instanceof PluginFormInterface) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $plugin = $this->entity->getPlugin();
    if ($plugin && $plugin instanceof PluginFormInterface) {
      $plugin->submitConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->getEntityType()->getSingularLabel()];
    $message = $result === SAVED_NEW
      ? $this->t('Created new geocoder provider %label.', $message_args)
      : $this->t('Updated geocoder provider %label.', $message_args);
    $this->messenger()->addStatus($message);
    try {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
    catch (\Exception $e) {
      watchdog_exception('geocoder', $e);
    }
    return $result;
  }

}
