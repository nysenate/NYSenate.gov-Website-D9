<?php

namespace Drupal\rain_admin_jumpnav\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element to display Jumpnav.
 *
 * @RenderElement("jumpnav")
 */
class Jumpnav extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderJumpnavElement'],
      ],
      '#cache' => [],
      '#form_display' => NULL,
    ];
  }

  /**
   * Pre-render callback.
   */
  public static function preRenderJumpnavElement($element) {
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $display */
    $display = $element['#form_display'];
    $group_settings = $display->getThirdPartySettings('field_group');
    // Gather top level groups.
    foreach ($group_settings as $name => $data) {
      if ($data['parent_name'] !== '') {
        continue;
      }
      $items[] = [
        'label' => $data['label'],
        'name' => $name,
        'target' => 'edit-' . str_replace('_', '-', $name),
        'weight' => $data['weight'],
      ];
    }

    usort($items, 'static::itemWeightSort');

    // @TODO attach library here.
    $build = [
      '#theme' => 'jumpnav',
      '#items' => $items,
    ];

    return $build;
  }

  /**
   * Usort callback: sort by weight.
   *
   * @param array $a
   *   An item in the jumpnav.
   * @param array $b
   *   An item in the jumpnav.
   */
  private static function itemWeightSort(array $a, array $b) {
    return $a['weight'] > $b['weight'];
  }

}
