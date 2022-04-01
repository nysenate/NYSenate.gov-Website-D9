<?php

namespace Drupal\Tests\taxonomy_access_fix\Functional;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestBase;
use Drupal\Tests\taxonomy_access_fix\Traits\TaxonomyAccessFixTestTrait;

/**
 * Tests administrative Taxonomy UI access.
 *
 * @group taxonomy
 */
class VocabularyAccessTest extends TaxonomyTestBase {

  use TaxonomyAccessFixTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy_access_fix', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Users used.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users;

  /**
   * The vocabularies used.
   *
   * @var \Drupal\taxonomy\VocabularyInterface[]
   */
  protected $vocabularies;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->vocabularies[] = $this->createVocabulary();
    $this->vocabularies[] = $this->createVocabulary();

    $this->users['administer'] = $this->drupalCreateUser([
      'administer taxonomy',
      'create terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['overview'] = $this->drupalCreateUser([
      'access taxonomy overview',
    ]);
    $this->users['create_first_vocabulary'] = $this->drupalCreateUser([
      'create terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['overview_and_create_first_vocabulary'] = $this->drupalCreateUser([
      'access taxonomy overview',
      'create terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['update_first_vocabulary'] = $this->drupalCreateUser([
      'edit terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['overview_and_update_first_vocabulary'] = $this->drupalCreateUser([
      'access taxonomy overview',
      'edit terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['delete_first_vocabulary'] = $this->drupalCreateUser([
      'delete terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['overview_and_delete_first_vocabulary'] = $this->drupalCreateUser([
      'access taxonomy overview',
      'delete terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['view_first_vocabulary'] = $this->drupalCreateUser([
      'view terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['overview_and_view_first_vocabulary'] = $this->drupalCreateUser([
      'access taxonomy overview',
      'view terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['reorder_first_vocabulary'] = $this->drupalCreateUser([
      'reorder terms in ' . $this->vocabularies[0]->id(),
    ]);
    $this->users['overview_and_reorder_first_vocabulary'] = $this->drupalCreateUser([
      'access taxonomy overview',
      'reorder terms in ' . $this->vocabularies[0]->id(),
    ]);

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests access to administrative Taxonomy Vocabulary collection.
   */
  public function testTaxonomyVocabularyCollection() {
    $assert_session = $this->assertSession();

    // Test the 'administer taxonomy' permission.
    $this->drupalLogin($this->users['administer']);

    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextContains(t('Add vocabulary'));
    $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString());
    $this->assertSortableTable(FALSE);

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $assert_session->pageTextContains($vocabulary->label());
      $assert_session->pageTextContains($vocabulary->getDescription());
      $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
      ])->toString());
      $add_terms_url = Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString();
      $this->assertLinkByEndOfHref($add_terms_url);
    }

    // Test the 'access taxonomy overview' permission.
    $this->drupalLogin($this->users['overview']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextNotContains(t('Add vocabulary'));
    $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString());
    $this->assertNoSortableTable(FALSE);
    $assert_session->pageTextContains(t('No vocabularies available.'));

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $assert_session->pageTextNotContains($vocabulary->label());
      $assert_session->pageTextNotContains($vocabulary->getDescription());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
      ])->toString());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
    }

    // Test the per vocabulary 'view terms in' permission.
    $this->drupalLogin($this->users['view_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->users['overview_and_view_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextNotContains(t('Add vocabulary'));
    $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString());
    $this->assertNoSortableTable(FALSE);
    $assert_session->pageTextContains(t('No vocabularies available.'));

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $assert_session->pageTextNotContains($vocabulary->label());
      $assert_session->pageTextNotContains($vocabulary->getDescription());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
      ])->toString());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
    }

    // Test the per vocabulary 'create terms in' permission.
    $this->drupalLogin($this->users['create_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->users['overview_and_create_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextNotContains(t('Add vocabulary'));
    $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString());
    $this->assertNoSortableTable(FALSE);

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
      ])->toString());
      $overview_url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString();
      $add_terms_url = Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString();
      if ($delta === 0) {
        $assert_session->pageTextContains($vocabulary->label());
        $assert_session->pageTextContains($vocabulary->getDescription());
        $this->assertLinkByEndOfHref($overview_url);
        $this->assertLinkByEndOfHref($add_terms_url);
      }
      else {
        $assert_session->pageTextNotContains($vocabulary->label());
        $assert_session->pageTextNotContains($vocabulary->getDescription());
        $this->assertNoLinkByEndOfHref($overview_url);
        $this->assertNoLinkByEndOfHref($add_terms_url);
      }
    }

    // Test the per vocabulary 'edit terms in' permission.
    $this->drupalLogin($this->users['update_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->users['overview_and_update_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextNotContains(t('Add vocabulary'));
    $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString());
    $this->assertNoSortableTable(FALSE);

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
      ])->toString());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      $overview_url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString();
      if ($delta === 0) {
        $assert_session->pageTextContains($vocabulary->label());
        $assert_session->pageTextContains($vocabulary->getDescription());
        $this->assertLinkByEndOfHref($overview_url);
      }
      else {
        $assert_session->pageTextNotContains($vocabulary->label());
        $assert_session->pageTextNotContains($vocabulary->getDescription());
        $this->assertNoLinkByEndOfHref($overview_url);
      }
    }

    // Test the per vocabulary 'delete terms in' permission.
    $this->drupalLogin($this->users['delete_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->users['overview_and_delete_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextNotContains(t('Add vocabulary'));
    $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString());
    $this->assertNoSortableTable(FALSE);

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
      ])->toString());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      $overview_url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString();
      if ($delta === 0) {
        $assert_session->pageTextContains($vocabulary->label());
        $assert_session->pageTextContains($vocabulary->getDescription());
        $this->assertLinkByEndOfHref($overview_url);
      }
      else {
        $assert_session->pageTextNotContains($vocabulary->label());
        $assert_session->pageTextNotContains($vocabulary->getDescription());
        $this->assertNoLinkByEndOfHref($overview_url);
      }
    }

    // Test the per vocabulary 'reorder terms in' permission.
    $this->drupalLogin($this->users['reorder_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->users['overview_and_reorder_first_vocabulary']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextNotContains(t('Add vocabulary'));
    $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString());
    $this->assertNoSortableTable(FALSE);

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
      ])->toString());
      $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      $overview_url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString();
      if ($delta === 0) {
        $assert_session->pageTextContains($vocabulary->label());
        $assert_session->pageTextContains($vocabulary->getDescription());
        $this->assertLinkByEndOfHref($overview_url);
      }
      else {
        $assert_session->pageTextNotContains($vocabulary->label());
        $assert_session->pageTextNotContains($vocabulary->getDescription());
        $this->assertNoLinkByEndOfHref($overview_url);
      }
    }
  }

  /**
   * Tests access to Taxonomy Vocabulary overview page.
   */
  public function testTaxonomyVocabularyOverview() {
    $assert_session = $this->assertSession();

    $published_terms = [];
    $unpublished_terms = [];

    foreach ($this->vocabularies as $delta => $vocabulary) {
      $published_terms[$delta] = $this->createTerm($vocabulary, [
        'name' => 'Published term',
        'status' => 1,
      ]);
      $published_terms[$delta]->save();
      $unpublished_terms[$delta] = $this->createTerm($vocabulary, [
        'name' => 'Unpublished term',
        'status' => 0,
      ]);
      $unpublished_terms[$delta]->save();
    }

    // Test the 'administer taxonomy' permission.
    $this->drupalLogin($this->users['administer']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      $assert_session->statusCodeEquals(200);

      $this->assertElementByCssSelector('#edit-reset-alphabetical');
      $this->assertSortableTable();

      $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
      ])->toString());
      $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
      ])->toString());

      $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
      ])->toString());
      $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
        'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
      ])->toString());

      $assert_session->pageTextContains(t('Add term'));
      $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(200);
    }

    // Test the 'access taxonomy overview' permission.
    $this->drupalLogin($this->users['overview']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      $assert_session->statusCodeEquals(403);

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    // Test the per vocabulary 'create terms in' permission.
    $this->drupalLogin($this->users['create_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      $assert_session->statusCodeEquals(403);

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    $this->drupalLogin($this->users['overview_and_create_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      if ($delta === 0) {
        $assert_session->statusCodeEquals(200);

        $this->assertNoElementByCssSelector('#edit-reset-alphabetical');
        $this->assertNoSortableTable();

        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $assert_session->pageTextContains(t('Add term'));
        $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      }
      else {
        $assert_session->statusCodeEquals(403);
      }

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    // Test the per vocabulary 'edit terms in' permission.
    $this->drupalLogin($this->users['update_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      $assert_session->statusCodeEquals(403);

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    $this->drupalLogin($this->users['overview_and_update_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      if ($delta === 0) {
        $assert_session->statusCodeEquals(200);

        $this->assertNoElementByCssSelector('#edit-reset-alphabetical');
        $this->assertNoSortableTable();

        $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $assert_session->pageTextNotContains(t('Add term'));
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      }
      else {
        $assert_session->statusCodeEquals(403);
      }

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    // Test the per vocabulary 'delete terms in' permission.
    $this->drupalLogin($this->users['delete_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      $assert_session->statusCodeEquals(403);

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    $this->drupalLogin($this->users['overview_and_delete_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      if ($delta === 0) {
        $assert_session->statusCodeEquals(200);

        $this->assertNoElementByCssSelector('#edit-reset-alphabetical');
        $this->assertNoSortableTable();

        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $assert_session->pageTextNotContains(t('Add term'));
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      }
      else {
        $assert_session->statusCodeEquals(403);
      }

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    // Test the per vocabulary 'reorder terms in' permission.
    $this->drupalLogin($this->users['reorder_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      $assert_session->statusCodeEquals(403);

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    $this->drupalLogin($this->users['overview_and_reorder_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      if ($delta === 0) {
        $assert_session->statusCodeEquals(200);

        // @todo This fails, but should be available, since it related to
        // reordering terms and the route is also available. Fix and enable.
        // $this->assertElementByCssSelector('#edit-reset-alphabetical');
        // @todo Enable once Issue 2958658 has been fixed.
        // $this->assertSortableTable();
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $published_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.delete_form', ['taxonomy_term' => $unpublished_terms[$delta]->id()])->setOption('query', [
          'destination' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString(),
        ])->toString());

        $assert_session->pageTextNotContains(t('Add term'));
        $this->assertNoLinkByEndOfHref(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $vocabulary->id()])->toString());
      }
      else {
        $assert_session->statusCodeEquals(403);
      }

      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }

    $this->drupalLogin($this->users['view_first_vocabulary']);
    foreach ($this->vocabularies as $vocabulary) {
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/overview');
      $assert_session->statusCodeEquals(403);
      $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/reset');
      $assert_session->statusCodeEquals(403);
    }
  }

  /**
   * Test the vocabulary overview with no vocabularies.
   */
  public function testTaxonomyVocabularyNoVocabularies() {
    $assert_session = $this->assertSession();

    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $vocabulary) {
      $vocabulary->delete();
    }
    $this->assertEmpty(Vocabulary::loadMultiple(), 'No vocabularies found.');
    $this->drupalLogin($this->users['administer']);
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains(t('No vocabularies available.'));
  }

  /**
   * Tests access to entity operations on Taxonomy Vocabulary entities.
   */
  public function testTaxonomyVocabularyOperations() {
    $assert_session = $this->assertSession();

    // Test the 'administer taxonomy' permission.
    $this->drupalLogin($this->users['administer']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', TRUE);
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', TRUE);
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', TRUE);
      $this->assertVocabularyAccess($vocabulary, 'create', TRUE);
      $this->assertVocabularyAccess($vocabulary, 'update', TRUE);
      $this->assertVocabularyAccess($vocabulary, 'delete', TRUE);
    }

    // Test the per vocabulary 'access taxonomy overview' permission.
    $this->drupalLogin($this->users['overview']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }

    // Test the per vocabulary 'create terms in' permission.
    $this->drupalLogin($this->users['create_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }

    $this->drupalLogin($this->users['overview_and_create_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      if ($delta === 0) {
        $this->assertVocabularyAccess($vocabulary, 'view', TRUE);
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', TRUE);
      }
      else {
        $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      }
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }

    // Test the per vocabulary 'update terms in' permission.
    $this->drupalLogin($this->users['update_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }
    $this->drupalLogin($this->users['overview_and_update_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      if ($delta === 0) {
        $this->assertVocabularyAccess($vocabulary, 'view', TRUE);
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', TRUE);
      }
      else {
        $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      }
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }

    // Test the per vocabulary 'delete terms in' permission.
    $this->drupalLogin($this->users['delete_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }
    $this->drupalLogin($this->users['overview_and_delete_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      if ($delta === 0) {
        $this->assertVocabularyAccess($vocabulary, 'view', TRUE);
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', TRUE);
      }
      else {
        $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      }
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }

    // Test the per vocabulary 'view terms in' permission.
    $this->drupalLogin($this->users['view_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }
    $this->drupalLogin($this->users['overview_and_view_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }

    // Test the per vocabulary 'reorder terms in' permission.
    $this->drupalLogin($this->users['reorder_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
      if ($delta === 0) {
        $this->assertVocabularyAccess($vocabulary, 'reorder_terms', TRUE);
      }
      else {
        $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      }
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }
    $this->drupalLogin($this->users['overview_and_reorder_first_vocabulary']);
    foreach ($this->vocabularies as $delta => $vocabulary) {
      if ($delta === 0) {
        $this->assertVocabularyAccess($vocabulary, 'view', TRUE);
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', TRUE);
        $this->assertVocabularyAccess($vocabulary, 'reorder_terms', TRUE);
      }
      else {
        $this->assertVocabularyAccess($vocabulary, 'view', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
        $this->assertVocabularyAccess($vocabulary, 'access taxonomy overview', FALSE, "The 'access taxonomy overview' and one of the 'create terms in {$vocabulary->id()}', 'delete terms in {$vocabulary->id()}', 'edit terms in {$vocabulary->id()}', 'reorder terms in {$vocabulary->id()}' permissions OR the 'administer taxonomy' permission are required.");
        $this->assertVocabularyAccess($vocabulary, 'reorder_terms', FALSE, "The 'reorder terms in {$vocabulary->id()}' OR the 'administer taxonomy' permission is required.");
      }
      $this->assertVocabularyAccess($vocabulary, 'create', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'update', FALSE, "The 'administer taxonomy' permission is required.");
      $this->assertVocabularyAccess($vocabulary, 'delete', FALSE, "The 'administer taxonomy' permission is required.");
    }
  }

  /**
   * Checks access to Taxonomy Vocabulary entity.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   A Taxonomy Vocabulary entity.
   * @param bool $access_operation
   *   The entity operation, e.g. 'view', 'edit', 'delete', etc.
   * @param bool $access_allowed
   *   Whether the current user has access to the given operation or not.
   * @param string $access_reason
   *   (optional) The reason of the access result.
   */
  protected function assertVocabularyAccess(VocabularyInterface $vocabulary, $access_operation, $access_allowed, $access_reason = '') {
    $access_result = $vocabulary->access($access_operation, NULL, TRUE);
    $this->assertSame($access_allowed, $access_result->isAllowed());
    if ($access_reason) {
      /** @var \Drupal\Core\Access\AccessResultReasonInterface $access_result */
      $this->assertSame($access_reason, $access_result->getReason());
    }
  }

}
