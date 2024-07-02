<?php

namespace Drupal\eva;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;

/**
 * Token handling service.
 */
class TokenHandler {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Inject token dependencies.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token')
    );
  }

  /**
   * Get view arguments array from string that contains tokens.
   *
   * @param string $string
   *   The token string defined by the view.
   * @param string $type
   *   The token type.
   * @param object $object
   *   The object being used for replacement data (typically a node).
   *
   * @return array
   *   An array of argument values.
   */
  public function getArgumentsFromTokenString($string, $type, $object) {
    $args = trim($string);
    if (empty($args)) {
      return [];
    }
    $args = $this->token->replace($args, [$type => $object], ['sanitize' => FALSE]);
    return explode('/', $args);
  }

}
