<?php

namespace Drupal\nys_school_importer;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\State;

/**
 * Helper class for nys_school_importer.
 */
class ImporterHelper {

  const NYS_SCHOOL_IMPORTER_CONTENT_TYPE = 'nys_school_importer_content_type';

  const NYS_SCHOOL_IMPORTER_CONTENT_TYPE_DEFAULT = 'school';

  const NYS_SCHOOL_IMPORTER_DEFAULT_SCHOOL_NAMES_INDEX_NAME = 'nys_school_importer_default_school_names_index_name';

  const NYS_SCHOOL_IMPORTER_DEFAULT_SCHOOL_NAMES_INDEX_VALUE = 4;

  const NYS_SCHOOL_IMPORTER_COUNTY_TAXONOMY_VID = 8;

  const NYS_SCHOOL_IMPORTER_DISTRICT_TAXONOMY_VID = 12;

  /**
   * Default object for database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Default object for extension.path.resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $pathResolver;

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default object for state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Default object for messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Default value for logger.factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
        Connection $connection,
        ExtensionPathResolver $path_resolver,
        EntityTypeManagerInterface $entity_type_manager,
        State $state,
        MessengerInterface $messenger,
        LoggerChannelFactory $logger
    ) {
    $this->connection = $connection;
    $this->pathResolver = $path_resolver;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger;
  }

  /**
   * Calculates an index for each non unique legal_name for all schools.
   *
   * Stores the Number of elements to build a unique school name
   * 1 - only the legal name is required.
   * 2 - legal_name and city  are required.
   * 3 - legal_name, city and grade_organization are required.
   * 4 - legal_name, city, grade_organization and zip are required.
   */
  public function calculateNameIndexes() {
    // Iterate thru all the rows in the nys_school_names table.
    $result = $this->connection->query('SELECT * FROM `nys_school_names` WHERE 1');
    foreach ($result as $record) {
      $this->calculateNameIndex($record);
    }
  }

  /**
   * Calculates an index for each non unique legal_name of a single school.
   */
  public function calculateNameIndex($record) {
    // Processes one row in the nys_school_names table.
    $sid = $record->sid;
    $legal_name = $record->legal_name;
    $grade_organization = $record->grade_organization;
    $city = $record->city;
    $zip = $record->zip;

    $num_keys_required = $this->getNumSchoolsOneKey($legal_name);
    if ($num_keys_required == 1) {
      $this->createSchoolNameIndex($legal_name, 1);
      return;
    }

    $num_keys_required = $this->getNumSchoolsTwoKeys($legal_name, $city);
    if ($num_keys_required == 1) {
      $this->createSchoolNameIndex($legal_name, 2);
      return;
    }

    $num_keys_required = $this->getNumSchoolsThreeKeys($legal_name, $grade_organization, $city);
    if ($num_keys_required == 1) {
      $this->createSchoolNameIndex($legal_name, 3);
      return;
    }

    $num_keys_required = $this->getNumSchoolsFourKeys($legal_name, $grade_organization, $city, $zip);
    if ($num_keys_required == 1) {
      $this->createSchoolNameIndex($legal_name, 4);
      return;
    }

    $this->createSchoolNameIndex($legal_name, 5);
  }

  /**
   * Fetch the number of matching rows using only the legal_name as a key.
   */
  public function getNumSchoolsOneKey($legal_name) {
    $legal_name = addslashes($legal_name);
    return $this->connection->query(
          "SELECT count(*) FROM nys_school_names WHERE legal_name = ':legal_name'", [
            ':legal_name' => $legal_name,
          ]
      )->fetchField();
  }

  /**
   * Fetch the number of matching rows using just two keys.
   */
  public function getNumSchoolsTwoKeys($legal_name, $city) {
    $legal_name = addslashes($legal_name);
    return $this->connection->query(
          "SELECT count(*) FROM nys_school_names WHERE legal_name = ':legal_name' AND city = ':city'", [
            ':legal_name' => $legal_name,
            ':city' => $city,
          ]
      )->fetchField();
  }

