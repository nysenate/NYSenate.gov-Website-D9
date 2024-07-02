<?php

namespace Drupal\views_ical\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\Feed;

/**
 * Provides a separate iCal display type.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "ical",
 *   title = @Translation("iCal display"),
 *   help = @Translation("Provides a views display with configuration to set the filename of the downloaded ical .ics file."),
 *   uses_route = TRUE,
 *   admin = @Translation("iCal"),
 *   returns_response = TRUE
 * )
 */
class IcalDisplay extends Feed {

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Since we're childing off the 'path' type, we'll still *call* our
    // category 'page' but let's override it so it says ICS settings.
    $categories['page'] = [
      'title' => $this->t('iCal Settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      $displays = $this->view->storage->get('display');
      if (!empty($displays[$display])) {
        $attach_to = $displays[$display]['display_title'];
      }
    }

    if (!isset($attach_to)) {
      $attach_to = $this->t('None');
    }

    $options['displays'] = array(
      'category' => 'path',
      'title' => $this->t('Attach to'),
      'value' => $attach_to,
    );

    // Add filename to the summary if set.
    if ($this->getOption('filename')) {
      $options['path']['value'] .= $this->t(' (@filename)', ['@filename' => $this->getOption('filename')]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Add the content disposition header if a custom filename has been used.
    if (($response = $this->view->getResponse()) && $this->getOption('filename')) {
      $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->generateFilename($this->getOption('filename')) . '"');
    }

    return parent::render();
  }

  /**
   * Given a filename and a view, generate a filename.
   *
   * @param $filename_pattern
   *   The filename, which may contain replacement tokens.
   * @return string
   *   The filename with any tokens replaced.
   */
  protected function generateFilename($filename_pattern) {
    return $this->globalTokenReplace($filename_pattern);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'path':
        $form['filename'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Filename'),
          '#default_value' => $this->options['filename'],
          '#description' => $this->t('The filename that will be suggested to the browser for downloading purposes. You may include replacement patterns from the list below.'),
        ];
        // Support tokens.
        $this->globalTokenForm($form, $form_state);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'path':
        $this->setOption('filename', $form_state->getValue('filename'));
        break;
    }
  }

  public static function buildResponse($view_id, $display_id, array $args = []) {
    $response = parent::buildResponse($view_id, $display_id, $args);
    $response->headers->set('Content-Type', 'text/calendar');
    return $response;
  }

}
