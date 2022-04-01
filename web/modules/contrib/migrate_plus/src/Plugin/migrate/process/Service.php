<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\process\Callback;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a plugin to use a callable from a service class.
 *
 * Example:
 *
 * @code
 * process:
 *   filemime:
 *     plugin: service
 *     service: file.mime_type.guesser
 *     method: guessMimeType
 *     source: filename
 * @endcode
 *
 * All options for the callback plugin can be used, except for 'callable',
 * which will be ignored.
 *
 * @see \Drupal\migrate\Plugin\migrate\process\Callback
 *
 * @MigrateProcessPlugin(
 *   id = "service"
 * )
 */
class Service extends Callback implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['service'])) {
      throw new \InvalidArgumentException('The "service" must be set.');
    }
    if (!isset($configuration['method'])) {
      throw new \InvalidArgumentException('The "method" must be set.');
    }
    if (!$container->has($configuration['service'])) {
      throw new \InvalidArgumentException(sprintf('You have requested the non-existent service "%s".', $configuration['service']));
    }
    $service = $container->get($configuration['service']);
    if (!method_exists($service, $configuration['method'])) {
      throw new \InvalidArgumentException(sprintf('The "%s" service has no method "%s".', $configuration['service'], $configuration['method']));
    }

    $configuration['callable'] = [$service, $configuration['method']];
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
