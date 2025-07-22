<?php

namespace Drupal\nys_registration;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_sage\Service\SageApi;
use Drupal\path_alias\AliasManager;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper/service methods relevant to user registration.
 */
class RegistrationHelper {

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * NYS SAGE service.
   *
   * @var \Drupal\nys_sage\Service\SageApi
   */
  protected SageApi $sageApi;

  /**
   * PathAuto's Alias Manager service.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected AliasManager $aliasManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, SageApi $sageApi, AliasManager $aliasManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->sageApi = $sageApi;
    $this->aliasManager = $aliasManager;
  }

  /**
   * Wrapper function around SageApi->getDistrictFromAddress().
   */
  public function getDistrictFromAddress(array $address_parts): ?Term {
    return $this->sageApi->getDistrictFromAddress($address_parts);
  }

  /**
   * Get the Senators' district page.
   *
   * @param \Drupal\taxonomy\Entity\Term $senator
   *   The senators' taxonomy term.
   *
   * @return string
   *   returns the alias for the page
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getMicrositeDistrictAlias(Term $senator): string {
    $district_url = '';
    $nids = $this->entityTypeManager->getStorage('node')->loadByProperties(
      [
        'field_microsite_page_type' => '200001',
        'field_senator_multiref' => $senator->id(),
      ]
    );
    foreach ($nids as $nid => $value) {
      $district_node = $this->entityTypeManager->getStorage('node')->load($nid);
    }
    if (!empty($district_node)) {
      $district_url = $this->aliasManager
        ->getPathByAlias($district_node->toUrl()->toString());
    }
    return $district_url;
  }

  /**
   * Converts a string to a machine name-style string.
   *
   * Any character not an ASCII letter, number, or underscore is
   * replaced with an underscore.  The string is trimmed for leading
   * and trailing spaces and underscores.  Consecutive underscores
   * are reduced to a single character.
   */
  public function convertMachineName(string $s): string {
    $ret = preg_replace('/[^A-Za-z0-9_]/', '_', trim($s));
    return preg_replace('/_{2,}/', '_', trim($ret, '_'));
  }

  /**
   * Generates a unique username, based on first and last names.
   *
   * First name and last name are normalized to machine name style.  If
   * "<first_name>_<last_name>" is already taken, a numeric suffix will
   * be added.  The suffix is incremented until an unused name is found.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Must include expected fields first_name and last_name.
   *
   * @return string
   *   A blank string if first or last name is not populated, or on any
   *   error.  Otherwise, a unique username.
   */
  public function generateUserName(FormStateInterface $formState): string {
    // Get the normalized first and last name.
    $first = $this->convertMachineName($formState->getValue([
      'field_first_name',
      '0',
      'value',
    ], ''));
    $last = $this->convertMachineName($formState->getValue([
      'field_last_name',
      '0',
      'value',
    ], ''));

    $needle = '';
    if ($first && $last) {
      try {
        // Query until no matching username is found.
        $store = $this->entityTypeManager->getStorage('user');
        $i = 0;
        do {
          $needle = "{$first}_$last" . ($i ? "_$i" : '');
          $found = $store->getQuery()
            ->accessCheck(FALSE)
            ->condition('name', $needle)
            ->execute();
          $i++;
        } while ($found);
      }
      catch (\Throwable) {
        $needle = '';
      }
    }

    return $needle;
  }

}
