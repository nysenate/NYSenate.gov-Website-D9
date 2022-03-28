<?php

namespace Drupal\NYS_Openleg\Form;

use Drupal\Core\Form\FormBase as DrupalFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FormBase.
 *
 * Extends Drupal's FormBase to assign the current request.
 */
abstract class FormBase extends DrupalFormBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->request = \Drupal::requestStack()->getCurrentRequest();
  }

}
