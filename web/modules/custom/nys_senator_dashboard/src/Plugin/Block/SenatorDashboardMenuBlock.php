<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\nys_senator_dashboard\Service\ActiveSenatorManager;
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
   * @var \Drupal\nys_senator_dashboard\Service\ActiveSenatorManager
   */
  protected ActiveSenatorManager $senatorDashboardManager;

  /**
   * Constructs the SenatorDashboardMenuBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ActiveSenatorManager $senator_dashboard_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->senatorDashboardManager = $senator_dashboard_manager;
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
      $container->get('nys_senator_dashboard.active_senator_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user_id = $this->currentUser->id();

    try {
      $current_user = $this->entityTypeManager->getStorage('user')->load($current_user_id);
    }
    catch (\Throwable) {
      return [];
    }

    if (!empty($current_user) && $current_user->hasField('field_senator_multiref')) {
      $managed_senators = $current_user->field_senator_multiref->referencedEntities();

      // Get or set active senator.
      $active_senator_id = $this->senatorDashboardManager->getActiveSenatorForCurrentUser();
      if (!$active_senator_id && count($managed_senators) > 0) {
        $this->senatorDashboardManager->setActiveSenatorForUserId($current_user_id, $managed_senators[0]->id(), FALSE);
        $active_senator_id = $managed_senators[0]->id();
      }

      // Prepare Active Senator menu data.
      foreach ($managed_senators as $senator) {
        $active_senator_links[] = [
          'label' => $senator->label(),
          'url' => Url::fromRoute(
            'nys_senator_dashboard.active_senator.set',
            ['senator_id' => $senator->id()]
          ),
          'is_active' => ($active_senator_id == $senator->id()),
        ];
      }
    }

    if (empty($active_senator_id) || empty($active_senator_links)) {
      return [];
    }

    return [
      '#theme' => 'senator_dashboard_menu_block',
      '#active_senator_links' => $active_senator_links,
      '#manage_senator_links' => $this->getManageSenatorLinks($active_senator_id),
      '#constituent_activity_links' => $this->getConstituentActivityLinks(),
      '#cache' => [
        'contexts' => ['user', 'user.roles'],
        'tags' => [
          'user:' . $current_user_id,
          'tempstore_user:' . $current_user_id,
        ],
      ],
    ];
  }

  /**
   * Prepares Manage Senator menu data.
   */
  private function getManageSenatorLinks(int $active_senator_id): array {
    return [
      'overview' => [
        'label' => $this->t('Manage Content'),
        'link' => [
          'label' => $this->t('Overview'),
          'url' => Url::fromRoute('<front>')->toString(),
        ],
      ],
      'info' => [
        'label' => $this->t('Manage Senator Info'),
        'links' => [
          [
            'label' => $this->t('General info'),
            'url' => "/taxonomy/term/$active_senator_id/edit",
          ],
          [
            'label' => $this->t('Microsites'),
            'url' => '/admin/senator/content/micropages',
          ],
          [
            'label' => $this->t('Committee chair admin'),
            'url' => '/admin/senator/content/committee',
          ],
        ],
      ],
      'content' => [
        'label' => $this->t('Manage Content'),
        'links' => [
          [
            'label' => $this->t('Articles'),
            'url' => '/admin/senator/content/content?type=article&field_category_value=article',
          ],
          [
            'label' => $this->t('Press releases'),
            'url' => '/admin/senator/content/content?type=article&field_category_value=press_release',
          ],
          [
            'label' => $this->t('Events'),
            'url' => '/admin/senator/content/content?type=event',
          ],
          [
            'label' => $this->t('Honoree Profiles'),
            'url' => '/admin/senator/content/content?type=honoree',
          ],
          [
            'label' => $this->t('In the News'),
            'url' => '/admin/senator/content/content?type=in_the_news',
          ],
          [
            'label' => $this->t('Petitions'),
            'url' => '/admin/senator/content/content?type=petition',
          ],
          [
            'label' => $this->t('Videos'),
            'url' => '/admin/senator/content/content?type=video',
          ],
          [
            'label' => $this->t('Promotional Banners'),
            'url' => '/admin/senator/content/promobanners',
          ],
          [
            'label' => $this->t('Add issues to bills'),
            'url' => '/admin/senator/content/bills?type_1=bill',
          ],
          [
            'label' => $this->t('Add issues or images to resolutions'),
            'url' => '/admin/senator/content/bills?type_1=resolution',
          ],
          [
            'label' => $this->t('Questionnaires'),
            'url' => '/admin/senator/content/questionnaires',
          ],
          [
            'label' => $this->t('Questionnaire webform'),
            'url' => '/admin/webform',
          ],
          [
            'label' => $this->t('Create a new webform'),
            'url' => '/admin/webform/add',
          ],
        ],
      ],
    ];
  }

  /**
   * Prepares Constituent Activity menu data.
   */
  private function getConstituentActivityLinks(): array {
    return [
      [
        'label' => $this->t('Overview'),
        'url' => Url::fromRoute('<front>')->toString(),
      ],
      [
        'label' => $this->t('Constituents List'),
        'url' => Url::fromRoute('<front>')->toString(),
      ],
      [
        'label' => $this->t('Issues'),
        'url' => Url::fromRoute('<front>')->toString(),
      ],
      [
        'label' => $this->t('Sponsored Bills'),
        'url' => Url::fromRoute('<front>')->toString(),
      ],
      [
        'label' => $this->t('Responses to Petitions'),
        'url' => Url::fromRoute('<front>')->toString(),
      ],
      [
        'label' => $this->t('Responses to Questionnaires'),
        'url' => Url::fromRoute('<front>')->toString(),
      ],
    ];
  }

}
