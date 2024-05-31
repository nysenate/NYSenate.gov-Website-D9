<?php

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\Loader\ComponentsLoader;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\components\Template\Loader\ComponentsLoader
 * @group components
 */
class ComponentsLoaderTest extends UnitTestCase {

  /**
   * The components registry service.
   *
   * @var \Drupal\components\Template\ComponentsRegistry|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $componentsRegistry;

  /**
   * The system under test.
   *
   * @var \Drupal\components\Template\Loader\ComponentsLoader
   */
  protected $systemUnderTest;

  /**
   * Invokes a protected or private method of an object.
   *
   * @param object|null $obj
   *   The instantiated object (or null for static methods.)
   * @param string $method
   *   The method to invoke.
   * @param mixed $args
   *   The parameters to be passed to the method.
   *
   * @return mixed
   *   The return value of the method.
   *
   * @throws \ReflectionException
   */
  public function invokeProtectedMethod(?object $obj, string $method, ...$args) {
    // Use reflection to test a protected method.
    $methodUnderTest = new \ReflectionMethod($obj, $method);
    $methodUnderTest->setAccessible(TRUE);
    return $methodUnderTest->invokeArgs($obj, $args);
  }

  /**
   * Tests finding a template.
   *
   * @covers ::findTemplate
   *
   * @dataProvider providerTestFindTemplate
   */
  public function testFindTemplate(string $name, bool $throw, ?string $getTemplate, ?string $expected, ?string $exception = NULL) {
    // Mock services.
    $componentsRegistry = $this->createMock('\Drupal\components\Template\ComponentsRegistry');
    $componentsRegistry
      ->method('getTemplate')
      ->willReturn($getTemplate);

    $this->systemUnderTest = new ComponentsLoader($componentsRegistry);

    try {
      // Use reflection to test protected methods and properties.
      $result = $this->invokeProtectedMethod($this->systemUnderTest, 'findTemplate', $name, $throw);

      if (!$exception) {
        $this->assertEquals($expected, $result, $this->getName());
      }
    }
    catch (\Exception $e) {
      if ($exception) {
        $this->assertEquals($exception, $e->getMessage(), $this->getName());
        $exception = '';
      }
      else {
        $this->fail('No exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
      }
    }

    if ($exception) {
      $this->fail('No exception thrown, but "' . $exception . '" was expected during: ' . $this->getName());
    }
  }

  /**
   * Provides test data to ::testFindTemplate().
   *
   * @see testFindTemplate()
   */
  public function providerTestFindTemplate(): array {
    return [
      'error when template name has no @' => [
        'name' => 'n/template.twig',
        'throw' => FALSE,
        'getTemplate' => 'not called',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'error when template name has no namespace' => [
        'name' => '@/template.twig',
        'throw' => FALSE,
        'getTemplate' => 'not called',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'error when template name does not have an expected extension' => [
        'name' => '@ns/template.txt',
        'throw' => FALSE,
        'getTemplate' => 'not called',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'exception when invalid template name and $throw = TRUE' => [
        'name' => '@ns/template.txt',
        'throw' => TRUE,
        'getTemplate' => 'not called',
        'expected' => '',
        'exception' => 'Malformed namespaced template name "@ns/template.txt" (expecting "@namespace/template_name.twig").',
      ],
      'error when template not found' => [
        'name' => '@ns/template.twig',
        'throw' => FALSE,
        'getTemplate' => NULL,
        'expected' => NULL,
        'exception' => NULL,
      ],
      'exception when template not found and $throw = TRUE' => [
        'name' => '@ns/template.twig',
        'throw' => TRUE,
        'getTemplate' => NULL,
        'expected' => NULL,
        'exception' => 'Unable to find template "@ns/template.twig" in the components registry.',
      ],
      'template (.twig) found' => [
        'name' => '@ns/template.twig',
        'throw' => TRUE,
        'getTemplate' => 'themes/contrib/example/ns/template.twig',
        'expected' => 'themes/contrib/example/ns/template.twig',
        'exception' => NULL,
      ],
      'template (.html) found' => [
        'name' => '@ns/template.html',
        'throw' => TRUE,
        'getTemplate' => 'themes/contrib/example/ns/template.html',
        'expected' => 'themes/contrib/example/ns/template.html',
        'exception' => NULL,
      ],
      'template (.svg) found' => [
        'name' => '@ns/icon.svg',
        'throw' => TRUE,
        'getTemplate' => 'themes/contrib/example/ns/icon.svg',
        'expected' => 'themes/contrib/example/ns/icon.svg',
        'exception' => NULL,
      ],
    ];
  }

  /**
   * Tests checking if a template exists.
   *
   * @covers ::exists
   *
   * @dataProvider providerTestExists
   */
  public function testExists(string $template, ?string $getTemplate, bool $expected) {
    // Mock services.
    $componentsRegistry = $this->createMock('\Drupal\components\Template\ComponentsRegistry');
    $componentsRegistry
      ->method('getTemplate')
      ->willReturn($getTemplate);

    $this->systemUnderTest = new ComponentsLoader($componentsRegistry);

    $result = $this->systemUnderTest->exists($template);
    $this->assertEquals($expected, $result, $this->getName());
  }

  /**
   * Provides test data to ::testExists().
   *
   * @see testExists()
   */
  public function providerTestExists(): array {
    return [
      'confirms a template does exist' => [
        'template' => '@ns/example-exists.twig',
        'getTemplate' => 'themes/contrib/example/ns/example-exists.twig',
        'expected' => TRUE,
      ],
      'confirms a template does not exists' => [
        'template' => '@ns/example-does-not-exist.twig',
        'getTemplate' => NULL,
        'expected' => FALSE,
      ],
    ];
  }

}
