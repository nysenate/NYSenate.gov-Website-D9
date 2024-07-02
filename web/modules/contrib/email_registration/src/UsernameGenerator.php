<?php

declare(strict_types=1);

namespace Drupal\email_registration;

use Drupal\Component\Uuid\UuidInterface;

/**
 * The Username Generator service.
 */
final class UsernameGenerator {

  /**
   * The uuid generator object.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * Constructs an UsernameGenerator object.
   */
  public function __construct(UuidInterface $uuid_service) {
    $this->uuid = $uuid_service;
  }

  /**
   * Generates a random suffixed username.
   *
   * @return string
   *   The generated username.
   */
  public function generateRandomUsername(): string {
    return 'email_registration_' . $this->uuid->generate();
  }

}
