<?php

namespace Drupal\Tests\queue_unique\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\queue_unique\QueueFactory;
use Drupal\queue_unique\UniqueDatabaseQueue;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Unique queue factory kernel test.
 *
 * @group queue_unique
 */
class QueueFactoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['queue_unique'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Update Settings before it's used as a service argument.
    foreach (['queue_unique/reliable', 'something_else'] as $name) {
      $this->setSetting('queue_reliable_service_' . $name, 'queue.database');
    }

    foreach (['queue/hello', 'something_unique'] as $name) {
      $this->setSetting('queue_service_' . $name, 'queue_unique.database');
    }
    $this->container->setParameter('install_profile', 'testing');
    // Replace core service.
    $this->container->register('queue', QueueFactory::class)
      ->addArgument(new Reference('settings'))
      ->addMethodCall('setContainer', [new Reference('service_container')]);
    // Add an alias for queue_unique.database.
    $this->container->addAliases(['llama_monster.database' => 'queue_unique.database']);
  }

  /**
   * Test that queues are found based on name/prefix.
   */
  public function testQueueNameMapping() {
    $queue_service = $this->container->get('queue');
    self::assertClassOf(QueueFactory::class, $queue_service);

    $queue_instance_count = 0;
    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_service->get('queue_unique/test');
    self::assertClassOf(UniqueDatabaseQueue::class, $queue);
    self::assertQueueName($queue, 'test');
    $queue_instance_count++;

    // Test the aliased service.
    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_service->get('llama_monster/test');
    self::assertClassOf(UniqueDatabaseQueue::class, $queue);
    self::assertQueueName($queue, 'test');
    $queue_instance_count++;

    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_service->get('test');
    self::assertInstanceOf(DatabaseQueue::class, $queue);
    self::assertQueueName($queue, 'test');
    $queue_instance_count++;

    // This maps to core queue.database.
    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_service->get('queue/test');
    self::assertClassOf(DatabaseQueue::class, $queue);
    self::assertQueueName($queue, 'test');
    $queue_instance_count++;

    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_service->get('random/test');
    self::assertClassOf(DatabaseQueue::class, $queue);
    self::assertQueueName($queue, 'random/test');
    $queue_instance_count++;

    // Test Settings used in preference to the name service mapping.
    // See self::register() above.
    foreach (['queue_unique/reliable', 'something_else'] as $name) {
      /* @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $queue_service->get($name, TRUE);
      self::assertClassOf(DatabaseQueue::class, $queue);
      self::assertQueueName($queue, $name);
      $queue_instance_count++;
    }
    foreach (['queue/hello', 'something_unique'] as $name) {
      $queue = $queue_service->get($name);
      self::assertClassOf(UniqueDatabaseQueue::class, $queue);
      self::assertQueueName($queue, $name);
      $queue_instance_count++;
    }
    // Every name should have generated a new queue instance.
    $reflected_queues = (new \ReflectionObject($queue_service))->getProperty('queues');
    $reflected_queues->setAccessible(TRUE);
    $queue_instances = $reflected_queues->getValue($queue_service);
    self::assertCount($queue_instance_count, $queue_instances);
    // Getting the same names again should not change the number of instances.
    foreach (['queue_unique/test', 'test', 'queue/test', 'random/test'] as $name) {
      $queue_service->get($name);
    }
    $queue_instances = $reflected_queues->getValue($queue_service);
    self::assertCount($queue_instance_count, $queue_instances);
    // Appending any string to the name will generate new instances.
    foreach (['queue_unique/test', 'test', 'queue/test', 'random/test'] as $name) {
      $queue_service->get($name . '2');
      $queue_instance_count++;
    }
    $queue_instances = $reflected_queues->getValue($queue_service);
    self::assertCount($queue_instance_count, $queue_instances);
  }

  /**
   * Check for exact match of class name to object.
   *
   * Note that self::assertInstanceOf() can give an unexpected pass since
   * UniqueDatabaseQueue is a subclass of DatabaseQueue.
   *
   * @param $class
   *   The class name.
   * @param $object
   *   The object
   */
  protected static function assertClassOf($class, $object) {
    self::assertSame($class, get_class($object));
  }

  /**
   * Verify that the name property of the queue is the expected value.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   A queue.
   * @param string $expected
   *   The expected name of the queue used in the database.
   *
   * @throws \ReflectionException
   */
  protected static function assertQueueName(QueueInterface $queue, string $expected): void {
    $reflected_name = (new \ReflectionObject($queue))->getProperty('name');
    $reflected_name->setAccessible(TRUE);
    self::assertSame($expected, $reflected_name->getValue($queue));
  }

  /**
   * Data provider with queue names to test.
   *
   * @return array
   *  A name.
   */
  public function queueNamesProvider(): array {
    return [
      ['queue_unique/fellow', 'queue_unique.database', 'fellow'],
      ['queue/hello', 'queue.database', 'hello'],
      ['llama_monster/test', 'llama_monster.database', 'test'],
      ['yellow_bird/bye', 'queue.database', 'yellow_bird/bye'],
    ];
  }

  /**
   * Test QueueService::defaultServiceAndQueueName().
   *
   * @dataProvider queueNamesProvider
   */
  public function testQueueNameAndDefaultService($input_name, $expected_service, $expected_name) {
    $queue_service = $this->container->get('queue');
    self::assertClassOf(QueueFactory::class, $queue_service);
    [$default_service_name, $name] = $queue_service->defaultServiceAndQueueName($input_name);
    self::assertEquals($expected_service, $default_service_name);
    self::assertEquals($expected_name, $name);
  }
}
