<?php

namespace Drupal\Tests\eva\Functional;

/**
 * Preliminary tests for Eva.
 *
 * @group eva
 */
class EvaTest extends EvaTestBase {

  /**
   * Assert that the Eva of Articles appears on a Page.
   */
  public function testEvaOnPage() {
    $assert = $this->assertSession();

    $this->drupalGet('/node/' . $this->nids['just_eva']);
    $assert->statusCodeEquals(200);

    $this->assertEquals(
        $this->articleCount,
        \count($this->xpath('//div[contains(@class, "view-eva")]//div[contains(@class, "views-row")]')),
        sprintf('Found %d articles in Eva.', $this->articleCount)
    );
  }

  /**
   * Test issue described in https://www.drupal.org/node/2873385.
   */
  public function test2873385() {
    $assert = $this->assertSession();

    $this->drupalGet('/node/' . $this->nids['pages'][0]);
    $assert->statusCodeEquals(200);

    $this->drupalGet('/node/' . $this->nids['pages'][1]);
    $assert->statusCodeEquals(200);

    $this->drupalGet('/2873385');
    $assert->statusCodeEquals(200);

    // The view-eva's' should not all contain the same labels.
    $evas = $this->xpath('//div[contains(@class, "view-eva")]');
    $all_links = [];
    foreach ($evas as $x) {
      $links = $x->findAll('xpath', '//a');
      $these_links = [];
      foreach ($links as $l) {
        $these_links[] = $l->getText();
      }
      $all_links[] = implode('-', $these_links);
    }
    $this->assertGreaterThan(
        1,
        \count(\array_unique($all_links)),
        'Found more than one unique Eva.'
    );
  }

  /**
   * Test issue described in https://www.drupal.org/project/eva/issues/3059233.
   */
  public function test3059233() {
    $assert = $this->assertSession();

    // Test that an EVA shows up.
    $this->drupalGet('/node/' . $this->nids['just_eva']);
    $assert->statusCodeEquals(200);

    $this->assertEquals(
        $this->articleCount,
        \count($this->xpath('//div[contains(@class, "view-eva")]//div[contains(@class, "views-row")]')),
        sprintf('Found %d articles in Eva.', $this->articleCount)
    );

    // Duplicate the articles EVA, change the node type to attach to.
    $orig = \Drupal::service('entity_type.manager')->getStorage('view')->load('articles');
    $new = $orig->createDuplicate();
    $new->set('id', 'articles_2');
    $display = $new->get('display');
    $display['entity_view_1']['display_options']['bundles'] = ['another_eva'];
    $new->set('display', $display);
    $new->save();

    // Make another page.
    $node = $this->createNode([
      'title' => 'Test Eva 2',
      'type' => 'another_eva',
    ]);
    $this->nids['new_page'] = $node->id();

    // Test the new EVA shows up.
    $this->drupalGet('/node/' . $this->nids['new_page']);
    $this->assertEquals(
      $this->articleCount,
      \count($this->xpath('//div[contains(@class, "view-eva")]//div[contains(@class, "views-row")]')),
      sprintf('Found %d articles in Eva.', $this->articleCount)
    );

    // Delete the View (test https://www.drupal.org/project/eva/issues/3017744).
    $new->delete();

    // Test the EVA is gone.
    $this->drupalGet('/node/' . $this->nids['new_page']);
    $this->assertEquals(
      0,
      \count($this->xpath('//div[contains(@class, "view-eva")]//div[contains(@class, "views-row")]')),
      sprintf('Found %d articles in Eva.', $this->articleCount)
    );
  }

}
