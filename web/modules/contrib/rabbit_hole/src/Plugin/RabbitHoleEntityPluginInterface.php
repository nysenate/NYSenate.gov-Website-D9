<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Rabbit hole entity plugin plugins.
 */
interface RabbitHoleEntityPluginInterface extends PluginInspectionInterface {

  /**
   * Return locations to attach submit handlers to entities.
   *
   * This should return an array of arrays, e.g.:
   * [
   *   ['actions', 'submit', '#publish'],
   *   ['actions', 'publish', '#submit'],
   * ].
   */
  public function getFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state);

  /**
   * Return locations to attach submit handlers to entity bundle form.
   *
   * This should return an array of arrays, e.g.:
   * [
   *   ['actions', 'submit', '#publish'],
   *   ['actions', 'publish', '#submit'],
   * ].
   *
   * @return array
   *   A multidimensional array.
   */
  public function getBundleFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state);

  /**
   * Return the form ID of the config form for this plugin's entity.
   *
   * Return the form ID of the global config form for the entity targeted by
   * this plugin.
   *
   * @return string
   *   The form ID of the global config form.
   */
  public function getGlobalConfigFormId();

  /**
   * Return locations to attach submit handlers to the global config form.
   *
   * This should return an array of arrays, e.g.:
   * [
   *   ['actions', 'submit', '#publish'],
   *   ['actions', 'publish', '#submit'],
   * ].
   */
  public function getGlobalFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state);

  /**
   * Return a map of entity IDs used by this plugin to token IDs.
   *
   * @return array
   *   A map of token IDs to entity IDs in the form
   *   ['entity ID' => 'token ID']
   */
  public function getEntityTokenMap();

}