  /**
   * Fetch the number of matching rows using just three keys.
   */
  public function getNumSchoolsThreeKeys($legal_name, $grade_organization, $city) {
    $legal_name = addslashes($legal_name);
    return $this->connection->query(
          "SELECT count(*) FROM nys_school_names
      WHERE legal_name = ':legal_name' AND grade_organization = ':grade_organization' AND city = ':city'", [
        ':legal_name' => $legal_name,
        ':grade_organization' => $grade_organization,
        ':city' => $city,
      ]
      )->fetchField();
  }

  /**
   * Fetch the number of matching rows using all four keys.
   */
  public function getNumSchoolsFourKeys($legal_name, $grade_organization, $city, $zip) {
    $legal_name = addslashes($legal_name);
    return $this->connection->query(
          "SELECT count(*) FROM nys_school_names
      WHERE legal_name = ':legal_name' AND grade_organization = ':grade_organization' AND city = ':city' AND zip = ':zip'", [
        ':legal_name' => $legal_name,
        ':grade_organization' => $grade_organization,
        ':city' => $city,
        ':zip' => $zip,
      ]
      )->fetchField();
  }

  /**
   * Fetch the maximum number of keys needed.
   */
  public function getNumMaxNamesIndex() {
    return $this->connection->query("SELECT MAX(num_keys) FROM nys_school_names_index")->fetchField();
  }

  /**
   * Insert data into the nys_school_names.
   */
  public function createSchoolName($legal_name, $grade_organization, $city, $zip) {
    // Check for special ascii characters.
    $legal_name = addslashes($legal_name);
    // Insert columns into nys_school_names.
    $this->connection->insert('nys_school_names')
      ->fields(
              [
                'sid' => NULL,
                'legal_name' => $legal_name,
                'grade_organization' => $grade_organization,
                'city' => $city,
                'zip' => $zip,
              ]
          )
      ->execute();
  }

  /**
   * Stores the school name index (num_keys) for a school.
   */
  public function createSchoolNameIndex($legal_name, $num_keys) {
    if ($num_keys > 1) {
      // Check for special ascii characters.
      $legal_name = addslashes($legal_name);
    }
    // Insert or update the data in in the num_keys comumn.
    if ($num_keys > 1) {
      $this->connection->query(
            "INSERT INTO nys_school_names_index (snid, legal_name, num_keys)
        VALUES (NULL, ':legal_name', ':num_keys') ON DUPLICATE KEY UPDATE num_keys = ':num_keys'", [
          ':legal_name' => $legal_name,
          ':num_keys' => $num_keys,
        ]
        );
    }
  }

  /**
   * Returns school_name_index num_keys to form a unique school name.
   *
   * @param string $legal_name
   *   The legal name.
   *
   * @return int
   *   The school name index key.
   */
  public function getSchoolNameIndexNumKeys($legal_name) {
    $legal_name = addslashes($legal_name);

    // Get the num_keys column.
    $num_keys = $this->connection->query(
          "SELECT `num_keys` FROM nys_school_names_index WHERE legal_name = ':legal_name'", [
            ':legal_name' => $legal_name,
          ]
      )->fetchField();

    if ($num_keys === FALSE) {
      // Return the default value 1 indicating no changes are needed.
      return 1;
    }
    else {
      // Return the number of elements required in the name to make it unique.
      return $num_keys;
    }
  }

  /**
   * Clear The nys_school_names table.
   */
  public function clearNysSchoolNames() {
    // Truncate the nys_school_names.
    $this->connection->query("TRUNCATE nys_school_names");
  }

  /**
   * Clear The nys_school_names table.
   */
  public function clearNysSchoolNamesIndex() {
    // Truncate the nys_school_names.
    $this->connection->query("TRUNCATE nys_school_names_index");
  }

  /**
   * Returns wether the columns in the data file match the schema.
   *
   * Argument $data is an array of column names.
   */
  public function validateFile($data) {
    // Get the mappings.
    $data_mappings = $this->getMappings();
    foreach ($data as $key => $value) {
      $index = $key + 1;
      if ($value != $data_mappings->$index->csv_colname) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Returns the mapping json as an array.
   *
   * @return array|null
   *   The mapping as an array.
   */
  public function getMappings() {
    static $cache = NULL;
    if ($cache === NULL) {
      $mapping_file_path = $this->pathResolver->getPath('module', 'nys_school_importer') . '/nys_school_importer_mapping.json';
      $cache = json_decode(file_get_contents($mapping_file_path));
    }
    return $cache;
  }

  /**
   * Returns the column number of a field.
   *
   * @param string $column_name
   *   The column name.
   *
   * @return bool|string
   *   The csv column name or FALSE.
   */
  public function getColumnNumber($column_name) {
    $file_mappings = $this->getMappings();
    foreach ($file_mappings as $key => $value) {
      if ($column_name == $value->csv_colname) {
        return $key;
      }
    }
    return FALSE;
  }

  /**
   * Returns the csv_colname of a column.
   *
   * @param int $column_number
   *   The column number.
   *
   * @return string
   *   The csv column name.
   */
  public function getCsvColname($column_number) {
    $file_mappings = $this->getMappings();
    return $file_mappings->$column_number->csv_colname;
  }

  /**
   * Returns the drupal_colname of a column.
   *
   * @param int $column_number
   *   The column number.
   *
   * @return string
   *   The drupal_colname column name.
   */
  public function getDrupalColname($column_number) {
    $file_mappings = $this->getMappings();
    return $file_mappings->$column_number->drupal_colname;
  }

  /**
   * Returns the drupal_coltype of a column.
   *
   * @param int $column_number
   *   The column number.
   *
   * @return string
   *   The drupal_coltype column name.
   */
  public function getDrupalColtype($column_number) {
    $file_mappings = $this->getMappings();
    return $file_mappings->$column_number->drupal_coltype;
  }

  /**
   * Returns the drupal_coltitle of a column.
   *
   * @param int $column_number
   *   The column number.
   *
   * @return string
   *   The drupal_coltitle column name.
   */
  public function getDrupalColtitle($column_number) {
    $file_mappings = $this->getMappings();
    return $file_mappings->$column_number->drupal_coltitle;
  }

  /**
   * Loads a school node by $legal_name, $grade_organization, $city, and $zip.
   *
   * @param string $legal_name
   *   The legal name.
   * @param string $grade_organization
   *   The grade organization.
   * @param string $city
   *   The city name.
   * @param string $zip
   *   The zip code.
   *
   * @return object|bool
   *   The node object
   */
  public function loadSchoolNode($legal_name, $grade_organization, $city, $zip) {
    $sql = "
    SELECT `field_data_field_school_address`.`entity_id` FROM `field_data_field_school_address`, `field_data_field_school_legal_name`, `field_data_field_school_grade_organization`, `location`
    WHERE field_data_field_school_legal_name.entity_id = field_data_field_school_grade_organization.entity_id
    AND field_data_field_school_legal_name.entity_id = field_data_field_school_address.entity_id
    AND `field_data_field_school_address`.`field_school_address_lid` = `location`.`lid`
    AND `field_school_legal_name_value` = :legal_name
    AND `field_school_grade_organization_value` = :grade_org_desc
    AND `city` = :city
    AND `postal_code` = :zip";

    $result = $this->connection->query(
          $sql, [
            ':legal_name' => $legal_name,
            ':grade_org_desc' => $grade_organization,
            ':city' => $city,
            ':zip' => $zip,
          ]
      );

    if ($result->rowCount() == 1) {
      $entity_id = $result->fetchObject()->entity_id;
    }

    if (empty($entity_id) == FALSE && is_numeric($entity_id) == TRUE) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $node_array = $node_storage->loadMultiple([$entity_id]);
      if (is_array($node_array) === TRUE && count($node_array) > 0) {
        return $node_array[$entity_id];
      }
    }
    return FALSE;
  }

  /**
   * Creates a new empty school node.
   */
  public function createSchoolNode($data) {
    // Create a new empty node.
    $node = $this->entityTypeManager->getStorage('node')
      ->create(
              [
                'type' => self::NYS_SCHOOL_IMPORTER_CONTENT_TYPE ?? self::NYS_SCHOOL_IMPORTER_CONTENT_TYPE_DEFAULT,
              ]
          );
    $node->save();
    return $node;
  }

  /**
   * Compares the node to the data.
   *
   * If they compare returns TRUE.
   * If they don't compare returns FALSE.
   */
  public function compareSchoolNode(&$node, $data) {
    $return_value = TRUE;
    $location_array = [];
    if (empty($node) == FALSE && is_array($data) == TRUE && count($data) > 0) {
      foreach ($data as $col_index => $col_value) {
        // Set a drupal field.
        $column_number = $col_index + 1;
        $drupal_colname = $this->getDrupalColname($column_number);
        $drupal_coltype = $this->getDrupalColtype($column_number);

        if (empty($drupal_colname) == FALSE) {
          // Only add columns with field names in them some are unused.
          if ($drupal_colname == 'field_school_address') {
            // Build the address array.
            $location_array[$drupal_coltype] = $col_value;
          }
          elseif ($drupal_colname == 'field_school_legal_name') {
            $school_name = $this->cleanupName($col_value);
            if (strcmp(html_entity_decode($node->get($drupal_colname)->getValue()), $school_name) != 0) {
              return FALSE;
            }
          }
          else {
            // Do comparison here for regular fields.
            if (strcmp(html_entity_decode($node->get($drupal_colname)->getValue()), $col_value) != 0) {
              return FALSE;
            }

          }

        } // Column Name Was not Empty

      }// of each col

      if (count($location_array) > 0) {
        // Do comparison here for address fields.
        if (html_entity_decode($node->field_school_address->sub_administrative_area) == $data[$this->getColumnNumber('COUNTY')]) {
          return FALSE;
        }

        if (html_entity_decode($node->field_school_address->thoroughfare) == $data[$this->getColumnNumber('MAILING ADDRESS')]) {
          return FALSE;
        }

        if (html_entity_decode($node->field_school_address->locality) == $data[$this->getColumnNumber('CITY')]) {
          return FALSE;
        }

        if (html_entity_decode($node->field_school_address->administrative_area) == $data[$this->getColumnNumber('STATE')]) {
          return FALSE;
        }

        if (html_entity_decode($node->field_school_address->postal_code) == $data[$this->getColumnNumber('ZIP')]) {
          return FALSE;
        }

      }

    }

    if ($node->hasField('field_district') && !$node->get('field_district')->isEmpty()) {
      // If the district is not set, force an update
      // so a fresh sage lookup can be done.
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sets the node members with the data and saves the node.
   */
  public function updateSchoolNode(&$node, $data) {
    $location_array = [];
    $location_array["country"] = "us";
    $location_array["province"] = "NY";

    if (empty($node) == FALSE && is_array($data) == TRUE && count($data) > 0) {
      // For each item map columns
      // to fields in`nys_school_importer_mapping.json`.
      foreach ($data as $col_index => $col_value) {
        // Set a drupal field.
        $column_number = $col_index + 1;
        $drupal_colname = $this->getDrupalColname($column_number);
        $drupal_coltype = $this->getDrupalColtype($column_number);

        if (empty($drupal_colname) == FALSE) {
          // Only add columns with field names in them some are unused.
          if ($drupal_colname == 'field_school_address') {
            // Build the address array.
            $location_array[$drupal_coltype] = $col_value;
          }
          else {
            $node->$drupal_colname = [
              $drupal_coltype => $col_value,
              'format' => 'plain_text',
              'safe_value' => $col_value,
            ];
          }

        } // Of column not empty.

      } // Of for each column.

      // Final overrides and cleanup.
      $this->cleanup($node, $data, $location_array);

      // Add the $location_array to field_school_address in the node.
      if (count($location_array) > 0) {
        $node->set('field_school_address', $location_array);
      }
    }

    $node->save();
    return $node->id();
  }

  /**
   * The nys_school_importer_cleanup.
   */
  public function cleanup(&$node, $data, &$location_array) {
    // Get the key fields.
    $legal_name = $data[$this->getColumnNumber('LEGAL NAME') - 1];
    $grade_organization = $data[$this->getColumnNumber('GRADE ORGANIZATION DESCRIPTION') - 1];
    $nysed_id = $data[$this->getColumnNumber('SED CODE') - 1];

    $addr1 = $data[$this->getColumnNumber('MAILING ADDRESS') - 1];
    $city = $data[$this->getColumnNumber('CITY') - 1];
    $county_name = $data[$this->getColumnNumber('COUNTY') - 1];
    $state = 'NY';
    $zip5 = $data[$this->getColumnNumber('ZIP') - 1];

    // Get the override value for the school name index.
    $default_override_num_keys = self::NYS_SCHOOL_IMPORTER_DEFAULT_SCHOOL_NAMES_INDEX_NAME ?? '';
    $num_keys = NULL;
    if ($default_override_num_keys !== 0 && $default_override_num_keys !== '0') {
      // If there is no override use 4.
      $num_keys = self::NYS_SCHOOL_IMPORTER_DEFAULT_SCHOOL_NAMES_INDEX_VALUE;
    }
    elseif ($default_override_num_keys === 0 || $default_override_num_keys === '0') {
      // If the override is zero use the built in uniqueness calculated value.
      $num_keys = $this->getSchoolNameIndexNumKeys($legal_name);
    }
    elseif (is_numeric($default_override_num_keys) == TRUE && $default_override_num_keys > 0) {
      // Use the supplied value.
      $num_keys = $this->getSchoolNameIndexNumKeys($legal_name);
    }

    // Massage the name.
    if ($num_keys == 1) {
      $cleaned_up_name = $this->cleanupName($legal_name);
    }
    elseif ($num_keys == 2) {
      $cleaned_up_name = $this->cleanupName($legal_name) . ', ' . ucwords(strtolower($city));
    }
    elseif ($num_keys == 3) {
      $cleaned_up_name = $this->cleanupName($legal_name) . ', ' . $grade_organization . ', ' . ucwords(strtolower($city));
    }
    elseif ($num_keys == 4) {
      $cleaned_up_name = $this->cleanupName($legal_name) . ', ' . $grade_organization . ', ' . ucwords(strtolower($city)) . ', ' . $zip5;
    }
    else {
      $cleaned_up_name = $this->cleanupName($legal_name) . ', ' . $grade_organization . ', ' . ucwords(strtolower($city)) . ', ' . $zip5 . ', ' . rand(1, 9);
    }

    // Set the node title.
    $node->title = $cleaned_up_name;

    // Set the Location name.
    $location_array["name"] = $cleaned_up_name;

    // Get the NYSED data for the SED CODE.
    $nysed_data = $this->getNysedData($nysed_id);
    if ($nysed_data !== FALSE) {
      // Get the data from sage based on the NYSED physical address.
      $sage_data = $this->getSageDistrictAssignData($nysed_data->physical_address_line_1, $nysed_data->city, $nysed_data->state, $nysed_data->zip_code);
    }
    else {
      // Get the data from sage using the mailing address.
      $sage_data = $this->getSageDistrictAssignData($addr1, $city, $state, $zip5);
    }

    // Set the district tid, latitude and longitude.
    if ($sage_data->status == 'SUCCESS') {

      // We are atempting to set the long and lat butit doesnt stick.
      $location_array['inhibit_geocode'] = TRUE;
      $location_array["longitude"] = $sage_data->geocode->lon;
      $location_array["latitude"] = $sage_data->geocode->lat;

      if ($sage_data->districtAssigned == TRUE && is_object($sage_data->districts->senate) == TRUE) {

        $district_name = 'NY Senate District ' . $sage_data->districts->senate->district;
        $district_tid = $this->getDistrictTid($district_name);
        if ($district_tid !== FALSE) {
          $node->field_district->target_id = $district_tid;
        }
      }
      else {
        $this->messenger->addStatus(
              "Sage could not supply district data for %cleaned_up_name.", [
                '%cleaned_up_name' => $cleaned_up_name,
              ]
          );
        $this->loggerFactory->get('nys_school_importer')->notice(var_export($sage_data, TRUE));
      }
    }

    // Set the County tid.
    $county_tid = $this->getCountyTid($county_name);
    if ($county_tid !== FALSE) {
      $node->field_county->target_id = $county_tid;
    }

    // Make the legal school name look nice.
    $node->field_school_legal_name = $this->cleanupName($legal_name);
    $node->save();
  }

  /**
   * Formats legal school names as titles & moves.
   *
   * THE to the beginning if necessary.
   *
   * @param string $raw_message
   *   The name to beautify.
   *
   * @return string
   *   The cleaned up name.
   */
  public function cleanupName($raw_message) {
    if ($this->nameEndsWith($raw_message, ' (THE)') == FALSE) {
      // Just clean it up.
      $massaged_name = ucwords(strtolower($raw_message), " -\t\r\n\f\v");
      if (strncmp($massaged_name, 'Ps', 2) == 0) {
        return substr_replace($massaged_name, 'PS', 0, 2);
      }
      else {
        return $massaged_name;
      }
    }
    else {
      // The name ends with (THE).
      // Move 'The' to the beginning of the name and clean it up.
      return ucwords(strtolower('THE ' . substr($raw_message, 0, strlen($raw_message) - 6)), " -\t\r\n\f\v");
    }
  }

  /**
   * Matches string ending characters.
   */
  public function nameEndsWith($haystack, $needle) {
    // Search forward starting from end minus needle length characters.
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
  }

  /**
   * Restores massaged school name to a legal school name.
   */
  public function restoreLegalSchoolName($schol_name) {
    if (strncasecmp($schol_name, 'The ', 4) == 0) {
      return strtoupper(substr($schol_name, 4, strlen($schol_name) - 4)) . ' (THE)';
    }
    return strtoupper($schol_name);
  }

  /**
   * Gets the tid for a district from the taxonomy term.
   */
  public function getDistrictTid($district_name) {
    // Lookup the district and it's associated Senator, if available.
    $term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(
              [
                'vid' => self::NYS_SCHOOL_IMPORTER_COUNTY_TAXONOMY_VID,
                'name' => $district_name,
              ]
          );

    if (!empty($term)) {
      return $term;
    }

    return FALSE;
  }

  /**
   * Gets the tid for a county from the taxonomy term.
   */
  public function getCountyTid($county_name) {
    // Lookup the district and it's associated Senator, if available.
    $term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(
              [
                'vid' => self::NYS_SCHOOL_IMPORTER_DISTRICT_TAXONOMY_VID,
                'name' => $county_name,
              ]
          );

    if (!empty($term)) {
      return $term;
    }

    return FALSE;
  }

  /**
   * Gets the district assign data structure from sage.
   */
  public function getSageDistrictAssignData($addr1, $city, $state, $zip5) {
    $sage_base_url = 'http://pubgeo.nysenate.gov/api/v2/';
    $sage_district = 'district/assign';

    $sage_parameters =
        '?addr1=' . urlencode($addr1)
        . '&city=' . urlencode($city)
        . '&state=' . urlencode($state)
        . '&zip5=' . urlencode($zip5);

    $response = json_decode(file_get_contents($sage_base_url . $sage_district . $sage_parameters));
    if ($response->status == 'SUCCESS') {
      return $response;
    }
    return FALSE;
  }

  /**
   * Create or Update institution data in SED database based on the sed_code.
   */
  public function insertOrUpdateNysedData(
        $institution_id,
        $popular_name,
        $sed_code,
        $institution_type_desc,
        $institution_sub_type_desc,
        $physical_address_line_1,
        $address_line_2,
        $city,
        $state,
        $zip_code,
        $ceo_first_name,
        $ceo_last_name,
        $ceo_title,
        $ceo_phone_number,
        $senatorial_dist_1,
        $senatorial_dist_2,
        $senatorial_dist_3,
        $senatorial_dist_4,
        $senatorial_dist_5,
        $senatorial_dist_6
    ) {

    // Insert or update UPSERT.
    $sql = "INSERT INTO `nys_school_nysed_data` (`institution_id`, `popular_name`, `sed_code`, `institution_type_desc`, `institution_sub_type_desc`, `physical_address_line_1`, `address_line_2`, `city`, `state`, `zip_code`, `ceo_first_name`, `ceo_last_name`, `ceo_title`, `ceo_phone_number`, `senatorial_dist_1`, `senatorial_dist_2`, `senatorial_dist_3`, `senatorial_dist_4`, `senatorial_dist_5`, `senatorial_dist_6`)
    VALUES (:institution_id, :popular_name, :sed_code, :institution_type_desc, :institution_sub_type_desc, :physical_address_line_1, :address_line_2, :city, :state, :zip_code, :ceo_first_name, :ceo_last_name, :ceo_title, :ceo_phone_number, :senatorial_dist_1, :senatorial_dist_2, :senatorial_dist_3, :senatorial_dist_4, :senatorial_dist_5, :senatorial_dist_6)
    ON DUPLICATE KEY UPDATE `institution_id` = :institution_id, `popular_name` = :popular_name, `institution_type_desc` = :institution_type_desc, `institution_sub_type_desc` = :institution_sub_type_desc, `physical_address_line_1` = :physical_address_line_1, `address_line_2` = :address_line_2, `city` = :city, `state` = :state, `zip_code` = :zip_code, `ceo_first_name` = :ceo_first_name, `ceo_last_name` = :ceo_last_name, `ceo_title` = :ceo_title, `ceo_phone_number` = :ceo_phone_number, `senatorial_dist_1` = :senatorial_dist_1, `senatorial_dist_2` = :senatorial_dist_2, `senatorial_dist_3` = :senatorial_dist_3, `senatorial_dist_4` = :senatorial_dist_4, `senatorial_dist_5` = :senatorial_dist_5, `senatorial_dist_6` = :senatorial_dist_6";

    $values = [
      ':institution_id' => $institution_id,
      ':popular_name' => $popular_name,
      ':sed_code' => $sed_code,
      ':institution_type_desc' => $institution_type_desc,
      ':institution_sub_type_desc' => $institution_sub_type_desc,
      ':physical_address_line_1' => $physical_address_line_1,
      ':address_line_2' => $address_line_2,
      ':city' => $city,
      ':state' => $state,
      ':zip_code' => $zip_code,
      ':ceo_first_name' => $ceo_first_name,
      ':ceo_last_name' => $ceo_last_name,
      ':ceo_title' => $ceo_title,
      ':ceo_phone_number' => $ceo_phone_number,
      ':senatorial_dist_1' => $senatorial_dist_1,
      ':senatorial_dist_2' => $senatorial_dist_2,
      ':senatorial_dist_3' => $senatorial_dist_3,
      ':senatorial_dist_4' => $senatorial_dist_4,
      ':senatorial_dist_5' => $senatorial_dist_5,
      ':senatorial_dist_6' => $senatorial_dist_6,
    ];

    $this->connection->query($sql, $values);
  }

  /**
   * Get the nysed data for a school ID.
   *
   * @param int $nysed_id
   *   The nysed id.
   *
   * @return object
   *   The nysed data.
   */
  public function getNysedData($nysed_id) {
    $result = $this->connection->query(
          "SELECT * FROM `nys_school_nysed_data` WHERE sed_code = :nysed_id", [
            ':nysed_id' => $nysed_id,
          ]
      );
    if ($result->rowCount() == 1) {
      return $result->fetchObject();
    }
    return FALSE;
  }

  /**
   * Get the nysed data size.
   */
  public function getNysedDataCount() {
    return $this->connection->query("SELECT COUNT(*) FROM `nys_school_nysed_data`")->fetchField();
  }

}
