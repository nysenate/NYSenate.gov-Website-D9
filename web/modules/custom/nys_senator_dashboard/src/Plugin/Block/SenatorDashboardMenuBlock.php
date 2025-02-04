<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Senator Dashboard dynamic menu block.
 */
#[Block(
  id: 'senator_dashboard_menu_block',
  admin_label: new TranslatableMarkup('Senator Dashboard menu block')
)]
class SenatorDashboardMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * Constructs the SenatorDashboardMenuBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ManagedSenatorsHandler $managed_senators_handler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->managedSenatorsHandler = $managed_senators_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('nys_senator_dashboard.managed_senators_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => 'header',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get current user's managed senators and active senator.
    $user_id = $this->currentUser->id();
    $managed_senators = $this->managedSenatorsHandler->getManagedSenators($user_id);
    $active_senator_tid = $this->managedSenatorsHandler->getOrSetActiveSenator($user_id);
    if (empty($managed_senators) || empty($active_senator_tid)) {
      return [];
    }

    // Prepare menu data, depending on plugin "mode".
    $active_senator_links = match($this->configuration['mode']) {
      'header' => $this->getActiveSenatorLinks($managed_senators, $active_senator_tid),
      default => [],
    };
    $manage_senator_links = match($this->configuration['mode']) {
      'header' => $this->getManageSenatorLinks($active_senator_tid),
      'manage_content' => $this->getManageSenatorLinks($active_senator_tid, TRUE),
      default => [],
    };
    $constituent_activity_links = match($this->configuration['mode']) {
      'header' => $this->getConstituentActivityLinks(),
      'constituent_activity' => $this->getConstituentActivityLinks(TRUE),
      default => [],
    };

    return [
      '#theme' => 'senator_dashboard_menu_block',
      '#mode' => $this->configuration['mode'],
      '#active_senator_links' => $active_senator_links,
      '#manage_senator_links' => $manage_senator_links,
      '#constituent_activity_links' => $constituent_activity_links,
      '#cache' => [
        'contexts' => ['user', 'user.roles'],
        'tags' => [
          'user:' . $user_id,
          'tempstore_user:' . $user_id,
        ],
      ],
    ];
  }

  /**
   * Prepares Active Senator menu data.
   *
   * @param array $managed_senators
   *   The current user's managed senator term entities.
   * @param int $active_senator_tid
   *   The current user's active senator TID.
   *
   * @return array
   *   The data to render the links.
   */
  private function getActiveSenatorLinks(array $managed_senators, int $active_senator_tid): array {
    $active_senator_links = [];
    foreach ($managed_senators as $senator) {
      $active_senator_links[] = [
        'label' => $senator->label(),
        'url' => Url::fromRoute(
          'nys_senator_dashboard.active_senator.set',
          ['senator_tid' => $senator->id()]
        ),
        'is_active' => ($active_senator_tid == $senator->id()),
      ];
    }
    return $active_senator_links;
  }

  /**
   * Prepares Manage Senator menu data.
   *
   * @param int $active_senator_tid
   *   User's active senator TID.
   * @param bool $description
   *   Whether to include link descriptions in the return array.
   *
   * @return array
   *   The data to render the links.
   */
  private function getManageSenatorLinks(int $active_senator_tid, bool $description = FALSE): array {
    return [
      'overview' => [
        'label' => $this->t('Manage Content'),
        'link' => [
          'label' => $this->t('Overview'),
          'url' => Url::fromRoute('<front>')->toString(),
        ],
      ],
      'info' => [
        'label' => $this->t("Manage Senator's Information"),
        'links' => [
          [
            'label' => $this->t('General info'),
            'url' => "/taxonomy/term/$active_senator_tid/edit",
            'description' => $description ? $this->t('Edit theme, name, biography, images, contact information & promotional banners.') : '',
          ],
          [
            'label' => $this->t('Microsites'),
            'url' => '/admin/senator/content/micropages',
            'description' => $description ? $this->t('Edit the senator’s microsite pages: homepage, about, contact, events, legislation, newsroom, district, etc.') : '',
          ],
          [
            'label' => $this->t('Committee chair admin'),
            'url' => '/admin/senator/content/committee',
            'description' => $description ? $this->t('Committee chairs can manage committee descriptions, featured legislation & header images.') : '',
          ],
        ],
      ],
      'content' => [
        'label' => $this->t("Manage Senator's Content"),
        'links' => [
          [
            'label' => $this->t('Articles'),
            'url' => '/admin/senator/content/content?type=article&field_category_value=article',
            'description' => $description ? $this->t('View & edit the senator’s articles.') : '',
          ],
          [
            'label' => $this->t('Press releases'),
            'url' => '/admin/senator/content/content?type=article&field_category_value=press_release',
            'description' => $description ? $this->t('View & edit the senator’s press releases.') : '',
          ],
          [
            'label' => $this->t('Events'),
            'url' => '/admin/senator/content/content?type=event',
            'description' => $description ? $this->t('View & edit the senator’s events.') : '',
          ],
          [
            'label' => $this->t('Honoree Profiles'),
            'url' => '/admin/senator/content/content?type=honoree',
            'description' => $description ? $this->t('View & edit the senator’s honoree profiles.') : '',
          ],
          [
            'label' => $this->t('In the News'),
            'url' => '/admin/senator/content/content?type=in_the_news',
            'description' => $description ? $this->t('View & edit the senator’s news posts.') : '',
          ],
          [
            'label' => $this->t('Petitions'),
            'url' => '/admin/senator/content/content?type=petition',
            'description' => $description ? $this->t('View & edit the senator’s petitions.') : '',
          ],
          [
            'label' => $this->t('Videos'),
            'url' => '/admin/senator/content/content?type=video',
            'description' => $description ? $this->t('View & edit the senator’s videos.') : '',
          ],
          [
            'label' => $this->t('Promotional Banners'),
            'url' => '/admin/senator/content/promobanners',
            'description' => $description ? $this->t('View & edit the senator’s promotional banners.') : '',
          ],
          [
            'label' => $this->t('Connect issues to bills'),
            'url' => '/admin/senator/content/bills?type_1=bill',
            'description' => $description ? $this->t('Assign issues to bills the Senator has introduced or sponsored. Issue tags help constituents discover bills & build exposure on NYSenate.gov.') : '',
          ],
          [
            'label' => $this->t('Add issues or images to resolutions'),
            'url' => '/admin/senator/content/bills?type_1=resolution',
            'description' => $description ? $this->t('Add images or issues to resolutions the Senator has introduced or sponsored.') : '',
          ],
          [
            'label' => $this->t('Add & view questionnaires'),
            'url' => '/admin/senator/content/questionnaires',
            'description' => $description ? $this->t('Create a new questionnaire or view all questionnaires.') : '',
          ],
          [
            'label' => $this->t('Manage webforms attached to questionnaires'),
            'url' => '/admin/webform',
            'description' => $description ? $this->t('Manage webforms that have been attached to questionnaires.') : '',
          ],
          [
            'label' => $this->t('Create a new webform'),
            'url' => '/admin/webform/add',
            'description' => $description ? $this->t('Create a new webform.') : '',
          ],
        ],
      ],
    ];
  }

  /**
   * Prepares Constituent Activity menu data.
   *
   * @param bool $description
   *   Whether to include link descriptions in the return array.
   *
   * @return array
   *   The data to render the links.
   */
  private function getConstituentActivityLinks(bool $description = FALSE): array {
    $link_data = [];
    if ($this->configuration['mode'] == 'header') {
      $link_data[] = [
        'label' => $this->t('Overview'),
        'url' => Url::fromRoute('<front>')->toString(),
      ];
    }
    return $link_data + [
      [
        'label' => $this->t('Constituents List'),
        'url' => Url::fromRoute('<front>')->toString(),
        'description' => $description ? $this->t("View the Senator's constituents who have a New York State Senate account.") : '',
      ],
      [
        'label' => $this->t('Bills'),
        'url' => Url::fromRoute('<front>')->toString(),
        'description' => $description ? $this->t('View constituent activity on bills.') : '',
      ],
      [
        'label' => $this->t('Issues'),
        'url' => Url::fromRoute('<front>')->toString(),
        'description' => $description ? $this->t("View issues the Senator's constituents are following.") : '',
      ],
      [
        'label' => $this->t('Responses to Petitions'),
        'url' => Url::fromRoute('<front>')->toString(),
        'description' => $description ? $this->t('View constituent responses to petitions the Senator created.') : '',
      ],
      [
        'label' => $this->t('Responses to Questionnaires'),
        'url' => Url::fromRoute('<front>')->toString(),
        'description' => $description ? $this->t('View constituent responses to questionnaires the Senator created.') : '',
      ],
    ];
  }

}
