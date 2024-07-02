<?php

declare(strict_types=1);

namespace Drupal\sophron;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\sophron\Event\MapEvent;
use Drupal\sophron\Map\DrupalMap;
use FileEye\MimeMap\Extension;
use FileEye\MimeMap\Map\AbstractMap;
use FileEye\MimeMap\Map\DefaultMap;
use FileEye\MimeMap\MapHandler;
use FileEye\MimeMap\Type;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a sensible mapping between filename extensions and MIME types.
 */
class MimeMapManager implements MimeMapManagerInterface {

  use StringTranslationTrait;

  /**
   * The module configuration settings.
   */
  protected ImmutableConfig $sophronSettings;

  /**
   * The FQCN of the map currently in use.
   */
  protected string $currentMapClass;

  /**
   * The array of initialized map classes.
   *
   * Keyed by FQCN, each value stores the array of initialization errors.
   *
   * @var array
   */
  protected array $initializedMapClasses = [];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EventDispatcherInterface $eventDispatcher,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    $this->sophronSettings = $this->configFactory->get('sophron.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isMapClassValid(string $map_class): bool {
    if (class_exists($map_class) && in_array(AbstractMap::class, class_parents($map_class))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapClass(): string {
    if (!isset($this->currentMapClass)) {
      switch ($this->sophronSettings->get('map_option')) {
        case static::DRUPAL_MAP:
          $this->setMapClass(DrupalMap::class);
          break;

        case static::DEFAULT_MAP:
          $this->setMapClass(DefaultMap::class);
          break;

        case static::CUSTOM_MAP:
          $map_class = $this->sophronSettings->get('map_class');
          $this->setMapClass($this->isMapClassValid($map_class) ? $map_class : DrupalMap::class);
          break;

      }
    }
    return $this->currentMapClass;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapClass(string $map_class): MimeMapManagerInterface {
    $this->currentMapClass = $map_class;
    if (!isset($this->initializedMapClasses[$map_class])) {
      $event = new MapEvent($map_class);
      $this->eventDispatcher->dispatch($event, MapEvent::INIT);
      $this->initializedMapClasses[$map_class] = $event->getErrors();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingErrors(string $map_class): array {
    $this->setMapClass($map_class);
    return $this->initializedMapClasses[$map_class] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function listTypes(): array {
    return MapHandler::map($this->getMapClass())->listTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function getType(string $type): Type {
    return new Type($type, $this->getMapClass());
  }

  /**
   * {@inheritdoc}
   */
  public function listExtensions(): array {
    return MapHandler::map($this->getMapClass())->listExtensions();
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension(string $extension): Extension {
    return new Extension($extension, $this->getMapClass());
  }

  /**
   * {@inheritdoc}
   */
  public function requirements(string $phase): array {
    $is_sophron_guessing = $this->moduleHandler->moduleExists('sophron_guesser');
    return [
      'mime_type_guessing_sophron' => [
        'title' => $this->t('MIME type guessing'),
        'value' => $is_sophron_guessing ? $this->t('Sophron') : $this->t('Drupal core'),
        'description' => $is_sophron_guessing ? $this->t('The <strong>Sophron guesser</strong> module is providing MIME type guessing. <a href=":url">Uninstall the module</a> to revert to Drupal core guessing.', [':url' => Url::fromRoute('system.modules_uninstall')->toString()]) : $this->t('Drupal core is providing MIME type guessing. <a href=":url">Install the <strong>Sophron guesser</strong> module</a> to allow the enhanced guessing provided by Sophron.', [':url' => Url::fromRoute('system.modules_list')->toString()]),
      ],
    ];
  }

}
