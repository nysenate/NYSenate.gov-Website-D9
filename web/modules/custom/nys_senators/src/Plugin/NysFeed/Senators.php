<?php

namespace Drupal\nys_senators\Plugin\NysFeed;

use Drupal\address\Repository\CountryRepository;
use Drupal\address\Repository\SubdivisionRepository;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\FeedState;
use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\nys_feeds\Traits\EntityFormatterTrait;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\nys_senators\Service\Microsites;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * NYS Feeds plugin for active senators.
 *
 * Just built different.  Senators is a consistent-count domain (maximum of 63,
 * less if there is an empty seat) with low volatility.  As such:
 *   - The entire possible result is built and cached,
 *   - Responses will either be the entire result (unfiltered), or the
 *     requested subset (filtered by shortname),
 *   - Base response caching system is overridden in favor of custom caching
 *     found in ::compile().
 */
#[NysFeed(
  label: new TranslatableMarkup("Active Senators"),
  description: new TranslatableMarkup("NYS Feed for active Senators."),
  entity_type: 'taxonomy_term',
  bundle: 'senators',
  id: "senators",
)]
class Senators extends NysFeedPluginBase {

  use EntityFormatterTrait;

  /**
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * Address module Country repository.
   *
   * @var \Drupal\address\Repository\CountryRepository|mixed
   */
  protected mixed $countryRepo;

  /**
   * Address module Subdivision repository.
   *
   * @var \Drupal\address\Repository\SubdivisionRepository
   */
  protected SubdivisionRepository $stateRepo;

  /**
   * NYS Senators Microsites service.
   *
   * @var \Drupal\nys_senators\Service\Microsites
   */
  protected Microsites $themes;

