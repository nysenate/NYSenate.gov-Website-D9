<?php

namespace Drupal\conditional_fields\Form;

/**
 * A conditional fields form designed to appear in a tab.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldFormTab extends ConditionalFieldForm {

  /**
   * {@inheritdoc}
   */
  protected $editPath = 'conditional_fields.edit_form.tab';

  /**
   * {@inheritdoc}
   */
  protected $deletePath = 'conditional_fields.delete_form.tab';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_form_tab';
  }

}
