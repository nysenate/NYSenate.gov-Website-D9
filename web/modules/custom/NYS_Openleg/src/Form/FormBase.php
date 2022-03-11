<?php

namespace Drupal\NYS_Openleg\Form;

use Drupal\Core\Form\FormBase as DrupalFormBase;
use Symfony\Component\HttpFoundation\Request;

abstract class FormBase extends DrupalFormBase {

  protected Request $request;

  /**
   *
   */
  public function __construct() {
    $this->request = \Drupal::requestStack()->getCurrentRequest();
  }

}
