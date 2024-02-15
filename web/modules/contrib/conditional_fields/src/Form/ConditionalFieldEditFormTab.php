<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * A form to edit a conditional field, designed to be displayed in a tab.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldEditFormTab extends ConditionalFieldEditForm {

  /**
   * The name of the route to redirect to when the form has been submitted.
   *
   * @var string
   */
  protected $redirectPath = 'conditional_fields.tab';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_edit_form_tab';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->cleanValues()->getValues();
    $parameters = ["{$values['entity_type']}_type" => $values['bundle']];
    $redirect = $this->redirectPath . "." . $values['entity_type'];

    $form_state->setRedirect($redirect, $parameters);

  }

}
