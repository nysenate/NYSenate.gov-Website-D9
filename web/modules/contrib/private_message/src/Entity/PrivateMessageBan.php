<?php

namespace Drupal\private_message\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the Private Message Ban entity type.
 *
 * This is a lightweight entity type used to store banned users.
 *
 * @ingroup private_message
 *
 * @ContentEntityType(
 *   id = "private_message_ban",
 *   label = @Translation("Private Message Ban"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\private_message\Entity\Builder\PrivateMessageBanListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\private_message\Form\PrivateMessageBanForm",
 *       "add" = "Drupal\private_message\Form\PrivateMessageBanForm",
 *       "edit" = "Drupal\private_message\Form\PrivateMessageBanForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\private_message\Entity\Access\PrivateMessageBanAccessControlHandler",
 *   },
 *   base_table = "private_message_ban",
 *   admin_permission = "administer private message ban entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "owner",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/private_message_ban/add",
 *     "edit-form" = "/admin/structure/private_message_ban/{private_message_ban}/edit",
 *     "delete-form" = "/admin/structure/private_message_ban/{private_message_ban}/delete",
 *     "collection" = "/admin/structure/private_message_ban",
 *   },
 *   constraints = {
 *     "UniquePrivateMessageBan" = {}
 *   }
 * )
 */
class PrivateMessageBan extends ContentEntityBase implements PrivateMessageBanInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'owner' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('owner')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('owner', $account->id());
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getTarget(): User {
    return $this->get('target')->entity;
  }

  /**
   * {@inheritDoc}
   */
  public function getTargetId(): int {
    return $this->get('target')->target_id;
  }

  /**
   * {@inheritDoc}
   */
  public function setTarget(AccountInterface $user): PrivateMessageBanInterface {
    return $this->set('target', $user->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owned by'))
      ->setDescription(t('The ID of user who performed the ban.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);

    $fields['target'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Banned user'))
      ->setDescription(t('The ID of user being banned'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return new TranslatableMarkup('Private Message Ban by @username', ['@username' => $this->getOwner()->getDisplayName()]);
  }

}
