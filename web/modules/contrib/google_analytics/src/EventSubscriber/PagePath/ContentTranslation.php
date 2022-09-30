<?php

namespace Drupal\google_analytics\EventSubscriber\PagePath;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;
use Drupal\google_analytics\Event\PagePathEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Content Translation to custom URL
 */
class ContentTranslation implements EventSubscriberInterface {

  /**
   * Drupal Config Factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request, ModuleHandlerInterface $module_handler, EntityRepositoryInterface $entity_repsoitory) {
    $this->config = $config_factory->get('google_analytics.settings');
    $this->moduleHandler = $module_handler;
    $this->entityRepository = $entity_repsoitory;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::PAGE_PATH][] = ['onPagePath'];
    return $events;
  }

  /**
   * Adds a new event to the Ga Javascript
   *
   * @param \Drupal\google_analytics\Event\PagePathEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onPagePath(PagePathEvent $event) {
    // Site search tracking support.
    // If this node is a translation of another node, pass the original
    // node instead.
    $request = $this->request->getCurrentRequest();
    if ($this->moduleHandler->moduleExists('content_translation') && $this->config->get('translation_set')) {
      // Check if we have a node object, it has translation enabled, and its
      // language code does not match its source language code.
      if ($request->attributes->has('node')) {
        $node = $request->attributes->get('node');
        if ($node instanceof NodeInterface && $this->entityRepository->getTranslationFromContext($node) !== $node->getUntranslated()) {
          $url_custom = Json::encode(Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['language' => $node->getUntranslated()->language()])->toString());
          $event->setPagePath($url_custom);
          $event->stopPropagation();
        }
      }
    }
  }
}
