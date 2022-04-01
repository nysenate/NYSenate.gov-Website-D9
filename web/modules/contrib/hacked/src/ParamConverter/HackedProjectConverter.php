<?php

namespace Drupal\hacked\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\hacked\hackedProject;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity ids to full objects.
 */
class HackedProjectConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return new hackedProject($value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] === 'hacked_project');
  }

}