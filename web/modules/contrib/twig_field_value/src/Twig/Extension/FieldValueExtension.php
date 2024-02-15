<?php

namespace Drupal\twig_field_value\Twig\Extension;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Security\DoTrustedCallbackTrait;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides field value filters for Twig templates.
 */
class FieldValueExtension extends AbstractExtension {

  use DoTrustedCallbackTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The twig_field_value logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $loggerChannel;

  /**
   * Constructs a FieldValueExtension.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controllerResolver
   *   The controller resolver.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(LanguageManagerInterface $language_manager, EntityRepositoryInterface $entityRepository, ControllerResolverInterface $controllerResolver, LoggerChannelFactoryInterface $loggerFactory) {
    $this->languageManager = $language_manager;
    $this->entityRepository = $entityRepository;
    $this->controllerResolver = $controllerResolver;
    $this->loggerChannel = $loggerFactory->get('twig_field_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('field_label', [$this, 'getFieldLabel']),
      new TwigFilter('field_value', [$this, 'getFieldValue']),
      new TwigFilter('field_raw', [$this, 'getRawValues']),
      new TwigFilter('field_target_entity', [$this, 'getTargetEntity']),
    ];
  }

  /**
   * Twig filter callback: Only return a field's label.
   *
   * @param array|null $build
   *   Render array of a field.
   *
   * @return string
   *   The label of a field. If $build is not a render array of a field, NULL is
   *   returned.
   */
  public function getFieldLabel($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    if (!$this->accessAllowed($build)) {
      return NULL;
    }

    return $build['#title'] ?? NULL;
  }

  /**
   * Twig filter callback: Only return a field's value(s).
   *
   * @param array|null $build
   *   Render array of a field.
   *
   * @return array
   *   Array of render array(s) of field value(s). If $build is not the render
   *   array of a field, NULL is returned.
   */
  public function getFieldValue($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    $children = $this->getVisibleChildren($build);
    if (empty($children)) {
      return NULL;
    }

    $items = [];
    foreach ($children as $delta => $child) {
      $items[$delta] = $child;
    }

    return $items;
  }

  /**
   * Twig filter callback: Return specific field item(s) value.
   *
   * @param array|null $build
   *   Render array of a field.
   * @param string $key
   *   The name of the field value to retrieve.
   *
   * @return array|null
   *   Single field value or array of field values. If the field value is not
   *   found, null is returned.
   */
  public function getRawValues($build, $key = '') {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    $item_values = $this->getVisibleItemValues($build);
    if (empty($item_values)) {
      return NULL;
    }

    $raw_values = [];
    foreach ($item_values as $delta => $values) {
      if ($key) {
        $raw_value = $values[$key] ?? NULL;
      }
      else {
        $raw_value = $values;
      }
      $raw_values[$delta] = $raw_value;
    }

    return count($raw_values) > 1 ? $raw_values : reset($raw_values);
  }

  /**
   * Twig filter callback: Return the referenced entity.
   *
   * Suitable for entity_reference fields: Image, File, Taxonomy, etc.
   *
   * @param array|null $build
   *   Render array of a field.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\ContentEntityInterface[]|null
   *   A single target entity or an array of target entities. If no target
   *   entity is found, null is returned.
   */
  public function getTargetEntity($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    $visibleChildren = $this->getVisibleChildren($build);
    if (empty($visibleChildren)) {
      return NULL;
    }

    if (!isset($build['#field_name'])) {
      return NULL;
    }

    $parent_key = $this->getParentObjectKey($build);
    if (empty($parent_key)) {
      return NULL;
    }

    // Use the parent object to load the target entity(s) of the field.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = $build[$parent_key];

    $entities = [];
    $fieldItems = $parent->get($build['#field_name']);
    foreach ($fieldItems as $delta => $item) {
      if (isset($item->entity) && $item->entity instanceof EntityInterface) {
        $entity = $this->entityRepository->getTranslationFromContext($item->entity);
        if ($entity->access('view')) {
          $entities[$delta] = $entity;
        }
      }
    }

    // Access control at field item level is not supported.
    // The render array allows access restriction at field item level
    // (i.e. #access = FALSE) but does not provide the data to determine which
    // referenced entity should be blocked.
    if (count($entities) != count($visibleChildren)) {
      $this->loggerChannel->alert('The field_target_entity twig filter does not support access control at field item level. See README.txt for more information. Entity type: %entity_type, bundle: %bundle, field: %field_name', [
        '%entity_type' => $parent->getEntityType()->id(),
        '%bundle' => $parent->bundle(),
        '%field_name' => $fieldItems->getName(),
      ]);
      return NULL;
    }

    return count($entities) > 1 ? $entities : reset($entities);
  }

