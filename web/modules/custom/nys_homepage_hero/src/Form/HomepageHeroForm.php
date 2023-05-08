<?php

namespace Drupal\nys_homepage_hero\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for nys_homepage_hero.
 */
class HomepageHeroForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_homepage_hero_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_homepage_hero.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory->getEditable('nys_homepage_hero.settings');

    // Container fieldset.
    $form['hero'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Homepage Hero'),
    ];

    // Upload field.
    $form['hero']['homepage_hero'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Hero Image'),
      '#default_value' => $config->get('homepage_hero') ?? NULL,
      '#description' => $this->t('To change hero image, click Remove button, and then click Browse to either upload a new hero image or choose an existing one from the hero image library.'),
      '#allowed_bundles' => ['image'],
      '#cardinality' => 1,
      '#multiselect' => FALSE,
      '#media_upload_allowed' => TRUE,
      '#media_upload_max_filesize' => '2M',
      '#upload_location' => 'public://homepage_hero/',
      '#destination' => 'public://homepage_hero/',
      '#directory' => 'homepage_hero',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Retrieve the configuration, set values and save config.
    $this->configFactory->getEditable('nys_homepage_hero.settings')
      ->set('homepage_hero', $values['homepage_hero'])
      ->save();

    // Invalidate cache tags on views:homepage_hero.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['views:homepage_hero']);

    parent::submitForm($form, $form_state);
  }

}
