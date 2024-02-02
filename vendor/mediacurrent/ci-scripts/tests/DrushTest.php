<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class DrushTest extends TestCase implements ContainerAwareInterface
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

    public function testDrushTask()
    {
        $drush_command = 'help';
        $opts = 'status';
        $configuration = array();

        $configuration['drupal_composer_install_dir'] = '/var/www/html';
        $configuration['vagrant_hostname'] = 'example.ddev.site';

        $command = $this->taskDrush()
            ->drushCommand($drush_command)
            ->drushOptions($opts)
            ->setConfiguration($configuration)
            ->getCommand();
        $expected = '/var/www/html/vendor/bin/drush --uri=http://example.ddev.site --root=/var/www/html/web help status';
        $this->assertEquals($expected, $command);
    }
}
