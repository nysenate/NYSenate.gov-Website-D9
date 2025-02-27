<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $senator_image_url = $this->getSenatorImageUrl();
    $breadcrumbs = $this->getBreadcrumbs();
    $header_title = $this->getHeaderTitle();
    $homepage_url = $this->getHomepageUrl();
    return [
      '#theme' => 'nys_senator_dashboard__senator_dashboard_header_block',
      '#senator_image_url' => $senator_image_url,
      '#breadcrumbs' => $breadcrumbs,
      '#header_title' => $header_title,
      '#header_blurb' => $config['header_blurb'] ?? '',
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
    $active_senator = $this->managedSenatorsHandler->getActiveSenator($this->currentUser->id(), FALSE);
    $breadcrumbs[] = [
      'title' => $active_senator->label(),
      'url' => $this->managedSenatorsHandler->getActiveSenatorHomepageUrl($this->currentUser->id()),
    ];
    if ($this->configuration['display_breadcrumbs']) {
      $active_trail_ids = $this->menuActiveTrail->getActiveTrailIds('senator-dashboard');
      if (!empty($active_trail_ids)) {
        $active_trail_ids = array_reverse($active_trail_ids);
        foreach ($active_trail_ids as $item) {
          if (empty($item)) {
            continue;
          }
          try {
            $menu_link = $this->menuLinkManager->getDefinition($item);
          }
          catch (PluginNotFoundException) {
          }
          if (isset($menu_link)) {
            $title = $menu_link['title'];
            $url = '';
            if (!empty($menu_link['url'])) {
              $url = Url::fromUri($menu_link['url']);
            }
            elseif (!empty($menu_link['route_name'])) {
              $route_parameters = $menu_link['route_parameters'] ?? [];
              if ($this->routeProvider->getRouteByName($menu_link['route_name'])) {
                $url = Url::fromRoute($menu_link['route_name'], $route_parameters);
              }
            }
            if ($url) {
              $breadcrumbs[] = [
                'title' => $title,
                'url' => $url->toString(),
              ];
            }
          }
        }
      }
    }
    return $breadcrumbs;
  }

  /**
   * Helper method to get the header title.
   *
   * @return string
   *   The header title.
   */
  private function getHeaderTitle(): string {
    $header_title = 'Senator Dashboard';
    if ($this->configuration['display_header_title']) {
      $route = $this->routeMatch->getRouteObject();
      $header_title = $this->titleResolver
        ->getTitle($this->requestStack->getCurrentRequest(), $route) ?? $header_title;
    }
    return $header_title;
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
    $form['display_header_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display header title'),
      '#default_value' => $config['display_header_title'],
    ];
    $form['display_homepage_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Display link to senator's homepage"),
      '#default_value' => $config['display_homepage_link'],
    ];
    $form['header_blurb'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header blurb'),
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
      'display_header_title' => FALSE,
      'display_homepage_link' => FALSE,
      'header_blurb' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('display_image', $form_state->getValue('display_image'));
    $this->setConfigurationValue('display_breadcrumbs', $form_state->getValue('display_breadcrumbs'));
    $this->setConfigurationValue('display_header_title', $form_state->getValue('display_header_title'));
    $this->setConfigurationValue('display_homepage_link', $form_state->getValue('display_homepage_link'));
    $this->setConfigurationValue('header_blurb', $form_state->getValue('header_blurb'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['user'];
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
