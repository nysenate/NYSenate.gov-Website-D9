<?php

namespace Drupal\devel_dumper_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for dumper_test module.
 *
 * @package Drupal\devel_dumper_test\Controller
 */
class DumperTestController extends ControllerBase {

  /**
   * The dumper manager.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * Constructs a new DumperTestController object.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $devel_dumper_manager
   *   The dumper manager.
   */
  public function __construct(DevelDumperManagerInterface $devel_dumper_manager) {
    $this->dumper = $devel_dumper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('devel.dumper')
    );
  }

  /**
   * Returns the dump output to test.
   *
   * @return array
   *   The render array output.
   */
  public function dump(): array {
    $this->dumper->dump('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the message output to test.
   *
   * @return array
   *   The render array output.
   */
  public function message(): array {
    $this->dumper->message('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the debug output to test.
   *
   * @return array
   *   The render array output.
   */
  public function debug(): array {
    $this->dumper->debug('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the export output to test.
   *
   * @return array
   *   The render array output.
   */
  public function export(): array {
    return [
      '#markup' => $this->dumper->export('Test output'),
    ];
  }

  /**
   * Returns the renderable export output to test.
   *
   * @return array
   *   The render array output.
   */
  public function exportRenderable(): array {
    return $this->dumper->exportAsRenderable('Test output');
  }

}