  /**
   * Drupal's Cache Backend service (data bin).
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * {@inheritDoc}
   *
   * Adds services: nys_senator Helper, nys_senator Microsites, Address
   * Country and Subdivision repositories, Cache Backend (data bin).
   */
  public function __construct(SenatorsHelper $helper, Microsites $themes, CountryRepository $countryRepo, SubdivisionRepository $stateRepo, CacheBackendInterface $cache, EntityTypeManagerInterface $entityTypeManager, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($entityTypeManager, $configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
    $this->themes = $themes;
    $this->countryRepo = $countryRepo;
    $this->stateRepo = $stateRepo;
    $this->cache = $cache;
  }

  /**
   * {@inheritDoc}
   *
   * Adds services: nys_senator Helper, nys_senator Microsites, Address
   * Country and Subdivision repositories, Cache Backend (data bin).
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('nys_senators.senators_helper'),
      $container->get('nys_senators.microsites'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('cache.data'),
      $container->get('entity_type.manager'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritDoc}
   *
   * Since senators have a low refresh rate, we build/cache the entire feed and
   * return only the requested names (if applicable).
   */
  public function getFeed(): FeedState {

    // Disable response caching.
    $this->state->setCaching(FALSE);

    $feed = $this->compile();
    $shortnames = array_filter(explode(',', $this->state->params['shortname'] ?? ''));

    $this->state->data = [
      'senators' => count($shortnames)
        ? array_intersect_key($feed, array_flip($shortnames))
        : $feed,
    ];
    $this->state->data['count'] = count($this->state->data['senators']);

    if (!count($this->state->data['senators'])) {
      $this->state->messages[] = "No data found";
    }

    return $this->state;
  }

  /**
   * Retrieves the JSON feed either from cache, or a new compilation.
   */
  protected function compile(): array {
    $feed = $this->cache->get($this->cacheId())?->data;
    if (!$feed) {
      $feed = $this->buildFeed();
      $this->cache->set(
        $this->cacheId(),
        $feed,
        $this->getPluginDefinition()['max_cache_age'] + time(),
        ['taxonomy_term_list:senator']
      );
    }
    return $feed;
  }

  /**
   * Builds a new compilation of the senators feed.
   */
  protected function buildFeed(): array {
    return array_map(
      function ($v) {
        return $this->transcribeEntry($v);
      },
      $this->helper->getActiveSenators('', 'field_ol_shortname')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function query(): array {
    return $this->helper->getActiveSenators(
      $this->state->params['shortname'] ?? '',
      'field_ol_shortname'
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function transcribeEntry(mixed $data): array {
    // Only do work on senator terms.
    if (!(($data instanceof Term) && $data->bundle() == 'senator')) {
      return ['error' => 'Require senator taxonomy terms, received ' . get_class($data)];
    }

    $ret = [];
    if ($district = $this->helper->loadDistrict($data)) {
      // Sort the district number.
      $number = (int) $district->field_district_number->value;
      $ordinal = $this->ordinalSuffix($number);

      // Generate the URLs.
      $district_url = $this->getUrl($district, 'canonical', ['absolute' => TRUE]);
      try {
        $site_url = $this->helper->getMicrositeUrl($data);
      }
      catch (\Throwable) {
        $site_url = '';
      }

      // Collect the images.
      $img = $data->field_member_headshot->entity->field_image->entity ?? '';
      $headshot = $img ? ($img->createFileUrl(FALSE) ?? '') : '';
      $hero_img = $data->field_image_hero->entity->field_image->entity ?? '';
      $banner = $hero_img ? ($hero_img->createFileUrl(FALSE) ?? '') : '';

      // Fetch the palette info, if available.
      $palette_name = $data->field_microsite_theme->value ?? 'default';
      $palette = $this->themes->getTheme($palette_name)
        ?? $this->themes->getTheme('default');

      // Information from OpenLeg.
      $ret['open_leg'] = [
        'member_id' => $data->field_ol_member_id->value ?: -1,
        'shortname' => strtolower($data->field_ol_shortname->value),
        'district' => ['number' => $number, 'ordinal' => $number . $ordinal],
        'is_active' => (bool) $number,
      ];

      // Personal information.
      $ret['profile'] = [
        'name' => [
          'full' => $data->name->value,
          'first' => $data->field_senator_name->given ?? '',
          'last' => $data->field_senator_name->family ?? '',
        ],
        'summary' => $data->field_about->value ?? '',
        'party' => $this->getFlatValue($data->field_party),
        'role' => $data->field_current_duties->value,
        'site' => [
          'url' => $site_url,
          'district_url' => $district_url,
          'palette_name' => $palette_name,
          'palette' => $palette,
          'images' => ['headshot' => $headshot, 'banner' => $banner],
        ],
      ];

      // Contact information.
      $ret['contact'] = [
        'email' => $data->field_email->value,
        'offices' => array_filter(array_map(
          function ($office_ref) {
            return $this->transcribeOffice($office_ref);
          },
          $data->field_offices->referencedEntities()
        )),
      ];

      // Add social media links.
      $source_array = ['facebook', 'twitter', 'youtube', 'instagram'];
      $media = [];
      foreach ($source_array as $val) {
        $prop = "field_{$val}_url";
        $one_val = $data->$prop->value;
        if ($one_val) {
          $media[$val] = $one_val;
        }
      }
      if (count($media)) {
        $ret['social_media'] = $media;
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
   * Flattens a FieldItemList array.
   */
  protected function getFlatValue(FieldItemList $list): array {
    return array_map(
      function ($v) {
        return $v['value'];
      }, $list->getValue()
    );
  }

  /**
   * Transcribes an office field entry to a JSON-suitable array.
   */
  protected function transcribeOffice(Paragraph $office): array {
    try {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
      $address = $office->field_office_address->first();
    }
    catch (\Throwable) {
      $address = NULL;
    }
    $ret = [];
    if ($address && ($country = $address->getCountryCode())) {
      try {
        $country_name = $this->countryRepo->get($country)->getName();
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
        "country" => $country,
        "province_name" => $this->statesList()[$admin_area] ?? '',
        "country_name" => $country_name,
        "fax" => $office->field_fax->value ?? '',
        "phone" => $office->field_office_contact_phone->value ?? '',
      ];
    }
    return $ret;
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

}
