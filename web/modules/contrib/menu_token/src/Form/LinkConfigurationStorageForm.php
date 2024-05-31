<?php

namespace Drupal\menu_token\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LinkConfigurationStorageForm.
 *
 * @package Drupal\menu_token\Form
 */
class LinkConfigurationStorageForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\menu_token\Entity\LinkConfigurationStorage $link_configuration_storage */
    $link_configuration_storage = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $link_configuration_storage->label(),
      '#description' => $this->t("Label for the Link configuration storage."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $link_configuration_storage->id(),
      '#machine_name' => [
        'exists' => '\Drupal\menu_token\Entity\LinkConfigurationStorage::load',
      ],
      '#disabled' => !$link_configuration_storage->isNew(),
    ];

    $form['linkid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link id'),
      '#maxlength' => 255,
      '#default_value' => $link_configuration_storage->linkid,
      '#description' => $this->t("Link id for configuration storage"),
      '#required' => TRUE,
    ];

    $form['configurationSerialized'] = [
      '#type' => 'textfield',
      '#title' => $this->t('configurationSerialized'),
      '#maxlength' => 255,
      '#default_value' => $link_configuration_storage->configurationSerialized,
      '#description' => $this->t("Serialized configuration"),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $link_configuration_storage = $this->entity;
    $status = $link_configuration_storage->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Link configuration storage.', [
          '%label' => $link_configuration_storage->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Link configuration storage.', [
          '%label' => $link_configuration_storage->label(),
        ]));
    }
    $form_state->setRedirectUrl($link_configuration_storage->toUrl('collection'));
  }

}
