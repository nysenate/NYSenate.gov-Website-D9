<?php

namespace Drupal\Tests\name\Kernel;

use Drupal\name\Controller\NameAutocompleteController;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\name\Functional\NameTestTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests name autocomplete.
 *
 * @group name
 */
class NameAutocompleteTest extends KernelTestBase {

  use NameTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * The entity listener.
   *
   * @var \Drupal\Core\Entity\EntityTypeListener
   */
  protected $entityListener;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);

    $this->entityListener = \Drupal::service('entity_type.listener');
    $this->entityListener->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('entity_test'));

    $this->field = $this->createNameField('field_name_test', 'entity_test', 'entity_test');
  }

  /**
   * Tests the controller.
   */
  public function testAutocompleteController() {
    $autocomplete = NameAutocompleteController::create($this->container);
    $request = new Request();
    $request->attributes->add(['q' => 'Bob']);

    $result = $autocomplete->autocomplete($request, 'field_name_test', 'entity_test', 'entity_test', 'family');
    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Tests the controller with an invalid bundle.
   *
   * In this case it expected that an exception of type
   * AccessDeniedHttpException is thrown.
   */
  public function testAutocompleteControllerWithInvalidBundle() {
    $autocomplete = NameAutocompleteController::create($this->container);
    $request = new Request();
    $request->attributes->add(['q' => 'Bob']);

    $this->expectException(AccessDeniedHttpException::class);
    $autocomplete->autocomplete($request, 'field_name_test', 'entity_test', 'invalid_bundle', 'family');
  }

  /**
   * Tests the service.
   */
  public function testAutocomplete() {
    $autocomplete = \Drupal::service('name.autocomplete');

    // Title component.
    $matches = $autocomplete->getMatches($this->field, 'title', 'M');
    $this->assertEquals($matches, $this->mapAssoc(['Mr.', 'Mrs.', 'Miss', 'Ms.']));

    $matches = $autocomplete->getMatches($this->field, 'title', 'Mr');
    $this->assertEquals($matches, $this->mapAssoc(['Mr.', 'Mrs.']));

    $matches = $autocomplete->getMatches($this->field, 'title', 'Pr');
    $this->assertEquals($matches, $this->mapAssoc(['Prof.']));

    $matches = $autocomplete->getMatches($this->field, 'title', 'X');
    $this->assertEquals($matches, []);

    // First name component.
    $names = [
      'SpongeBob SquarePants',
      'Patrick Star',
      'Squidward Tentacles',
      'Eugene Krabs',
      'Sandy Cheeks',
      'Gary Snail',
    ];
    foreach ($names as $name) {
      $name = explode(' ', $name);
      $entity = $this->container->get('entity_type.manager')
        ->getStorage('entity_test')
        ->create([
          'bundle' => 'entity_test',
          'field_name_test' => [
            'given' => $name[0],
            'family' => $name[1],
          ],
        ]);
      $entity->save();
    }

    $matches = $autocomplete->getMatches($this->field, 'name', 'S');
  }

}
