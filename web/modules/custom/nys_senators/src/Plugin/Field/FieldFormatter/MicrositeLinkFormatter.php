<?php

namespace Drupal\nys_senators\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\UriLinkFormatter;
use Drupal\Core\Url;
use Drupal\nys_senators\SenatorsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'microsite_link' formatter.
 *
 * @FieldFormatter(
 *   id = "microsite_link",
 *   label = @Translation("Name to Microsite Link"),
 *   field_types = {
 *     "string",
 *     "name"
 *   }
 * )
 */
class MicrositeLinkFormatter extends UriLinkFormatter {

  /**
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * {@inheritDoc}
   */
  public function __construct(SenatorsHelper $helper, $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->helper = $helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
          $container->get('nys_senators.senators_helper'),
          $plugin_id,
          $plugin_definition,
          $configuration['field_definition'],
          $configuration['settings'],
          $configuration['label'],
          $configuration['view_mode'],
          $configuration['third_party_settings']
      );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If the field's entity is not a taxonomy term in bundle 'senator'.
   */
  public function viewElements(FieldItemListInterface $item, $langcode): array {
    // This formatter only applies to the senator bundle of taxonomy terms.
    /**
* @var \Drupal\taxonomy\Entity\Term $entity
*/
    $entity = $item->getEntity();
    if (!(($entity->bundle() == 'senator')
          && ($entity->getEntityTypeId() == 'taxonomy_term'))
      ) {
      throw new ContextException('The microsite_link format may only be applied to a Senator taxonomy term');
    }

    return [
      0 => [
        '#type' => 'link',
        '#url' => Url::fromUri($this->helper->getMicrositeUrl($entity)),
        '#title' => $entity->getName(),
        '#attributes' => ['class' => ['microsite-link']],
      ],
    ];
  }

}
