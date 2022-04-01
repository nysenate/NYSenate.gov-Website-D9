<?php

namespace Drupal\geocoder_address\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geocoder_field\Plugin\Field\FieldFormatter\GeocodeFormatter;
use Geocoder\Model\AddressCollection;
use Drupal\Component\Plugin\Exception\PluginException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\geocoder\GeocoderInterface;
use Drupal\geocoder\ProviderPluginManager;
use Drupal\geocoder\DumperPluginManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geocoder_address\AddressService;

/**
 * Plugin implementation of the Geocode formatter.
 *
 * @FieldFormatter(
 *   id = "geocoder_address",
 *   label = @Translation("Geocode address"),
 *   field_types = {
 *     "address",
 *   }
 * )
 */
class AddressGeocodeFormatter extends GeocodeFormatter {

  /**
   * The address service.
   *
   * @var \Drupal\geocoder_address\AddressService
   */
  protected $addressService;

  /**
   * Constructs a GeocodeFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\geocoder\GeocoderInterface $geocoder
   *   The gecoder service.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The provider plugin manager service.
   * @param \Drupal\geocoder\DumperPluginManager $dumper_plugin_manager
   *   The dumper plugin manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\geocoder_address\AddressService $address_service
   *   The Geocoder Address service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    GeocoderInterface $geocoder,
    ProviderPluginManager $provider_plugin_manager,
    DumperPluginManager $dumper_plugin_manager,
    RendererInterface $renderer,
    LinkGeneratorInterface $link_generator,
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    AddressService $address_service
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $geocoder, $provider_plugin_manager, $dumper_plugin_manager, $renderer, $link_generator, $logger_factory, $entity_type_manager);
    $this->addressService = $address_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('geocoder'),
      $container->get('plugin.manager.geocoder.provider'),
      $container->get('plugin.manager.geocoder.dumper'),
      $container->get('renderer'),
      $container->get('link_generator'),
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('geocoder_address.address')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    try {
      $dumper = $this->dumperPluginManager->createInstance($this->getSetting('dumper'));
    }
    catch (PluginException $e) {
      $this->loggerFactory->get('geocoder')->error('No Dumper has been set');
    }
    $providers = $this->getEnabledGeocoderProviders();

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      $address_string = $this->addressService->addressArrayToGeoString($value);
      if ($address_collection = $this->geocoder->geocode($address_string, $providers)) {
        $elements[$delta] = [
          '#markup' => $address_collection instanceof AddressCollection && !$address_collection->isEmpty() ? $dumper->dump($address_collection->first()) : "",
        ];
      }
    }

    return $elements;
  }

}
