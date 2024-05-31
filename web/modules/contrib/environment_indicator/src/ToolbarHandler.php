<?php

namespace Drupal\environment_indicator;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\environment_indicator\Entity\EnvironmentIndicator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Toolbar integration handler.
 */
class ToolbarHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The environment indicator config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The active environment.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $activeEnvironment;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $account;

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * Drupal settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, AccountProxyInterface $account, StateInterface $state, Settings $settings) {
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('environment_indicator.settings');
    $this->activeEnvironment = $config_factory->get('environment_indicator.indicator');
    $this->account = $account;
    $this->state = $state;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ToolbarHandler {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('state'),
      $container->get('settings')
    );
  }

  /**
   * User can access all indicators.
   *
   * @return bool
   */
  public function hasAccessAll(): bool {
    return $this->account->hasPermission('access environment indicator');
  }

  /**
   * User can access a specific indicator.
   *
   * @param $environment
   *
   * @return bool
   */
  public function hasAccessEnvironment($environment): bool {
    return $this->hasAccessAll() || $this->account->hasPermission('access environment indicator ' . $environment);
  }

  /**
   * User can access the indicator for the active environment.
   *
   * @return bool
   */
  public function hasAccessActiveEnvironment(): bool {
    return $this->hasAccessEnvironment($this->activeEnvironment->get('machine'));
  }

  /**
   * Hook bridge.
   *
   * @return array
   *   The environment indicator toolbar items render array.
   *
   * @see hook_toolbar()
   */
  public function toolbar(): array {
    $items['environment_indicator'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    if ($this->hasAccessActiveEnvironment() && $this->externalIntegration('toolbar')) {

      $title = $this->getTitle();

      $items['environment_indicator'] += [
        '#type' => 'toolbar_item',
        '#cache' => [
          'tags' => Cache::mergeTags(['config:environment_indicator.settings'], $this->getCacheTags()),
        ],
        '#weight' => 125,
        'tab' => [
          '#type' => 'link',
          '#title' => $title,
          '#url' => Url::fromRoute('environment_indicator.settings'),
          '#attributes' => [
            'title' => $this->t('Environments'),
            'class' => ['toolbar-icon', 'toolbar-icon-environment'],
          ],
          '#access' => !empty($title),
        ],
        'tray' => [
          '#heading' => $this->t('Environments menu'),
        ],
        '#attached' => [
          'library' => ['environment_indicator/drupal.environment_indicator'],
          'drupalSettings' => [
            'environmentIndicator' => [
              'name' => $title ?: ' ',
              'fgColor' => $this->activeEnvironment->get('fg_color'),
              'bgColor' => $this->activeEnvironment->get('bg_color'),
              'addFavicon' => $this->config->get('favicon'),
            ],
          ],
        ]
      ];

      if ($this->account->hasPermission('administer environment indicator settings')) {
        $items['environment_indicator']['tray']['configuration'] = [
          '#type' => 'link',
          '#title' => $this->t('Configure'),
          '#url' => Url::fromRoute('environment_indicator.settings'),
          '#options' => [
            'attributes' => ['class' => ['edit-environments']],
          ],
        ];
      }

      if ($links = $this->getLinks()) {
        $items['environment_indicator']['tray']['environment_links'] =  [
          '#theme' => 'links__toolbar_shortcuts',
          '#links' => $links,
          '#attributes' => [
            'class' => ['toolbar-menu'],
          ],
        ];
      }
    }

    return $items;
  }

  /**
   * Retrieve the current release from the state or deployment_identifier.
   *
   * @return string|null
   */
  public function getCurrentRelease() {
    $current_release = $this->state->get('environment_indicator.current_release');
    if ($current_release !== NULL) {
      return (string) $current_release;
    }

    $deployment_identifier = $this->settings->get('deployment_identifier');
    if ($deployment_identifier !== NULL) {
      return (string) $deployment_identifier;
    }

    return NULL;
  }

  /**
   * Construct the title for the active environment.
   *
   * @return string|null
   */
  public function getTitle(): ?string {
    $environment = $this->activeEnvironment->get('name');
    $release = $this->getCurrentRelease();
    return ($release) ? '(' . $release . ') ' . $environment : $environment;
  }

  /**
   * Helper function that checks if there is external integration.
   *
   * @param $integration
   *   Name of the integration: toolbar, admin_menu, ...
   *
   * @return bool
   *   TRUE if integration is enabled. FALSE otherwise.
   */
  public function externalIntegration($integration): bool {
    if ($integration == 'toolbar') {
      if ($this->moduleHandler->moduleExists('toolbar')) {
        $toolbar_integration = $this->config->get('toolbar_integration') ?? [];
        if (in_array('toolbar', $toolbar_integration)) {
          if ($this->account->hasPermission('access toolbar')) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Get the cache tags for the environment indicator switcher.
   *
   * @return array
   *   The cache tags.
   */
  public function getCacheTags(): array {
    /* @var EnvironmentIndicator[] $environment_entities */
    if (!$environment_entities = EnvironmentIndicator::loadMultiple()) {
      return [];
    }

    $cache_tags = [];
    foreach ($environment_entities as $entity) {
      $cache_tags = Cache::mergeTags($cache_tags, $entity->getCacheTags());
    }

    return $cache_tags;
  }

  /**
   * Get all the links for the switcher.
   *
   * @return array
   */
  public function getLinks(): array {
    if (!$environment_entities = EnvironmentIndicator::loadMultiple()) {
      return [];
    }

    $current = Url::fromRoute('<current>');
    $current_path = $current->toString();
    $environment_entities = array_filter(
      $environment_entities,
      function (EnvironmentIndicator $entity) {
        return $entity->status()
          && !empty($entity->getUrl())
          && $this->hasAccessEnvironment($entity->id());
      }
    );

    $links = array_map(
      function (EnvironmentIndicator $entity) use ($current_path) {
        return [
          'attributes' => [
            'style' => 'color: ' . $entity->getFgColor() . '; background-color: ' . $entity->getBgColor() . ';',
            'title' => $this->t('Opens the current page in the selected environment.'),
          ],
          'title' => $this->t('Open on @label', ['@label' => $entity->label()]),
          'url' => Url::fromUri($entity->getUrl() . $current_path),
          'type' => 'link',
          'weight' => $entity->getWeight(),
        ];
      },
      $environment_entities
    );

    if (!$links) {
      return [];
    }

    uasort($links, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $links;
  }
}
