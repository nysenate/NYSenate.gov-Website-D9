<?php

namespace Drupal\nys_accumulator;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\nys_accumulator\Service\Accumulator;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\nys_users\UsersHelper;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Allows for management of an accumulator entry prior to recording it.
 */
class AccumulatorEntry {

  use LoggerChannelTrait;

  /**
   * A User entity, presumed to be the current user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user;

  /**
   * A Senator or District entity.
   *
   * Target may be identified by either a senator or district.  In either case,
   * the other facet will be discovered and populated.  This defaults to the
   * district/senator assigned to $this->user.
   *
   * @var \Drupal\taxonomy\Entity\Term|null
   */
  protected ?Term $target;

  /**
   * The message type.  Defaults to 'misc'.
   */
  protected string $type = 'misc';

  /**
   * The message action.  Defaults to an empty string.
   */
  protected string $action = '';

  /**
   * A logger channel for nys_accumulator.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $log;

  /**
   * NYS Senators' Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * Holds metadata for this entry.  The `msg_info` field, event_info key.
   */
  public array $info = [];

  /**
   * Drupal's Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Drupal's Event Dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * Drupal's current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Constructor.
   */
  public function __construct(EventDispatcherInterface $dispatcher, Connection $database, Request $request, SenatorsHelper $helper) {
    $this->dispatcher = $dispatcher;
    $this->database = $database;
    $this->request = $request;
    $this->helper = $helper;
    $this->log = $this->getLogger('nys_accumulator');
  }

  /**
   * Utility method to extract a "key" from an entity.
   */
  protected function getEntityKey(?ContentEntityBase $entity): string {
    if ($entity instanceof ContentEntityBase) {
      return $entity->getEntityTypeId() . ':' . $entity->bundle();
    }
    return '';
  }

  /**
   * Wrapper to return the resolved user's district's taxonomy term.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   A taxonomy term representing a district, or NULL if none could be found.
   */
  public function getUserDistrict(): ?Term {
    try {
      $ret = $this->getUser()->field_district->entity;
    }
    catch (\Throwable) {
      $this->log->warning('Cannot resolve district without a valid user');
      $ret = NULL;
    }
    return $ret;
  }

  /**
   * Getter for the user.
   */
  public function getUser(): User {
    if (!isset($this->user)) {
      $this->setUser();
    }
    return $this->user;
  }

  /**
   * Sets the user for this entry.  Chainable.
   *
   * @see \Drupal\nys_users\UsersHelper
   */
  public function setUser($user = NULL): self {
    // Don't allow resolveUser() to reload if an actual user has been passed.
    $this->user = ($user instanceof User) ? $user : UsersHelper::resolveUser($user);
    return $this;
  }

  /**
   * Getter for the target.
   */
  public function getTarget(): ?Term {
    if (!isset($this->target)) {
      $this->setTarget();
    }
    return $this->target;
  }

  /**
   * Sets the target.  Must be a senator or district taxonomy term.  Chainable.
   *
   * @param \Drupal\taxonomy\Entity\Term|null $target
   *   Must be NULL, or a taxonomy term from the "senator" or "district"
   *   bundle.  If NULL, the user's district will be used.
   */
  public function setTarget(?Term $target = NULL): self {
    $require_entity = ['taxonomy_term:senator', 'taxonomy_term:districts'];
    // If NULL, get the user's district.  User must be populated already.
    if (is_null($target)) {
      $target = $this->getUser()->field_district->entity;
    }

    // If the entity is not a senator or district, report something traceable.
    // This may be a "normal" error, e.g. anonymous and out-of-state users.
    if (!(
      ($target instanceof ContentEntityBase)
      && (in_array($this->getEntityKey($target), $require_entity))
    )) {
      // Unauthenticated users will never have a district.
      if ($this->getUser()->id()) {
        $this->log->warning(
          'Failed to resolve a valid target',
          [
            '@type' => $this->type,
            '@action' => $this->action,
            '@uid' => $this->getUser()->id(),
            '@info' => $this->info,
          ]
        );
      }
      $target = NULL;
    }

    // Set the target and return.
    $this->target = $target;
    return $this;
  }

  /**
   * Getter for Type.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Setter for Type.  Chainable.
   */
  public function setType(string $type): self {
    $this->type = $type;
    return $this;
  }

  /**
   * Getter for Action.
   */
  public function getAction(): string {
    return $this->action;
  }

  /**
   * Setter for Action.  Chainable.
   */
  public function setAction(string $action): self {
    $this->action = $action;
    return $this;
  }

