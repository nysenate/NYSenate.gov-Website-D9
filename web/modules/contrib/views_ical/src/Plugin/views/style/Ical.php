<?php

namespace Drupal\views_ical\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Url;

/**
 * Style plugin to render an iCal feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "ical",
 *   title = @Translation("Legacy iCal style"),
 *   help = @Translation("Display the results as an iCal feed. This is the Legacy version, it is recommended that you use the iCal Wizard Style."),
 *   theme = "views_view_ical",
 *   display_types = {"feed"}
 * )
 */
class Ical extends StylePluginBase {
  protected $usesFields = TRUE;
  protected $usesGrouping = FALSE;
  protected $usesRowPlugin = TRUE;
  protected $usesOptions = FALSE;

  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    $this->view->feedIcons[] = [];

    // Attach a link to the iCal feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => 'application/calendar',
      'href' => $url,
      'title' => $title,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (empty($this->view->rowPlugin)) {
      trigger_error('Drupal\views_ical\Plugin\views\style\Ical: Missing row plugin', E_WARNING);
      return [];
    }

    // Run the parent render to
    $parent_render = parent::render();

    $rows = [];

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    ];
    unset($this->view->row_index);
    return $parent_render;
  }

}
