<?php

declare(strict_types=1);

namespace Drupal\email_registration;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a new registration service provider.
 */
class EmailRegistrationServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace the core authentication service with our version
    // which supports authorization through email.
    $definition = $container->getDefinition('user.auth');
    $definition->setClass(UserAuth::class);
  }

}
