<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a header block for the Senator Dashboard.
 */
#[Block(
  id: 'senator_dashboard_header_block',
  admin_label: new TranslatableMarkup('Senator Dashboard header block')
)]
class SenatorDashboardHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The breadcrumb builder.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbBuilder;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The managed senators handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * The title resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected TitleResolverInterface $titleResolver;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected MenuLinkManagerInterface $menuLinkManager;

  /**
   * The menu active trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected MenuActiveTrailInterface $menuActiveTrail;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The route provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected RouteProvider $routeProvider;

  /**
   * The senator dashboard helper service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper
   */
  protected SenatorDashboardHelper $senatorDashboardHelper;

  /**
   * Constructs a SenatorDashboardHeaderBlock object.
   *
   * @param array $configuration
   *   A configuration array containing plugin instance information.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managed_senators_handler
   *   The managed senators handler service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The menu active trail service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider service.
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumbBuilder
   *   The breadcrumb builder.
   * @param \Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper $senator_dashboard_helper
   *   The senator dashboard helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ManagedSenatorsHandler $managed_senators_handler,
    TitleResolverInterface $title_resolver,
    RouteMatchInterface $route_match,
    FileUrlGeneratorInterface $file_url_generator,
    MenuLinkManagerInterface $menu_link_manager,
    MenuActiveTrailInterface $menu_active_trail,
    RequestStack $request_stack,
    RouteProvider $route_provider,
    BreadcrumbBuilderInterface $breadcrumbBuilder,
    SenatorDashboardHelper $senator_dashboard_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->managedSenatorsHandler = $managed_senators_handler;
    $this->titleResolver = $title_resolver;
    $this->routeMatch = $route_match;
    $this->fileUrlGenerator = $file_url_generator;
    $this->menuLinkManager = $menu_link_manager;
    $this->menuActiveTrail = $menu_active_trail;
    $this->requestStack = $request_stack;
    $this->routeProvider = $route_provider;
    $this->breadcrumbBuilder = $breadcrumbBuilder;
    $this->senatorDashboardHelper = $senator_dashboard_helper;
  }

  /**
   * Creates a SenatorDashboardHeaderBlock instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   A configuration array containing plugin instance information.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   An instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('nys_senator_dashboard.managed_senators_handler'),
      $container->get('title_resolver'),
      $container->get('current_route_match'),
      $container->get('file_url_generator'),
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.active_trail'),
      $container->get('request_stack'),
      $container->get('router.route_provider'),
      $container->get('system.breadcrumb.default'),
      $container->get('nys_senator_dashboard.senator_dashboard_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $senator_image_url = $this->getSenatorImageUrl();
    $breadcrumbs = $this->getBreadcrumbs();
    $header_title = $this->getHeaderTitle();
    $header_blurb = $this->getHeaderBlurb();
    $homepage_url = $this->getHomepageUrl();
    return [
      '#theme' => 'nys_senator_dashboard__senator_dashboard_header_block',
      '#senator_image_url' => $senator_image_url,
      '#breadcrumbs' => $breadcrumbs,
      '#header_title' => $header_title,
      '#header_blurb' => $header_blurb,
      '#homepage_url' => $homepage_url,
    ];
  }

  /**
   * Helper method to get the senator image URL.
   *
   * @return string
   *   The image URL.
   */
  private function getSenatorImageUrl(): string {
    $senator_image_url = '';
    if ($this->configuration['display_image']) {
      $senator_tid = $this->managedSenatorsHandler->getActiveSenator($this->currentUser->id());
      try {
        $senator = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->load($senator_tid);
      }
      catch (\Throwable) {
      }
      if (isset($senator) && $senator->hasField('field_member_headshot') && !$senator->field_member_headshot->isEmpty()) {
        $headshot_media = $senator->field_member_headshot->entity;
        if ($headshot_media && $headshot_media->hasField('field_image') && !$headshot_media->field_image->isEmpty()) {
          $image_file = $headshot_media->field_image->entity;
          if ($image_file instanceof File) {
            $image_uri = $image_file->getFileUri();
            $senator_image_url = $this->fileUrlGenerator->generateAbsoluteString($image_uri);
          }
        }
      }
    }
    return $senator_image_url;
  }

  /**
   * Helper method to get breadcrumbs.
   *
   * @return array
   *   The breadcrumbs.
   */
  private function getBreadcrumbs(): array {
    $ret = [];
    if ($this->configuration['display_breadcrumbs']) {
      $breadcrumbs = $this->breadcrumbBuilder->build($this->routeMatch);
      $links = $breadcrumbs->getLinks();
      if (!empty($links)) {
        // Replaces 'Home' link with link to active managed senator.
        $active_senator = $this->managedSenatorsHandler->getActiveSenator($this->currentUser->id(), FALSE);
        $links[0] = Link::fromTextAndUrl(
          $active_senator->label(),
          Url::fromUri(
            $this->managedSenatorsHandler->getActiveSenatorHomepageUrl($this->currentUser->id()
          ))
        );
        // Generate new Breadcrumb due to immutability in generation pipeline.
        $new_breadcrumbs = new Breadcrumb();
        $new_breadcrumbs->setLinks($links);
        $ret = $new_breadcrumbs->toRenderable();
      }
    }
    return $ret;
  }

  /**
   * Helper method to get the header title.
   *
   * @return string
   *   The header title.
   */
  private function getHeaderTitle(): string {
    $header_title = 'Senator Dashboard';
    $route = $this->routeMatch->getRouteObject();
    $current_request = $this->requestStack->getCurrentRequest();
    $header_title = $this->titleResolver
      ->getTitle($current_request, $route) ?? $header_title;

    // If configured for use on a contextual view detail page.
    if ($this->configuration['display_header_title_as_contextual_link']) {
      $header_title = 'Detail page';
      $entity = $this->senatorDashboardHelper->getContextualEntity();
      try {
        $title = $entity?->label();
        $url = $entity?->toUrl()->toString();
      }
      catch (\Exception) {
        return $header_title;
      }
      $header_title = '<a href="' . $url . '">' . $title . '</a>';
    }

    return $header_title;
  }

  /**
   * Helper method to get the header blurb.
   *
   * @return string
   *   The header blurb.
   */
  private function getHeaderBlurb(): string {
    $header_blurb = $this->configuration['header_blurb'] ?? '';

    if ($this->configuration['use_blurb_from_entity']) {
      $entity = $this->senatorDashboardHelper->getContextualEntity();
      if ($entity->bundle() === 'bill') {
        $header_blurb = $entity->field_ol_name?->value ?? $header_blurb;
      }
    }

    return $header_blurb;
  }

  /**
   * Helper method to get the homepage URL.
   *
   * @return string
   *   The homepage URL.
   */
  private function getHomepageUrl(): string {
    $homepage_url = '';
    if ($this->configuration['display_homepage_link']) {
      $homepage_url = $this->managedSenatorsHandler->getActiveSenatorHomepageUrl($this->currentUser->id());
    }
    return $homepage_url;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form['display_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Senator Image'),
      '#default_value' => $config['display_image'],
    ];
    $form['display_breadcrumbs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display breadcrumbs'),
      '#default_value' => $config['display_breadcrumbs'],
    ];
    $form['display_header_title_as_contextual_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display header title as link to contextual filter entity'),
      '#default_value' => $config['display_header_title_as_contextual_link'],
    ];
    $form['display_homepage_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Display link to senator's homepage"),
      '#default_value' => $config['display_homepage_link'],
    ];
    $form['use_blurb_from_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use short summary from entity as blurb text"),
      '#default_value' => $config['use_blurb_from_entity'],
    ];
    $form['header_blurb'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header blurb (input will be ignored if using entity blurb)'),
      '#default_value' => $config['header_blurb'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'display_image' => FALSE,
      'display_breadcrumbs' => FALSE,
      'display_header_title_as_contextual_link' => FALSE,
      'display_homepage_link' => FALSE,
      'use_blurb_from_entity' => FALSE,
      'header_blurb' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('display_image', $form_state->getValue('display_image'));
    $this->setConfigurationValue('display_breadcrumbs', $form_state->getValue('display_breadcrumbs'));
    $this->setConfigurationValue('display_header_title_as_contextual_link', $form_state->getValue('display_header_title_as_contextual_link'));
    $this->setConfigurationValue('display_homepage_link', $form_state->getValue('display_homepage_link'));
    $this->setConfigurationValue('use_blurb_from_entity', $form_state->getValue('use_blurb_from_entity'));
    $this->setConfigurationValue('header_blurb', $form_state->getValue('header_blurb'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['user', 'url.path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [
      'user:' . $this->currentUser->id(),
      'tempstore_user:' . $this->currentUser->id(),
    ];
  }

}
