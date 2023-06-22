<?php

namespace Drupal\nys_senators\Service;

use Drupal\address\Repository\CountryRepository;
use Drupal\address\Repository\SubdivisionRepository;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Render\RendererInterface;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller to build feed of active senator data.
 */
class SenatorsJson {

  /**
   * The maximum age of the compiled feed before it will be recompiled.
   */
  const NYS_SENATORS_JSON_MAX_CACHE_AGE = 3600;

  /**
   * The cache CID for the senator feed.
   */
  const NYS_SENATORS_JSON_CACHE_CID = 'nys_senators:json_feed';

  /**
   * Canary value for senator shortname to get all senators.
   */
  const NYS_SENATORS_JSON_ALL_SENATORS = '__all';

  /**
   * NYS SenatorsHelper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * Address module's Subdivision Repository service.
   *
   * @var \Drupal\address\Repository\SubdivisionRepository
   */
  protected SubdivisionRepository $stateRepo;

  /**
   * Address module's Country Repository service.
   *
   * @var \Drupal\address\Repository\CountryRepository
   */
  protected CountryRepository $countryRepo;

  /**
   * Drupal's Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * NYS Senators' Microsite Themes service.
   *
   * @var \Drupal\nys_senators\Service\Microsites
   */
  protected Microsites $themes;

  /**
   * Drupal's Backend Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructor.
   */
  public function __construct(SenatorsHelper $helper, Microsites $themes, CacheBackendInterface $cache, CountryRepository $countryRepo, SubdivisionRepository $stateRepo, RendererInterface $renderer) {
    $this->helper = $helper;
    $this->themes = $themes;
    $this->cache = $cache;
    $this->countryRepo = $countryRepo;
    $this->stateRepo = $stateRepo;
    $this->renderer = $renderer;
  }

  /**
   * A wrapper around getFeed() to produce a Drupal JSON response.
   *
   * @see static::getFeed()
   */
  public function getFeedJson(string $shortname = self::NYS_SENATORS_JSON_ALL_SENATORS, bool $refresh = FALSE): JsonResponse {
    return new JsonResponse($this->getFeed($shortname, $refresh), 200);
  }

  /**
   * Gets the active senators as a JSON-able array.
   *
   * @param string $shortname
   *   An optional senator shortname.  If supplied, only that senator will be
   *   included in the feed.
   * @param bool $refresh
   *   If true, cache is ignored and the feed is recompiled.
   */
  public function getFeed(string $shortname = self::NYS_SENATORS_JSON_ALL_SENATORS, bool $refresh = FALSE): array {
    // Load from cache, or get a fresh compilation.
    $cache = $this->cache->get(static::NYS_SENATORS_JSON_CACHE_CID);
    $feed = (!$refresh && $cache) ? $cache?->data : '';
    if (!$feed) {
      $feed = $this->compile();
    }

    // Pick a return based on the value of $shortname.
    if (!$shortname) {
      $shortname = self::NYS_SENATORS_JSON_ALL_SENATORS;
    }
    if ($shortname === self::NYS_SENATORS_JSON_ALL_SENATORS) {
      $ret = $feed;
    }
    else {
      $n = strtolower($shortname);
      $ret = current(
            array_filter(
                $feed,
                function ($s) use ($n) {
                    return $n == $s['short_name'];
                }
            )
        ) ?: [];
    }

    return $ret;
  }

  /**
   * Compile JSON for all active senators.
   */
  public function compile(bool $set_cache = TRUE): array {
    $senators = $this->helper->getActiveSenators();
    $feed = [];
    foreach ($senators as $one_senator) {
      if ($entry = $this->transcribeToArray($one_senator)) {
        $feed[] = $entry;
      }
    }
    if ($set_cache) {
      $this->cache->set(
            static::NYS_SENATORS_JSON_CACHE_CID,
            $feed,
            time() + static::NYS_SENATORS_JSON_MAX_CACHE_AGE,
            ['taxonomy_term_list:senator']
        );
    }
    return $feed;
  }

