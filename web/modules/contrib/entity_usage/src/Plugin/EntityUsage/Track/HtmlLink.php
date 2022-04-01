<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\entity_usage\EntityUsage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tracks usage of entities referenced from regular HTML Links.
 *
 * @EntityUsageTrack(
 *   id = "html_link",
 *   label = @Translation("HTML links"),
 *   description = @Translation("Tracks relationships created with standard links inside formatted text fields."),
 *   field_types = {"text", "text_long", "text_with_summary"},
 * )
 */
class HtmlLink extends TextFieldEmbedBase {

  /**
   * The Drupal Path Validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The public file directory.
   *
   * @var string
   */
  protected $publicFileDirectory;

  /**
   * Constructs the HtmlLink plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\entity_usage\EntityUsage $usage_service
   *   The usage tracking service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The EntityRepositoryInterface service.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The Drupal Path Validator service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperInterface $public_stream
   *   The Public Stream service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityUsage $usage_service, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, EntityRepositoryInterface $entity_repository, PathValidatorInterface $path_validator, StreamWrapperInterface $public_stream) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $usage_service, $entity_type_manager, $entity_field_manager, $config_factory, $entity_repository);
    $this->pathValidator = $path_validator;
    $this->publicFileDirectory = method_exists($public_stream, 'getDirectoryPath') ? $public_stream->getDirectoryPath() : '';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_usage.usage'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('entity.repository'),
      $container->get('path.validator'),
      $container->get('stream_wrapper.public')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parseEntitiesFromText($text) {
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $entities = [];

    // Loop trough all the <a> elements that don't have the LinkIt attributes.
    $xpath_query = "//a[@href != '']";
    foreach ($xpath->query($xpath_query) as $element) {
      /** @var \DOMElement $element */
      try {
        // Get the href value of the <a> element.
        $href = $element->getAttribute('href');

        // Strip off the scheme and host, so we only get the path.
        $site_domains = $this->config->get('site_domains') ?: [];
        foreach ($site_domains as $site_domain) {
          $host_pattern = '{^https?://' . str_replace('.', '\.', $site_domain) . '/}';
          if (\preg_match($host_pattern, $href)) {
            $href = preg_replace($host_pattern, '/', $href);
            break;
          }
        }

        $target_type = $target_id = NULL;

        // Check if the href links to an entity.
        $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($href);
        if ($url && $url->isRouted() && preg_match('/^entity\./', $url->getRouteName())) {
          // Ge the target entity type and ID.
          $route_parameters = $url->getRouteParameters();
          $target_type = array_keys($route_parameters)[0];
          $target_id = $route_parameters[$target_type];
        }
        elseif (\preg_match('{^/?' . $this->publicFileDirectory . '/}', $href)) {
          // Check if we can map the link to a public file.
          $file_uri = preg_replace('{^/?' . $this->publicFileDirectory . '/}', 'public://', urldecode($href));
          $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $file_uri]);
          if ($files) {
            // File entity found.
            $target_type = 'file';
            $target_id = array_keys($files)[0];
          }
        }

        if ($target_type && $target_id) {
          $entity = $this->entityTypeManager->getStorage($target_type)->load($target_id);
          if ($entity) {

            if ($element->hasAttribute('data-entity-uuid')) {
              // Normally the Linkit plugin handles when a element has this
              // attribute, but sometimes users may change the HREF manually and
              // leave behind the wrong UUID.
              $data_uuid = $element->getAttribute('data-entity-uuid');
              // If the UUID is the same as found in HREF, then skip it because
              // it's LinkIt's job to register this usage.
              if ($data_uuid == $entity->uuid()) {
                continue;
              }
            }

            $entities[$entity->uuid()] = $target_type;
          }
        }
      }
      catch (\Exception $e) {
        // Do nothing.
      }
    }

    return $entities;
  }

}
