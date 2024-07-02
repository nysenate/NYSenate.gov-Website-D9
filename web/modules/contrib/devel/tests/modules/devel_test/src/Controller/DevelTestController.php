<?php

namespace Drupal\devel_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for devel module routes.
 */
class DevelTestController extends ControllerBase {

  /**
   * Constructs a new DevelTestController object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    TranslationInterface $string_translation
  ) {
    $this->stringTranslation = $string_translation;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
    );
  }

  /**
   * Returns a simple page output.
   *
   * @return array
   *   A render array.
   */
  public function simplePage(): array {
    return [
      '#markup' => $this->t('Simple page'),
    ];
  }

}