  /**
   * Check if access is allowed to the render array.
   *
   * Access checks are based on \Drupal\Core\Render\Renderer::doRender.
   *
   * @param array $elements
   *   Render array elements.
   *
   * @return bool
   *   True if access is granted or no access restrictions in place.
   *
   * @see \Drupal\Core\Render\Renderer::doRender
   */
  protected function accessAllowed(array $elements) {
    if (!isset($elements['#access']) && isset($elements['#access_callback'])) {
      $elements['#access'] = $this->doCallback('#access_callback', $elements['#access_callback'], [$elements]);
    }

    if (isset($elements['#access'])) {
      if ($elements['#access'] instanceof AccessResultInterface) {
        if (!$elements['#access']->isAllowed()) {
          return FALSE;
        }
      }
      elseif ($elements['#access'] === FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Returns the children that are accessible.
   *
   * @param array $build
   *   Render array.
   *
   * @return array
   *   Visible children.
   */
  protected function getVisibleChildren(array $build) {

    if (!$this->accessAllowed($build)) {
      return [];
    }

    $elements = Element::children($build);
    if (empty($elements)) {
      return [];
    }

    $children = [];
    foreach ($elements as $delta) {
      if (Element::isVisibleElement($build[$delta])) {
        $children[$delta] = $build[$delta];
      }
    }

    return $children;
  }

  /**
   * Returns item values of visible elements.
   *
   * @param array $build
   *   Render array.
   *
   * @return array
   *   Array of values per child.
   */
  protected function getVisibleItemValues(array $build) {

    $visibleChildren = $this->getVisibleChildren($build);

    if (!isset($build['#items']) || !($build['#items'] instanceof TypedDataInterface)) {
      return [];
    }

    $values = $build['#items']->getValue();

    if (empty($values) || empty($visibleChildren)) {
      return [];
    }

    // Access control at field item level is not supported for entity reference
    // fields. The render array allows access restriction at field item level
    // (i.e. #access = FALSE) but does not provide the data to determine which
    // referenced entity should be blocked.
    if (count($values) != count($visibleChildren)
      && $build['#items'] instanceof EntityReferenceFieldItemListInterface
    ) {
      $this->loggerChannel->alert('The field_raw twig filter does not support access control at field item level for entity reference fields. See README.txt for more information.');
      return [];
    }

    $itemValues = [];
    foreach (array_keys($visibleChildren) as $delta) {
      if (isset($values[$delta])) {
        $itemValues[$delta] = $values[$delta];
      }
    }

    return $itemValues;
  }

  /**
   * Checks whether the render array is a field's render array.
   *
   * @param array|null $build
   *   The render array.
   *
   * @return bool
   *   True if $build is a field render array.
   */
  protected function isFieldRenderArray($build) {

    return isset($build['#theme']) && $build['#theme'] == 'field';
  }

  /**
   * Performs a callback.
   *
   * Based on Renderer::doCallback().
   *
   * @param string $callback_type
   *   The type of the callback. For example, '#post_render'.
   * @param string|callable $callback
   *   The callback to perform.
   * @param array $args
   *   The arguments to pass to the callback.
   *
   * @return mixed
   *   The callback's return value.
   *
   * @see \Drupal\Core\Security\TrustedCallbackInterface
   * @see \Drupal\Core\Render\Renderer::doCallback
   */
  protected function doCallback($callback_type, $callback, array $args) {
    if (is_string($callback)) {
      $double_colon = strpos($callback, '::');
      if ($double_colon === FALSE) {
        $callback = $this->controllerResolver->getControllerFromDefinition($callback);
      }
      elseif ($double_colon > 0) {
        $callback = explode('::', $callback, 2);
      }
    }
    $message = sprintf('Render %s callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was %s. See https://www.drupal.org/node/2966725', $callback_type, '%s');
    return $this->doTrustedCallback($callback, $args, $message, TrustedCallbackInterface::THROW_EXCEPTION, RenderCallbackInterface::class);
  }

  /**
   * Determine the build array key of the parent object.
   *
   * Different field types use different key names.
   *
   * @param array $build
   *   Render array.
   *
   * @return string
   *   The key.
   */
  private function getParentObjectKey(array $build) {
    $options = ['#object', '#field_collection_item'];
    $parent_key = '';

    foreach ($options as $option) {
      if (isset($build[$option])) {
        $parent_key = $option;
        break;
      }
    }

    return $parent_key;
  }

}