  /**
   * Discovers the district and shortname, given a user, senator, or district.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase|null $entity
   *   An entity with field_district.
   *
   * @return array
   *   An array with two keys: district and shortname.  Values will be zero and
   *   empty string, respectively, if $entity does not resolve to as expected.
   *   Shortname will be "NONE_LOADED" if a senator cannot be identified from
   *   an otherwise valid entity.
   */
  protected function resolveDistrictInfo(?ContentEntityBase $entity): array {
    // Default return.
    $ret = ['district' => 0, 'shortname' => ''];

    // Detect the entity type.  If it is unexpected, report.
    $senator = $district = NULL;
    if ($entity) {
      switch ($key = $this->getEntityKey($entity)) {
        case 'user:user':
          /** @var \Drupal\taxonomy\Entity\Term $district */
          $district = $entity->field_district->entity;
          $senator = $district?->field_senator?->entity;
          break;

        case 'taxonomy_term:senator':
          /** @var \Drupal\taxonomy\Entity\Term $entity */
          $district = $this->helper->loadDistrict($entity);
          $senator = $entity;
          break;

        case 'taxonomy_term:districts':
          $district = $entity;
          $senator = $entity->field_senator->entity;
          break;

        default:
          $this->log->error(
            "Invalid entity (@key), could not resolve district/shortname",
            ['@key' => $key]
          );
          break;
      }
    }

    if ($senator && $district) {
      /** @var \Drupal\taxonomy\Entity\Term $district */
      /** @var \Drupal\taxonomy\Entity\Term $senator */
      $ret = [
        'district' => $district->field_district_number->value ?: 0,
        'shortname' => $senator->field_ol_shortname->value ?: 'NONE_LOADED',
      ];
    }

    return $ret;
  }

  /**
   * Compiles the 'user_info' portion of the entry's info.
   */
  public function compileUserInfo(): array {

    $user = $this->getUser();
    $location = current($user->field_address->getValue()) ?: [];
    $address1 = $location['address_line1'] ?? '';
    $address2 = $location['address_line2'] ?? '';
    return [
      'id' => (int) ($user->id() ?? 0),
      'email' => trim($user->getEmail() ?? ''),
      'first_name' => trim($user->field_first_name->value ?? ''),
      'last_name' => trim($user->field_last_name->value ?? ''),
      'address' => trim($address1 . ($address2 ? ' ' . $address2 : '')),
      'city' => trim($location['locality'] ?? ''),
      'state' => trim($location['administrative_area'] ?? ''),
      'zipcode' => trim($location['postal_code'] ?? ''),
    ];

  }

  /**
   * Compiles the `msg_info` array.
   */
  public function compileInfo(): array {
    $info = [];

    $info['user_info'] = $this->compileUserInfo();
    if ($this->info) {
      $info['event_info'] = $this->info;
    }

    // Useful for all manner of debugging.
    // @todo Create debug options in config.
    $post = array_filter(
      $this->request->request->all(),
      function ($k) {
        return !($k == 'ajax_page_state');
      },
      ARRAY_FILTER_USE_KEY
    );
    $info['request_info'] = [
      'url' => $this->request->getPathInfo(),
      'get' => $this->request->query->all(),
      'post' => $post,
      'referer' => $this->request->server->get('HTTP_REFERER', ''),
    ];

    return $info;
  }

  /**
   * Builds the array of fields for a database INSERT.
   */
  public function compileFields(): array {
    $user = $this->getUser();
    $user_info = $this->resolveDistrictInfo($user);
    $target_info = $this->resolveDistrictInfo($this->getTarget());

    return [
      'user_id' => $user->id(),
      'user_is_verified' => $user->getLastAccessedTime() ? 1 : 0,
      'target_shortname' => $target_info['shortname'],
      'target_district' => $target_info['district'],
      'user_shortname' => $user_info['shortname'],
      'user_district' => $user_info['district'],
      'msg_type' => $this->getType(),
      'msg_action' => $this->getAction(),
      'msg_info' => $this->compileInfo(),
      'created_at' => time(),
    ];
  }

  /**
   * Saves the entry.  Returns the key value of the new row (zero on failure).
   */
  public function save(): int {

    $fields = $this->compileFields();
    $event = Accumulator::createEvent('pre_save', $fields);
    $this->dispatcher->dispatch($event);
    $event->context['msg_info'] = json_encode($event->context['msg_info']);

    try {
      $id = $this->database
        ->insert('accumulator')
        ->fields($event->context)
        ->execute();
    }
    catch (\Throwable $e) {
      $this->log->error(
        "Failed to save accumulator entry",
        ['@msg' => $e->getMessage(), '@fields' => $fields]
      );
      $id = 0;
    }

    return $id;

  }

}
