<?php

namespace Drupal\menu_token\Service;

use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\token\TokenInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Menu token context manager service.
 */
class MenuTokenContextManager {

  protected $tokenService;
  protected $contextRepository;
  protected $tokenEntityMapper;
  protected $state;
  protected $entityTypeManager;
  protected $menuTokenMenuLinkManager;
  protected $contextualReplacementLinks;

  /**
   * {@inheritdoc}
   */
  public function __construct(TokenInterface $tokenService, ContextRepositoryInterface $c, TokenEntityMapperInterface $tem, EntityTypeManagerInterface $en, StateInterface $state, MenuLinkManagerInterface $mlm) {
    $this->tokenService = $tokenService;
    $this->contextRepository = $c;
    $this->tokenEntityMapper = $tem;
    $this->entityTypeManager = $en;
    $this->state = $state;
    $this->menuTokenMenuLinkManager = $mlm;
    $this->contextualReplacementLinks = unserialize($this->state->get('menu_token_links_contextual_replacements'));
    if (empty($this->contextualReplacementLinks)) {
      $this->contextualReplacementLinks = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUndiscoveredMenuDefinitions() {
    // Get all custom menu links which should be rediscovered.
    $entity_ids = $this->entityTypeManager->getStorage('menu_link_content')->getQuery()
      ->condition('rediscover', FALSE)
      ->execute();
    $plugin_definitions = [];
    $menu_link_content_entities = $this->entityTypeManager->getStorage('menu_link_content')->loadMultiple($entity_ids);
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
    foreach ($menu_link_content_entities as $menu_link_content) {
      $plugin_definitions['menu_link_content:' . $menu_link_content->uuid()] = $menu_link_content->getPluginDefinition();
    }
    return $plugin_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareContextualLinks($relevantLink, $config) {

    $this->contextualReplacementLinks = unserialize($this->state->get('menu_token_links_contextual_replacements'));
    $text_tokens = $this->tokenService->scan($relevantLink["url"]);
    $text_tokens = array_merge($text_tokens, $this->tokenService->scan($relevantLink["title"]));

    $use_in_context = FALSE;
    foreach ($text_tokens as $token_type => $tokens) {
      $entity_type = $this->tokenEntityMapper->getEntityTypeForTokenType($token_type);

      if (empty($config[$entity_type][0]) || $config[$entity_type][0] === "context") {
        $use_in_context = TRUE;
      }
      if ($entity_type === FALSE) {
        $use_in_context = TRUE;
      }
    }

    if ($use_in_context) {
      $this->contextualReplacementLinks[$relevantLink['id']] = [
        "link" => $relevantLink,
        "config" => $config,
      ];
    }
    else {
      unset($this->contextualReplacementLinks[$relevantLink['id']]);
    }
    $this->state->set('menu_token_links_contextual_replacements', serialize($this->contextualReplacementLinks));
  }

  /**
   * @param $uuid_from_link
   */
  public function removeFromState($uuid_from_link) {
    unset($this->contextualReplacementLinks[$uuid_from_link]);
  }

  /**
   * Reset menu_token_links_contextual_replacements.
   */
  public function clear() {
    $this->contextualReplacementLinks = [];
    $this->state->set('menu_token_links_contextual_replacements', serialize($this->contextualReplacementLinks));
  }

  /**
   * Replace contextual links.
   */
  public function replaceContextualLinks() {
    $contextual_replacement_links = unserialize($this->state->get('menu_token_links_contextual_replacements'));
    if (empty($contextual_replacement_links)) {
      return TRUE;
    }
    $this->menuTokenMenuLinkManager->rebuildMenuToken($contextual_replacement_links);
  }

}
