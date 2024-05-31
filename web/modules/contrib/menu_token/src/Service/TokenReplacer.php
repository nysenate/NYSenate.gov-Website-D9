<?php

namespace Drupal\menu_token\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\token\TokenInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * TokenReplacer class.
 */
class TokenReplacer {

  protected $tokenService;
  protected $contextRepository;
  protected $tokenEntityMapper;
  protected $entityTypeManager;
  protected $url;

  /**
   * {@inheritdoc}
   */
  public function __construct(TokenInterface $tokenService, ContextRepositoryInterface $c, TokenEntityMapperInterface $tem, EntityTypeManagerInterface $en) {
    $this->tokenService = $tokenService;
    $this->contextRepository = $c;
    $this->tokenEntityMapper = $tem;
    $this->entityTypeManager = $en;
  }

  /**
   * Get token type from token string.
   *
   * @param string $token
   *   Token string.
   *
   * @return mixed
   *   array.
   */
  private function getTokenType($token) {
    preg_match_all('/
      \[             # [ - pattern start
      ([^\s\[\]:]+)  # match $type not containing whitespace : [ or ]
      :              # : - separator
      ([^\[\]]+)     # match $name not containing [ or ]
      \]             # ] - pattern end
      /x', $token, $matches);

    $types = $matches[1];
    return $types[0];
  }

  /**
   * Replacment for the none selection in the admin menu.
   *
   * @param string $token
   *   Token string.
   *
   * @return mixed
   *   array.
   */
  public function replaceNone($token) {
    $replacement = [$token => ''];
    return $replacement;
  }

  /**
   * Replace token string with the value from context.
   *
   * @param string $token
   *   Token to be replaced.
   * @param string $key
   *   Key in token.
   * @param \Drupal\Core\Render\BubbleableMetadata $b
   *   Bubable metadata for cache context.
   *
   * @return array|string
   *   Returns replaced token.
   */
  public function replaceContext($token, $key, BubbleableMetadata $b) {
    $token_type = $this->getTokenType($token);
    $entity_type = $this->tokenEntityMapper->getEntityTypeForTokenType($token_type);

    $b->addCacheContexts(["url"]);
    $b->addCacheContexts(["user"]);

    // If there is no entity type we are in trouble..
    if ($entity_type === FALSE) {
      return "";
    }

    $contexts_def = $this->contextRepository->getAvailableContexts();
    $real_context = $this->contextRepository->getRuntimeContexts(array_keys($contexts_def));

    foreach ($real_context as $key_i => $real_ci) {
      if (!$real_ci->hasContextValue()) {
        continue;
      }
      $context_data_definition_type = $real_ci->getContextData()->getPluginDefinition();
      $value = $real_ci->getContextData()->getValue();

      // Service contextRepository does not return value as expected
      // on anonymous users.
      if ($entity_type == "user" && method_exists($value, "isAnonymous") && $value->isAnonymous()) {
        // $value = User::load(\Drupal::currentUser()->id());.
        // Drupal screw me... User will always ask why
        // there are nothing shown for anonymous user..
        // Let them have string Anonymous and they will be happy and quiet.
        return [$token => "Anonymous"];
      }

      if (empty($value)) {
        switch ($entity_type) {
          case "user":
            $value = User::load(\Drupal::currentUser()->id());
            break;

          default:
            break;
        }
      }

      if ($context_data_definition_type["id"] == "entity" && method_exists($value, "getEntityTypeId") && $value->getEntityTypeId() == $entity_type) {
        if (!empty($value)) {
          $r_var = $value;
          if (is_array($r_var)) {
            $r_var = array_pop($r_var);
          }
          $replacement = $this->tokenService->generate($token_type, [$key => $token], [$token_type => $r_var], [], $b);
          return $replacement;
        }
      }
    }
    return "";
  }

  /**
   * Replace token string with the value from context.
   *
   * @param string $token
   *   Token to be replaced.
   * @param string $key
   *   Key in token.
   * @param \Drupal\Core\Render\BubbleableMetadata $b
   *   Bubable metadata for cache context.
   *
   * @return array|string
   *   Returns replaced token.
   */
  public function replaceRandom($token, $key, BubbleableMetadata $b) {

    $token_type = $this->getTokenType($token);
    $entity_type = $this->tokenEntityMapper->getEntityTypeForTokenType($token_type);
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery("AND");
    $user_ids = $query->execute();

    // Pick one random user.
    $random_id = array_rand($user_ids, 1);
    $random_user = $this->entityTypeManager->getStorage($entity_type)
      ->load($random_id);

    $replacement = $this->tokenService->generate($token_type, [$key => $token], [$token_type => $random_user], [], $b);
    return $replacement;
  }

  /**
   * Replace token string with the value from context.
   *
   * @param string $token
   *   Token to be replaced.
   * @param string $key
   *   Key in token.
   * @param string $value
   *   Admin submited value.
   * @param \Drupal\Core\Render\BubbleableMetadata $b
   *   Bubable metadata for cache context.
   *
   * @return array|string
   *   Returns replaced token.
   */
  public function replaceUserDefined($token, $key, $value, BubbleableMetadata $b) {

    $token_type = $this->getTokenType($token);
    $entity_type = $this->tokenEntityMapper->getEntityTypeForTokenType($token_type);

    $entity_object = $this->entityTypeManager->getStorage($entity_type)
      ->load($value);
    $replacement = $this->tokenService->generate($token_type, [$key => $token], [$token_type => $entity_object], [], $b);
    return $replacement;
  }

  /**
   * Replace token string with the value from global and special tokens.
   *
   * @param string $token
   *   Token to be replaced.
   * @param string $key
   *   Key in token.
   * @param \Drupal\Core\Render\BubbleableMetadata $b
   *   Bubable metadata for cache context.
   *
   * @return array|string
   *   Returns replaced token.
   */
  public function replaceExoticToken($token, $key, BubbleableMetadata $b) {

    $token_type = $this->getTokenType($token);

    $b->addCacheContexts(["url"]);
    $b->addCacheContexts(["user"]);

    $data = [];
    switch ($token_type) {
      case "url":
        $data["url"] = Url::createFromRequest(\Drupal::request());
        break;

      case "current-user":
        $data["user"] = User::load(\Drupal::currentUser()->id());

        if (method_exists($data["user"], "isAnonymous") && $data["user"]->isAnonymous()) {
          return [$token => "Anonymous"];
        }
        break;

      default:
        break;
    }

    // Exotic tokens...
    $replacement = $this->tokenService->generate($token_type, [$key => $token], $data, [], $b);
    return $replacement;
  }

}