  /**
   * Transcribes an office field entry to a JSON-suitable array.
   */
  protected function transcribeOffice(Paragraph $office): array {
    try {
      /**
       * @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address
      */
      $address = $office->field_office_address->first();
    }
    catch (\Throwable) {
      $address = NULL;
    }
    $ret = [];
    if ($address && ($country = $address->getCountryCode())) {
      // Some fields are missing from D9's implementation of location.  The
      // Java model does not appear to need them.  Leaving them here for
      // future reference.
      // "lid" => <location_id>.
      // "is_primary" => <0|1>.
      try {
        $country_name = $this->countryRepo
          ->get($country)
          ->getName();
      }
      catch (\Throwable) {
        $country_name = '';
      }
      $admin_area = $address->getAdministrativeArea() ?? '';
      $ret = [
        "name" => $address->getOrganization() ?? '',
        "street" => $address->getAddressLine1() ?? '',
        "additional" => $address->getAddressLine2() ?? '',
        "city" => $address->getLocality() ?? '',
        "province" => $admin_area,
        "postal_code" => $address->getPostalCode() ?? '',
        "country" => $$country,
        "province_name" => $this->statesList()[$admin_area] ?? '',
        "country_name" => $country_name,
        "fax" => $office->field_fax->value ?? '',
        "phone" => $office->field_office_contact_phone->value ?? '',
      ];
    }
    return $ret;
  }

  /**
   * Transcribes a Senator object into a JSON-appropriate array.
   */
  protected function transcribeToArray(Term $senator): array {
    $ret = [];
    if ($district = $this->helper->loadDistrict($senator)) {
      $number = $district->field_district_number->value;
      $ordinal = $this->ordinalSuffix($number);

      $ret['open_leg_id'] = $senator->field_ol_member_id->value ?: -1;
      $ret['senate_district'] = (int) $number;
      $ret['senate_district_ordinal'] = $number . $ordinal;
      $ret['is_active'] = (bool) $ret['senate_district'];
      $ret['full_name'] = $senator->name->value;
      $ret['first_name'] = $senator->field_senator_name->given ?? '';
      $ret['last_name'] = $senator->field_senator_name->family ?? '';
      $ret['short_name'] = strtolower($senator->field_ol_shortname->value);
      $ret['email'] = $senator->field_email->value;
      $ret['party'] = $this->getFlatValue($senator->field_party);
      $ret['role'] = $senator->field_current_duties->value;
      $ret['summary'] = $senator->field_about->value ?? '';

      // Generate the URLs.
      try {
        $ret['senate_district_url'] = $district
          ->toUrl('canonical', ['absolute' => TRUE])
          ->toString();
      }
      catch (\Throwable) {
        $ret['senate_district_url'] = '';
      }
      try {
        $ret['url'] = $this->helper->getMicrositeUrl($senator);
      }
      catch (\Throwable) {
        $ret['url'] = '';
      }

      // Try to collect the images.
      $img = $senator->field_member_headshot->entity->field_image->entity ?? '';
      $ret['img'] = $img ? ($img->createFileUrl(FALSE) ?? '') : '';
      $hero_img = $senator->field_image_hero->entity->field_image->entity ?? '';
      $ret['hero_img'] = $hero_img ? ($hero_img->createFileUrl(FALSE) ?? '') : '';

      // Fetch the palette info, if available.
      $palette_name = $senator->field_microsite_theme->value ?? 'default';
      $ret['palette'] = $this->themes->getTheme($palette_name) ?? $this->themes->getTheme('default');

      // Compile the office information.
      $ret['offices'] = [];
      foreach ($senator->field_offices as $office_ref) {
        if ($office = $this->transcribeOffice($office_ref->entity)) {
          $ret['offices'][] = $office;
        }
      }

      // Add social media links.
      $media_array = ['facebook', 'twitter', 'youtube', 'instagram'];
      foreach ($media_array as $val) {
        $property_name = "field_{$val}_url";
        $one_val = $senator->$property_name->value;
        if ($one_val) {
          $ret['social_media'][] = ['name' => $val, 'url' => $one_val];
        }
      }
    }

    return $ret;
  }

  /**
   * Calculates the ordinal suffix for a number.
   *
   * E.g., to make "2" look like "2nd".
   */
  protected function ordinalSuffix(int $number): string {
    // Check if number is zero.
    if ($number === 0) {
      $os = '';
    }
    // Check for 11, 12, 13.
    elseif (in_array($number % 100, [11, 12, 13])) {
      $os = 'th';
    }
    else {
      $os = match ($number % 10) {
        1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
      };
    }

    return $os;
  }

  /**
   * An array of [<state_abbr> => <state_name>, ...].
   */
  protected function statesList(bool $refresh = FALSE): array {
    static $list = [];
    if (!$list || $refresh) {
      $list = array_map(
            function ($v) {
                return $v->getName();
            },
            $this->stateRepo->getAll(['US'])
        );
    }
    return $list;
  }

  /**
   * Flattens a FieldItemList array.
   */
  protected function getFlatValue(FieldItemList $list): array {
    return array_map(
          function ($v) {
              return $v['value'];
          }, $list->getValue()
      );
  }

}
