<?php

namespace Drupal\nys_sendgrid\Api;

use SendGrid\Mail\TypeException;

/**
 * Represents a template object retrieved from Sendgrid API.
 */
class Template {

  /**
   * Template ID (UUID, 36 characters long)
   *
   * @var string
   */
  protected string $id;

  /**
   * Template name (100 characters max)
   *
   * @var string
   */
  protected string $name;

  /**
   * Template generation.  Must be 'legacy' or 'dynamic'.
   *
   * @var string
   */
  protected string $generation;

  /**
   * Constructor.
   *
   * @throws \SendGrid\Mail\TypeException
   */
  public function __construct($id, $name, $generation) {
    $this->setId($id, $generation)
      ->setName($name)
      ->setGeneration($generation);
  }

  /**
   * Gets the ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Sets the template id, a UUID with a strict length of 36 characters.
   *
   * The legacy templates have 36 characters.  The dynamic templates only
   * have 34, and begin with "d-".
   *
   * @param string $id
   *   A 36-character UUID.
   * @param string $generation
   *   Either 'dynamic' or 'legacy'.
   *
   * @return $this
   *
   * @throws \SendGrid\Mail\TypeException
   */
  public function setId(string $id, string $generation): self {

    $expected = ($generation == 'dynamic' && substr($id, 0, 2) == 'd-') ? 34 : 36;
    if (strlen($id) != $expected) {
      throw new TypeException("Template ID is malformed");
    }
    $this->id = $id;
    return $this;
  }

  /**
   * Gets the name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Sets the template name.  Max length is 100 characters.
   *
   * @param string $name
   *   No longer than 100 characters.
   *
   * @return $this
   *
   * @throws \SendGrid\Mail\TypeException
   */
  public function setName(string $name): self {
    if (strlen($name) > 100) {
      throw new TypeException("Template name must be 100 characters or less");
    }
    $this->name = $name;
    return $this;
  }

  /**
   * Checks if the template is a legacy template.
   */
  public function isLegacy(): bool {
    return $this->getGeneration() == 'legacy';
  }

  /**
   * Gets the generation.
   */
  public function getGeneration(): string {
    return $this->generation;
  }

  /**
   * Sets the generation.
   *
   * @param string $generation
   *   Acceptable values are 'legacy' and 'dynamic'.
   *
   * @return $this
   *
   * @throws \SendGrid\Mail\TypeException
   */
  public function setGeneration(string $generation): self {
    switch ($generation) {
      case 'legacy':
      case 'dynamic':
        $this->generation = $generation;
        break;

      default:
        throw new TypeException("Generation must be 'legacy' or 'dynamic'");
    }
    return $this;
  }

  /**
   * Checks if the template is a dynamic template.
   */
  public function isDynamic(): bool {
    return $this->getGeneration() == 'dynamic';
  }

}
