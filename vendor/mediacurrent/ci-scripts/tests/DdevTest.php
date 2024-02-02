<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class DdevTest extends TestCase implements ContainerAwareInterface
{
    use \Mediacurrent\CiScripts\Task\loadTasks;
    use TaskAccessor;
    use ContainerAwareTrait;

    // Set up the Robo container so that we can create tasks in our tests.
    function setup(): void
    {
        $container = Robo::createDefaultContainer(null, new NullOutput());
        $this->setContainer($container);
    }

    // Scaffold the collection builder
    public function collectionBuilder()
    {
        $emptyRobofile = new \Robo\Tasks;
        return CollectionBuilder::create($this->getContainer(), $emptyRobofile);
    }

    public function testDdevTask()
    {
        $ddev_command = 'config';
        $opts = [
            'docroot' => 'web',
            'project-type' => 'drupal8',
            'project-name' => null,
            'webserver-type' => 'nginx-fpm',
            'project-name' => 'drupal-project',
            'create-docroot' => 'true'
        ];

        $command = $this->taskDdev()
            ->ddevCommand($ddev_command)
            ->ddevOptions($opts)
            ->getCommand();
        $expected = 'ddev config  --docroot=web --project-type=drupal8 --project-name=drupal-project --webserver-type=nginx-fpm --create-docroot=true';
        $this->assertEquals($expected, $command);
    }

}
