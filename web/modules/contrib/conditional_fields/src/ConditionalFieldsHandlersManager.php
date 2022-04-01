<?php

namespace Drupal\conditional_fields;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Manages discovery and instantiation of handler plugins.
 */
class ConditionalFieldsHandlersManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a new ConditionalFieldsHandlersManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/conditional_fields/handler', $namespaces, $module_handler, 'Drupal\conditional_fields\ConditionalFieldsHandlersPluginInterface', 'Drupal\conditional_fields\Annotation\ConditionalFieldsHandler');

    $this->alterInfo('handler_info');
    $this->setCacheBackend($cache_backend, 'handler_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = parent::getDiscovery();
      $this->discovery = new StaticDiscoveryDecorator($this->discovery, [$this, 'registerDefinitions']);
    }
    return $this->discovery;
  }

  /**
   * Callback for registering definitions for constraints shipped with Symfony.
   *
   * @see ConstraintManager::__construct()
   */
  public function registerDefinitions() {
    $discovery = $this->getDiscovery();
    if ($discovery instanceof StaticDiscovery) {
      $discovery->setDefinition('states_handler_string_textfield', [
        'label' => new TranslatableMarkup('String textfield'),
        'class' => '\Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
        'type'  => ['string'],
      ]);
      $discovery->setDefinition('states_handler_string_textarea', [
        'label' => new TranslatableMarkup('String textarea'),
        'class' => '\Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
        'type'  => ['string'],
      ]);
      $discovery->setDefinition('states_handler_text_textfield', [
        'label' => new TranslatableMarkup('Text textfield'),
        'class' => '\Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
        'type'  => ['string'],
      ]);
      $discovery->setDefinition('states_handler_text_textarea', [
        'label' => new TranslatableMarkup('Text textarea'),
        'class' => '\Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
        'type'  => ['string'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    // Remove the default plugin from the array.
    $definitions = parent::getDefinitions();
    unset($definitions['states_handler_default_state']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'states_handler_default_state';
  }

}
