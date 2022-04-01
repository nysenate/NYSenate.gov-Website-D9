<?php

namespace Drupal\site_verify\Controller;

use Drupal\Core\Link;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element\HtmlTag;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Site Verify module routes.
 */
class SiteVerifyController extends ControllerBase {

  /**
   * Controller content callback: Verifications List page.
   *
   * @return string
   *   Render Array
   */
  public function verificationsListPage() {
    // $build['verifications_list'] = array(
    // '#markup' => $this->t('TODO: show list of verifications.'),
    // );
    \Drupal::service('router.builder')->rebuild();

    $engines = \Drupal::service('site_verify_service')->siteVerifyGetEngines();
    $destination = \Drupal::destination()->getAsArray();

    $header = [
      ['data' => $this->t('Engine'), 'field' => 'engine'],
      ['data' => $this->t('Meta tag'), 'field' => 'meta'],
      ['data' => $this->t('File'), 'field' => 'file'],
      ['data' => $this->t('Operations')],
    ];

    $verifications = \Drupal::database()->select('site_verify', 'sv')
      ->fields('sv')
      ->execute();

    $rows = [];
    foreach ($verifications as $verification) {
      $row = ['data' => []];
      $row['data'][] = $engines[$verification->engine]['name'];
      $row['data'][] = $verification->meta ? $this->t('Yes') : $this->t('No');
      $row['data'][] = $verification->file ? Link::fromTextAndUrl($verification->file, Url::fromRoute('site_verify.' . $verification->file)) : $this->t('None');
      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('site_verify.verification_edit', ['site_verify' => $verification->svid]),
        'query' => $destination,
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('site_verify.verification_delete', ['site_verify' => $verification->svid]),
        'query' => $destination,
      ];
      $row['data']['operations'] = [
        'data' => [
          '#theme' => 'links',
          '#links' => $operations,
          '#attributes' => ['class' => ['links', 'inline']],
        ],
      ];
      $rows[] = $row;
    }

    $build['verification_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No verifications available. <a href="@add">Add verification</a>.', ['@add' => Url::fromRoute('site_verify.verification_add')->toString()]),
    ];
    // $build['verification_pager'] = array('#theme' => 'pager');
    return $build;
  }

  /**
   * Controller content callback: Verifications File content.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response containing the Verification File content.
   */
  public function verificationsFileContent($svid) {
    $verification = \Drupal::service('site_verify_service')->siteVerifyLoad($svid);
    if ($verification['file_contents'] && $verification['engine']['file_contents']) {
      $response = new Response();
      $response->setContent($verification['file_contents']);
      return $response;
    }
    else {
      $build = [];
      $build['#title'] = $this->t('Verification page');
      $build['#markup'] = $this->t('This is a verification page for the !title search engine.', [
        '!title' => $verification['engine']['name'],
      ]);

      return $build;
    }
  }

}
