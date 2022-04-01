<?php

namespace Drupal\Tests\conditional_fields\Unit;

use Drupal\Tests\UnitTestCase;

use Drupal\conditional_fields\Controller\ConditionalFieldController;

/**
 * ConditionalFieldController units tests.
 *
 * In unit testing, there should be as few dependencies as possible.
 * We want the smallest number of moving parts to be interacting in
 * our test, or we won't be sure where the errors are, or whether our
 * tests passed by accident.
 *
 * @ingroup conditional_fields
 *
 * @group conditional_fields
 */
class ConditionalFieldControllerTest extends UnitTestCase {

  /**
   * CF Controller.
   *
   * @var \Drupal\conditional_fields\Controller\ConditionalFieldController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity_types = [];
    // `content_a` should appear in test results.
    $entity_types['content_a'] = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityType')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_types['content_a']->expects($this->any())
      ->method('getLabel')
      ->will($this->returnValue("contentA"));

    // `content_b` shouldn't appear in test results.
    $entity_types['content_b'] = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_types['content_b']->expects($this->any())
      ->method('getLabel')
      ->will($this->returnValue("contentB"));

    // Setup Drupal Container.
    $entity_type_manager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_type_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($entity_types));

    // For only one test case this classes not used,
    // change this after adding new test cases.
    $form_builder = $this->createMock('Drupal\Core\Form\FormBuilderInterface');
    $form_builder->expects($this->never())->method($this->anything());

    $entity_type_bundle_info = $this->createMock('Drupal\Core\Entity\EntityTypeBundleInfoInterface');
    $entity_type_bundle_info->expects($this->never())->method($this->anything());

    $entity_field_manager = $this->createMock('Drupal\Core\Entity\EntityFieldManagerInterface');
    $entity_field_manager->expects($this->never())->method($this->anything());

    // ConditionalFieldController::create();
    $this->controller = new ConditionalFieldController(
      $entity_type_manager,
      $form_builder,
      $entity_type_bundle_info,
      $entity_field_manager
    );
  }

  /**
   * Very simple test of ConditionalFieldController::entityTypeList().
   *
   * This is a very simple unit test of a single method.
   * It checks that only instances of `ContentEntityType` are available.
   */
  public function testEntityTypeList() {
    $available_entity_types = $this->controller->entityTypeList()['#content'];
    $this->assertEquals(1, count($available_entity_types));
    $this->assertEquals($available_entity_types[0]['title'], 'contentA');
  }

}
