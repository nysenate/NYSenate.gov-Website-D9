<?php

namespace Drupal\Tests\location_migration\Traits;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Driver\sqlite\Connection as SQLiteConnection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * Trait for location migration test assertions.
 */
trait LocationMigrationAssertionsTrait {

  /**
   * Taxonomy term 1: entity-level location with cardinality set to 1.
   *
   * @param array $expected_features
   *   An array of the expected features.
   *
   * @see LocationMigrationAssertionsTrait::getDefaultFieldExpectationConfiguration()
   */
  protected function assertTerm1FieldValues(array $expected_features) {
    $expected_features = $expected_features + static::getDefaultFieldExpectationConfiguration();
    $term = $this->container->get('entity_type.manager')->getStorage('taxonomy_term')->load(1);
    assert($term instanceof TermInterface);

    $expected_entity_structure = [
      'tid' => [['value' => 1]],
      'vid' => [['target_id' => 'vocabulary_1']],
      'status' => [['value' => 1]],
      'name' => [['value' => 'Taxonomy Term 1']],
      'description' => [
        [
          'value' => 'Description of Taxonomy Term 1',
          'format' => 'plain_text',
        ],
      ],
      'weight' => [['value' => 0]],
      'parent' => [['target_id' => 0]],
    ];
    if ($expected_features['entity']) {
      $expected_entity_structure['location_taxonomy_term'] = [
        [
          'langcode' => NULL,
          'country_code' => 'BE',
          'administrative_area' => '',
          'locality' => 'Antwerp',
          'dependent_locality' => NULL,
          'postal_code' => '2000',
          'sorting_code' => '',
          'address_line1' => 'Wapper 9-11',
          'address_line2' => '',
          'organization' => 'The Rubens House',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
      ];
      $expected_entity_structure['location_taxonomy_term_geoloc'] = [
        [
          'lat' => '51.220697',
          'lng' => '4.400298',
          'lat_sin' => 0.7795642626726556,
          'lat_cos' => 0.6263222496156742,
          'lng_rad' => 0.07679968816892145,
          'value' => '51.220697, 4.400298',
        ],
      ];

      if ($expected_features['email']) {
        $expected_entity_structure['location_taxonomy_term_email'] = [
          [
            'value' => 'rubenshuis@antwerpen.be',
          ],
        ];
      }

      if ($expected_features['fax']) {
        $expected_entity_structure['location_taxonomy_term_fax'] = [];
      }

      if ($expected_features['phone']) {
        $expected_entity_structure['location_taxonomy_term_phone'] = [
          [
            'value' => '+32 3 201 15 55',
          ],
        ];
      }

      if ($expected_features['www']) {
        $expected_entity_structure['location_taxonomy_term_url'] = [
          [
            'uri' => 'https://www.rubenshuis.be/en',
            'title' => NULL,
            'options' => [],
          ],
        ];
      }
    }

    $this->assertEquals($expected_entity_structure, $this->getImportantEntityProperties($term));
  }

  /**
   * User 2 - entity-level location with cardinality set to 1.
   *
   * @param array $expected_features
   *   An array of the expected features.
   *
   * @see LocationMigrationAssertionsTrait::getDefaultFieldExpectationConfiguration()
   */
  protected function assertUser2FieldValues(array $expected_features) {
    $expected_features = $expected_features + static::getDefaultFieldExpectationConfiguration();
    $user = $this->container->get('entity_type.manager')->getStorage('user')->load(2);
    assert($user instanceof UserInterface);

    $expected_entity_structure = [
      'uid' => [['value' => 2]],
      'name' => [['value' => 'user']],
      'mail' => [['value' => 'user.with.location@drupal7-location.local']],
      'status' => [['value' => 1]],
    ];
    if ($expected_features['entity']) {
      $expected_entity_structure['location_user'] = [
        [
          'langcode' => NULL,
          'country_code' => 'NL',
          'administrative_area' => 'NH',
          'locality' => 'Amsterdam',
          'dependent_locality' => NULL,
          'postal_code' => '1071',
          'sorting_code' => '',
          'address_line1' => 'Paulus Potterstraat 7',
          'address_line2' => '',
          'organization' => 'Van Gogh Museum',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
      ];
      $expected_entity_structure['location_user_geoloc'] = [
        [
          'lat' => '52.358333',
          'lng' => '4.881111',
          'lat_sin' => 0.7918457202558292,
          'lat_cos' => 0.6107211764074725,
          'lng_rad' => 0.08519145810531294,
          'value' => '52.358333, 4.881111',
        ],
      ];

      if ($expected_features['email']) {
        $expected_entity_structure['location_user_email'] = [
          [
            'value' => 'info@vangoghmuseum.nl',
          ],
        ];
      }

      if ($expected_features['fax']) {
        $expected_entity_structure['location_user_fax'] = [
          [
            'value' => '+31 20 570 5201',
          ],
        ];
      }

      if ($expected_features['phone']) {
        $expected_entity_structure['location_user_phone'] = [
          [
            'value' => '+31 20 570 5200',
          ],
        ];
      }

      if ($expected_features['www']) {
        $expected_entity_structure['location_user_url'] = [
          [
            'uri' => 'https://www.vangoghmuseum.nl/en',
            'title' => NULL,
            'options' => [],
          ],
        ];
      }
    }

    $this->assertEquals($expected_entity_structure, $this->getImportantEntityProperties($user));
  }

  /**
   * Assertions of node 1 - this node has only entity-level location, card.: 1.
   *
   * @param array $expected_features
   *   An array of the expected features.
   *
   * @see LocationMigrationAssertionsTrait::getDefaultFieldExpectationConfiguration()
   */
  protected function assertNode1FieldValues(array $expected_features) {
    $expected_features = $expected_features + static::getDefaultFieldExpectationConfiguration();
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(1);
    assert($node instanceof NodeInterface);

    $expected_entity_structure = [
      'nid' => [['value' => 1]],
      'type' => [['target_id' => 'node_type_1']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Node title with "Location node"']],
      'body' => [
        [
          'value' => 'Integer urna nisi, pellentesque id justo ac, vulputate mollis neque. Proin sagittis dignissim eros, et mattis sapien volutpat ut.',
          'summary' => '',
          'format' => 'plain_text',
        ],
      ],
    ];
    if ($expected_features['entity']) {
      $expected_entity_structure['location_node'] = [
        [
          'langcode' => NULL,
          'country_code' => 'HU',
          'administrative_area' => '',
          'locality' => 'Budapest',
          'dependent_locality' => NULL,
          'postal_code' => '1137',
          'sorting_code' => '',
          'address_line1' => '22 Szent István Bvd.',
          'address_line2' => '3/3.',
          'organization' => 'Cheppers Ltd.',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
      ];
      $expected_entity_structure['location_node_geoloc'] = [];

      if ($expected_features['email']) {
        $expected_entity_structure['location_node_email'] = [];
      }

      if ($expected_features['fax']) {
        $expected_entity_structure['location_node_fax'] = [];
      }

      if ($expected_features['phone']) {
        $expected_entity_structure['location_node_phone'] = [];
      }

      if ($expected_features['www']) {
        $expected_entity_structure['location_node_url'] = [];
      }
    }

    $this->assertEquals($expected_entity_structure, $this->getImportantEntityProperties($node));
  }

  /**
   * Node 2: with location field; entity-level location shouldn't be present.
   *
   * @param array $expected_features
   *   An array of the expected features.
   *
   * @see LocationMigrationAssertionsTrait::getDefaultFieldExpectationConfiguration()
   */
  protected function assertNode2FieldValues(array $expected_features) {
    $expected_features = $expected_features + static::getDefaultFieldExpectationConfiguration();
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(2);
    assert($node instanceof NodeInterface);
    $destination_is_sqlite = Database::getConnection() instanceof SQLiteConnection;

    $expected_entity_structure = [
      'nid' => [['value' => 2]],
      'type' => [['target_id' => 'node_type_2']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Node with "Location field"']],
      'body' => [
        [
          'value' => 'Ut eu nibh placerat, condimentum dui eget, efficitur tortor. Duis quis sem elit. Aliquam hendrerit tortor est, ut interdum ante euismod eu.',
          'summary' => '',
          'format' => 'plain_text',
        ],
      ],
      'field_location' => [
        [
          'langcode' => NULL,
          'country_code' => 'GN',
          'administrative_area' => '',
          'locality' => '',
          'dependent_locality' => NULL,
          'postal_code' => '',
          'sorting_code' => '',
          'address_line1' => '',
          'address_line2' => '',
          'organization' => 'Gulf of Guinea',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
      ],
      'field_location_geoloc' => [
        [
          'lat' => $destination_is_sqlite ? '0.0' : '0',
          'lng' => $destination_is_sqlite ? '0.0' : '0',
          'lat_sin' => 0.0,
          'lat_cos' => 1.0,
          'lng_rad' => 0.0,
          'value' => $destination_is_sqlite ? '0.0, 0.0' : '0, 0',
        ],
      ],
    ];

    if ($expected_features['email']) {
      $expected_entity_structure['field_location_email'] = [];
    }

    if ($expected_features['fax']) {
      $expected_entity_structure['field_location_fax'] = [];
    }

    if ($expected_features['phone']) {
      $expected_entity_structure['field_location_phone'] = [];
    }

    if ($expected_features['www']) {
      $expected_entity_structure['field_location_url'] = [];
    }

    $this->assertEquals($expected_entity_structure, $this->getImportantEntityProperties($node));
  }

  /**
   * Node 3: multi-value location field and multi-value entity location.
   *
   * @param array $expected_features
   *   An array of the expected features.
   *
   * @see LocationMigrationAssertionsTrait::getDefaultFieldExpectationConfiguration()
   */
  protected function assertNode3FieldValues(array $expected_features) {
    $expected_features = $expected_features + static::getDefaultFieldExpectationConfiguration();
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(3);
    assert($node instanceof NodeInterface);

    $expected_entity_structure = [
      'nid' => [['value' => 3]],
      'type' => [['target_id' => 'node_type_3']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Node with multiple entity- and field locations']],
      'body' => [
        [
          'value' => 'Aenean in nibh congue, vulputate lectus vel, facilisis arcu.',
          'summary' => '',
          'format' => 'plain_text',
        ],
      ],
      'field_location_multi' => [
        0 => [
          'langcode' => NULL,
          'country_code' => 'DK',
          'administrative_area' => '',
          'locality' => 'København',
          'dependent_locality' => NULL,
          'postal_code' => '',
          'sorting_code' => '',
          'address_line1' => '',
          'address_line2' => '',
          'organization' => 'Københavns Museum',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
        1 => [
          'langcode' => NULL,
          'country_code' => 'DK',
          'administrative_area' => '',
          'locality' => 'København',
          'dependent_locality' => NULL,
          'postal_code' => '',
          'sorting_code' => '',
          'address_line1' => '',
          'address_line2' => '',
          'organization' => 'SMK – Statens Museum for Kunst',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
      ],
      'field_location_multi_geoloc' => [
        0 => [
          'lat' => '55.674351',
          'lng' => '12.572486',
          'lat_sin' => 0.825845943940327,
          'lat_cos' => 0.5638958032095206,
          'lng_rad' => 0.21943127586089178,
          'value' => '55.674351, 12.572486',
        ],
        1 => [
          'lat' => '55.688816',
          'lng' => '12.576142',
          'lat_sin' => 0.8259882798128603,
          'lat_cos' => 0.5636872906246796,
          'lng_rad' => 0.2194950850983447,
          'value' => '55.688816, 12.576142',
        ],
      ],
    ];
    if ($expected_features['entity']) {
      $expected_entity_structure['location_node_3'] = [
        0 => [
          'langcode' => NULL,
          'country_code' => 'DK',
          'administrative_area' => 'CC',
          'locality' => 'København',
          'dependent_locality' => NULL,
          'postal_code' => '1471',
          'sorting_code' => '',
          'address_line1' => 'Ny Vestergade 10.',
          'address_line2' => "Prince's Mansion",
          'organization' => 'National Museum of Denmark',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
        1 => [
          'langcode' => NULL,
          'country_code' => 'DK',
          'administrative_area' => 'FC',
          'locality' => 'Humlebæk',
          'dependent_locality' => NULL,
          'postal_code' => '3050',
          'sorting_code' => '',
          'address_line1' => 'Gl. Strandvej 13.',
          'address_line2' => '',
          'organization' => 'Louisiana Museum of Modern Art',
          'given_name' => NULL,
          'additional_name' => NULL,
          'family_name' => NULL,
        ],
      ];
      $expected_entity_structure['location_node_3_geoloc'] = [
        0 => [
          'lat' => '55.674722',
          'lng' => '12.574722',
          'lat_sin' => 0.8258495952450619,
          'lat_cos' => 0.5638904557035591,
          'lng_rad' => 0.2194703014229664,
          'value' => '55.674722, 12.574722',
        ],
        1 => [
          'lat' => '55.972021',
          'lng' => '12.540894',
          'lat_sin' => 0.8287644054688954,
          'lat_cos' => 0.5595976771107868,
          'lng_rad' => 0.21887989144360173,
          'value' => '55.972021, 12.540894',
        ],
      ];
    }

    if ($expected_features['email']) {
      $expected_entity_structure['field_location_multi_email'] = [];
      if ($expected_features['entity']) {
        $expected_entity_structure['location_node_3_email'] = [['value' => 'post@natmus.dk']];
      }
    }

    if ($expected_features['fax']) {
      $expected_entity_structure['field_location_multi_fax'] = [];
      if ($expected_features['entity']) {
        $expected_entity_structure['location_node_3_fax'] = [];
      }
    }

    if ($expected_features['phone']) {
      $expected_entity_structure['field_location_multi_phone'] = [];
      if ($expected_features['entity']) {
        $expected_entity_structure['location_node_3_phone'] = [['value' => '+4533134411']];
      }
    }

    if ($expected_features['www']) {
      $expected_entity_structure['field_location_multi_url'] = [
        0 => [
          'uri' => 'https://cphmuseum.kk.dk/en',
          'title' => NULL,
          'options' => [],
        ],
        1 => [
          'uri' => 'https://www.smk.dk/en',
          'title' => NULL,
          'options' => [],
        ],
      ];
      if ($expected_features['entity']) {
        $expected_entity_structure['location_node_3_url'] = [
          0 => [
            'uri' => 'https://natmus.dk',
            'title' => NULL,
            'options' => [],
          ],
          1 => [
            'uri' => 'https://louisiana.dk/en',
            'title' => NULL,
            'options' => [],
          ],
        ];
      }
    }

    $this->assertEquals($expected_entity_structure, $this->getImportantEntityProperties($node));
  }

  /**
   * Default expectation config.
   *
   * - Key "entity" refers to whether "location_node", "location_taxonomy" and
   *   "location_user" are enabled on the source.
   * - Key "email" refers to whether "location_email" is enabled on the source.
   * - Key "fax" refers to whether "location_fax" is enabled on the source, and
   *   telephone module (which provides the field type to migrate these data
   *   into) is enabled on the destination.
   * - Key "phone" refers to whether "location_phone" is enabled on the source,
   *   and telephone module is enabled on the destination.
   * - Key "www" refers to whether "location_www" is enabled on the source,
   *   and link module is enabled on the destination.
   *
   * @return array
   *   An array of the assumed features which are available in the source
   *   database.
   */
  public static function getDefaultFieldExpectationConfiguration(): array {
    return [
      'entity' => TRUE,
      'email' => TRUE,
      'fax' => FALSE,
      'phone' => FALSE,
      'www' => FALSE,
    ];
  }

  /**
   * List of node properties whose value shouldn't have to be checked.
   *
   * @var string[]
   */
  protected $nodeUnconcernedProperties = [
    'changed',
    'created',
    'default_langcode',
    'langcode',
    'path',
    'promote',
    'revision_default',
    'revision_log',
    'revision_timestamp',
    'revision_translation_affected',
    'revision_uid',
    'sticky',
    'uuid',
    'vid',
  ];

  /**
   * List of user properties whose value shouldn't have to be checked.
   *
   * @var string[]
   */
  protected $userUnconcernedProperties = [
    'access',
    'changed',
    'created',
    'default_langcode',
    'init',
    'langcode',
    'login',
    'pass',
    'path',
    'preferred_admin_langcode',
    'preferred_langcode',
    'roles',
    'timezone',
    'user_picture',
    'uuid',
  ];

  /**
   * List of taxonomy term properties whose value shouldn't have to be checked.
   *
   * @var string[]
   */
  protected $taxonomyTermUnconcernedProperties = [
    'uuid',
    'revision_id',
    'langcode',
    'path',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'changed',
    'default_langcode',
    'revision_default',
    'revision_translation_affected',
  ];

  /**
   * Filters out unconcerned properties from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity instance.
   *
   * @return array
   *   The important entity property values as array.
   */
  protected function getImportantEntityProperties(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $exploded = explode('_', $entity_type_id);
    $prop_prefix = count($exploded) > 1
      ? $exploded[0] . implode('', array_map('ucfirst', array_slice($exploded, 1)))
      : $entity_type_id;
    $property_filter_preset_property = "{$prop_prefix}UnconcernedProperties";
    $entity_array = $entity->toArray();
    $unconcerned_properties = property_exists(get_class($this), $property_filter_preset_property)
      ? $this->$property_filter_preset_property
      : [
        'uuid',
        'langcode',
        'dependencies',
        '_core',
      ];

    foreach ($unconcerned_properties as $item) {
      unset($entity_array[$item]);
    }

    return $entity_array;
  }

}
