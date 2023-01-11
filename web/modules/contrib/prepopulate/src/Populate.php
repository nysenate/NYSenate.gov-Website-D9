<?php

namespace Drupal\prepopulate;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Html;

/**
 * Service to populate fields from URL.
 *
 * @package Drupal\prepopulate
 */
class Populate implements PopulateInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The whitelisted element types that can be pre-populated.
   *
   * The default list is intentionally limited to not include radios and
   * checkboxes for security reasons. Site visitors cannot click a link
   * to an admin page that prepopulates additional permissions or settings
   * that are un-noticed and unknowingly aggregated to the site.
   *
   * @var array
   */
  protected $whitelistedTypes = [
    'container',
    'date',
    'datelist',
    'datetime',
    'entity_autocomplete',
    'email',
    'fieldset',
    'inline_entity_form',
    'language_select',
    'machine_name',
    'number',
    'path',
    'select',
    'tel',
    'textarea',
    'text_format',
    'textfield',
    'url',
  ];

  /**
   * Populate constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(RequestStack $request, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->request = $request;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->moduleHandler->alter('prepopulate_whitelist', $this->whitelistedTypes);
  }

  /**
   * {@inheritdoc}
   */
  public function populateForm(array &$form, $request_slice = NULL) {
    if (is_null($request_slice)) {
      $request_slice = $this->request->getCurrentRequest()->query->get('edit');
    }
    if (is_array($request_slice)) {
      foreach (array_keys($request_slice) as $request_variable) {
        if (isset($form[$request_variable])) {
          $element = &$form[$request_variable];
          if (isset($element['widget'][0]['value']['#type'])) {
            $type = $element['widget'][0]['value']['#type'];
          }
          elseif (isset($element['widget'][0]['target_id']['#type'])) {
            $type = $element['widget'][0]['target_id']['#type'];
          }
          elseif (isset($element['widget']['#type'])) {
            $type = $element['widget']['#type'];
          }
          elseif (isset($element['#type'])) {
            $type = $element['#type'];
          }
          if (Element::child($request_variable) && !empty($element) && (empty($type) || in_array($type, $this->whitelistedTypes))) {
            $this->populateForm($element, $request_slice[$request_variable]);
          }
        }
      }
    }
    else {
      // If we don't have a form type, we cannot do anything.
      if (empty($form['#type'])) {
        return;
      }
      // If we already have a value in the form, don't overwrite it.
      if (!empty($form['#value']) && is_scalar($form['#value'])) {
        return;
      }
      // If we don't have access, don't alter it.
      if (isset($form['#access']) && $form['#access'] === FALSE) {
        return;
      }
      $value = Html::escape($request_slice);
      switch ($form['#type']) {
        case 'entity_autocomplete':
          $form['#value'] = $this->formatEntityAutocomplete($value, $form);
          break;

        case 'checkbox':
          $form['#checked'] = $value === 'true';

        default:
          $form['#value'] = $value;
          break;
      }
    }
  }

  /**
   * Check access and properly format an autocomplete string.
   *
   * @param string $value
   *   The value.
   * @param array $element
   *   The form element to populate.
   *
   * @return string
   *   The formatted label if entity exists and view label access is allowed.
   *   Otherwise, the value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function formatEntityAutocomplete($value, array &$element) {
    $entity = $this->entityTypeManager
      ->getStorage($element['#target_type'])
      ->load($value);
    if ($entity && $entity->access('view label')) {
      return "{$entity->label()} ($value)";
    }
    return $value;
  }

}
