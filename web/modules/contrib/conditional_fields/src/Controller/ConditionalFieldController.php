<?php

namespace Drupal\conditional_fields\Controller;

use Drupal\conditional_fields\Form\ConditionalFieldFormTab;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

/**
 * Returns responses for conditional_fields module routes.
 */
class ConditionalFieldController extends ControllerBase {

  /**
   * An entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * ConditionalFieldController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FormBuilderInterface $formBuilder, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Show entity types.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityTypeList() {
    $output = [
      '#theme' => 'admin_block_content',
      '#content' => [],
    ];

    foreach ($this->getEntityTypes() as $key => $entityType) {
      $output['#content'][] = [
        'url' => Url::fromRoute('conditional_fields.bundle_list', ['entity_type' => $key]),
        'title' => $entityType->getLabel(),
      ];
    }

    return $output;
  }

  /**
   * Title for fields form.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Entity bundle.
   *
   * @return string
   *   Page title.
   */
  public function formTitle($entity_type, $bundle) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    if (!isset($bundles[$bundle]['label'])) {
      return '';
    }
    return $bundles[$bundle]['label'];
  }

  /**
   * Title for field settings form.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Entity bundle.
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Page title.
   */
  public function editFormTitle($entity_type, $bundle, $field_name) {
    $instances = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    if (!isset($instances[$field_name])) {
      return '';
    }
    $field_instance = $instances[$field_name];
    return $field_instance->getLabel();
  }

  /**
   * Title for bundle list of current entity type.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return string
   *   The title for the bundle list page.
   */
  public function bundleListTitle($entity_type) {
    $type = $this->entityTypeManager->getDefinition($entity_type);
    return $type->getLabel();
  }

  /**
   * Show bundle list of current entity type.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function bundleList($entity_type) {
    $output = [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    if ($bundles) {
      $output['#theme'] = 'admin_block_content';
      foreach ($bundles as $bundle_key => $bundle) {
        $output['#content'][] = [
          'url' => Url::fromRoute('conditional_fields.conditions_list', [
            'entity_type' => $entity_type,
            'bundle' => $bundle_key,
          ]),
          'title' => $bundle['label'],
        ];
      }
    }
    else {
      $output['#type'] = 'markup';
      $output['#markup'] = $this->t("Bundles not found");
    }

    return $output;
  }

  /**
   * Get list of available EntityTypes.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   List of content entity types.
   */
  public function getEntityTypes() {
    $entityTypes = [];

    foreach ($this->entityTypeManager->getDefinitions() as $key => $entityType) {
      if ($entityType instanceof ContentEntityType) {
        $entityTypes[$key] = $entityType;
      }
    }

    return $entityTypes;
  }

  /**
   * Provide arguments for ConditionalFieldFormTab.
   *
   * @param string $node_type
   *   Node type.
   *
   * @return array
   *   Form array.
   */
  public function provideArguments($node_type) {
    return $this->formBuilder->getForm(ConditionalFieldFormTab::class, 'node', $node_type);
  }

  /**
   * Provide arguments for ConditionalFieldFormTab.
   *
   * @param string $media_type
   *   Media type.
   *
   * @return array
   *   Form array.
   */
  public function getMediaEditFormTab($media_type) {
    return $this->formBuilder->getForm(ConditionalFieldFormTab::class, 'media', $media_type);
  }

  /**
   * Provide arguments for ConditionalFieldFormTab.
   *
   * @param string $block_content_type
   *   Block content type.
   *
   * @return array
   *   Form array.
   */
  public function getBlockEditFormTab($block_content_type) {
    return $this->formBuilder->getForm(ConditionalFieldFormTab::class, 'block_content', $block_content_type);
  }

  /**
   * Provide arguments for ConditionalFieldFormTab.
   *
   * @param string $comment_type
   *   Comment type.
   *
   * @return array
   *   Form array.
   */
  public function getCommentEditFormTab($comment_type) {
    return $this->formBuilder->getForm(ConditionalFieldFormTab::class, 'comment', $comment_type);
  }

  /**
   * Provide arguments for ConditionalFieldFormTab.
   *
   * @return array
   *   Form array.
   */
  public function getUserEditFormTab() {
    $user_type = "user";
    return $this->formBuilder->getForm(ConditionalFieldFormTab::class, 'user', $user_type);
  }

}
