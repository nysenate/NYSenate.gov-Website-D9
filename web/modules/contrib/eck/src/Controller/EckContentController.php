<?php

namespace Drupal\eck\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\eck\EckEntityTypeInterface;
use Drupal\eck\Entity\EckEntityBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a content controller for entities.
 *
 * @ingroup eck
 */
class EckContentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an EckContentController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Displays add content link for available entity types.
   *
   * @param \Drupal\eck\EckEntityTypeInterface $eck_entity_type
   *   The request parameters.
   *
   * @return array
   *   The output as a renderable array.
   */
  public function addPage(EckEntityTypeInterface $eck_entity_type) {
    $content = [];
    $bundleStorage = $this->getBundleStorage($eck_entity_type);
    /** @var \Drupal\eck\Entity\EckEntityBundle $bundle */
    foreach ($bundleStorage->loadMultiple() as $bundle) {
      if ($this->entityTypeManager()
        ->getAccessControlHandler($eck_entity_type->id())
        ->createAccess($bundle->type)
      ) {
        $content[$bundle->type] = $bundle;
      }
    }

    return [
      '#theme' => 'eck_content_add_list',
      '#content' => $content,
      '#entity_type' => [
        'id' => $eck_entity_type->id(),
        'label' => $eck_entity_type->label(),
      ],
    ];
  }

  /**
   * Provides the entity submission form.
   *
   * @param \Drupal\eck\EckEntityTypeInterface $eck_entity_type
   *   The entity type.
   * @param string $eck_entity_bundle
   *   The entity type bundle.
   *
   * @return array
   *   The entity submission form.
   */
  public function add(EckEntityTypeInterface $eck_entity_type, $eck_entity_bundle) {
    $bundleStorage = $this->getBundleStorage($eck_entity_type);
    if (!$bundleStorage->load($eck_entity_bundle)) {
      throw new NotFoundHttpException($this->t('Bundle %bundle does not exist', ['%bundle' => $eck_entity_bundle]));
    }

    $entityStorage = $this->entityTypeManager()->getStorage($eck_entity_type->id());

    $entity = $entityStorage->create(['type' => $eck_entity_bundle]);

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Title callback for add page.
   *
   * @param \Drupal\eck\EckEntityTypeInterface $eck_entity_type
   *   The entity type.
   *
   * @return string
   *   The title.
   */
  public function addPageTitle(EckEntityTypeInterface $eck_entity_type) {
    return $this->t('Add %label content', ['%label' => $eck_entity_type->label()]);
  }

  /**
   * Title callback for add page.
   *
   * @param string $eck_entity_bundle
   *   The bundle id.
   *
   * @return string
   *   The title.
   */
  public function addContentPageTitle($eck_entity_bundle) {
    $eck_entity_bundle = EckEntityBundle::load($eck_entity_bundle);
    return $this->t('Add %label content', ['%label' => $eck_entity_bundle->get('name')]);
  }

  /**
   * Retrieves the bundle storage for the given entity type.
   *
   * @param \Drupal\eck\EckEntityTypeInterface $eck_entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The bundle storage.
   */
  private function getBundleStorage(EckEntityTypeInterface $eck_entity_type) {
    $entityTypeBundle = "{$eck_entity_type->id()}_type";
    $bundleStorage = $this->entityTypeManager()->getStorage($entityTypeBundle);
    return $bundleStorage;
  }

}
