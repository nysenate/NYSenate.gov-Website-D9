<?php

namespace Drupal\nys_school_forms;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\nys_sage\Service\SageApi;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides school-specific district reassignment.
 */
class SchoolFormsService {

  public const int ASSIGN_DISTRICT_SUCCESS = 1;
  public const int ASSIGN_DISTRICT_INVALID_NODE = 2;
  public const int ASSIGN_DISTRICT_INVALID_ADDRESS = 3;
  public const int ASSIGN_DISTRICT_NO_DISTRICT = 4;
  public const int ASSIGN_DISTRICT_SAVE_FAILED = 5;

  public const array ASSIGN_DISTRICT_MESSAGES = [
    1 => 'District assigned successfully',
    2 => 'Invalid node',
    3 => 'Invalid address',
    4 => 'Could not resolve district',
    5 => 'Failed to save entity',
  ];

  /**
   * Constructs the service.
   */
  public function __construct(
    protected SageApi $sageApi,
  ) {}

  /**
   * Reassigns a school's district using its address.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node in the 'school' bundle.
   * @param bool $save
   *   Whether to save the entity after assigning the district.
   *
   * @return int
   *   A result code from the class constants.
   */
  public function reassignDistrict(NodeInterface $node, bool $save = TRUE): int {
    $address = $node->get('field_school_address');
    if ($node->getType() !== 'school' || !($address instanceof FieldItemListInterface)) {
      return static::ASSIGN_DISTRICT_INVALID_NODE;
    }

    try {
      $address = $address->get(0);
    }
    catch (\Throwable) {
      return static::ASSIGN_DISTRICT_INVALID_ADDRESS;
    }
    $district = $address ? $this->sageApi->getDistrictFromAddress($address) : NULL;

    if (!$district instanceof Term) {
      return static::ASSIGN_DISTRICT_NO_DISTRICT;
    }

    try {
      $node->set('field_district', $district);
      if ($save) {
        $node->save();
      }
      return static::ASSIGN_DISTRICT_SUCCESS;
    }
    catch (\Throwable) {
      return static::ASSIGN_DISTRICT_SAVE_FAILED;
    }
  }

}
