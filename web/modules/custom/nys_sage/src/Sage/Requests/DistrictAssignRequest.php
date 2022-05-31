<?php

namespace Drupal\nys_sage\Sage\Requests;

use Drupal\nys_sage\Sage\Request;

/**
 * Request class for SAGE district/assign method.
 */
class DistrictAssignRequest extends Request {

  /**
   * {@inheritdoc}
   */
  protected static array $knownParams = [
    'addr',
    'addr1',
    'addr2',
    'city',
    'state',
    'zip4',
    'zip5',
    'lat',
    'long',
    'provider',
    'geoProvider',
    'showMembers',
    'showMaps',
    'showMultiMatch',
    'uspsValidate',
    'skipGeocode',
    'districtStrategy',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $group = 'district';

  /**
   * {@inheritdoc}
   */
  protected string $method = 'assign';

}
