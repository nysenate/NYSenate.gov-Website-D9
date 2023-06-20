<?php

namespace Drupal\nys_accumulator\Plugin\EventInfoGenerator;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\nys_accumulator\EventInfoGeneratorBase;
use Drupal\nys_senators\SenatorsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates the event info for petition-related accumulator events.
 *
 * @EventInfoGenerator(
 *   id = "petition",
 *   requires = { "node:petition" },
 *   content_url = "/node",
 *   fields = {
 *     "name" = "title",
 *     "stub" = "field_title_stub",
 *   }
 * )
 */
class PetitionEventInfo extends EventInfoGeneratorBase {

  /**
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * {@inheritDoc}
   */
  public function __construct(SenatorsHelper $helper, array $definition, string $plugin_id, array $configuration = []) {
    parent::__construct($definition, $plugin_id, $configuration);
    $this->helper = $helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EventInfoGeneratorBase {
    return new static(
          $container->get('nys_senators.senators_helper'),
          $plugin_definition,
          $plugin_id,
          $configuration
      );
  }

  /**
   * Add the petition's owner and their district.
   */
  protected function extraBuild(ContentEntityBase $source, array &$ret): void {
    /**
     * @var \Drupal\taxonomy\Entity\Term $sponsor
*/
    $sponsor = $source->field_senator_multiref->entity;
    $district = $this->helper->loadDistrict($sponsor);
    $ret['senator_shortname'] = $sponsor?->field_ol_shortname?->value ?? '';
    $ret['district_number'] = $district?->field_district_number?->value ?? 0;
  }

}
