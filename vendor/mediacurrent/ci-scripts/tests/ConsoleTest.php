<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase implements ContainerAwareInterface
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

    public function testConsoleTask()
    {
        $console_command = 'list';
        $opts = 'test';
        $configuration = array();

        $configuration['drupal_composer_install_dir'] = '/home/vagrant/docroot';
        $configuration['vagrant_hostname'] = 'example.mcdev';

        $command = $this->taskConsole()
            ->consoleCommand($console_command)
            ->consoleOptions($opts)
            ->setConfiguration($configuration)
            ->getCommand();
        $expected = '/home/vagrant/docroot/bin/drupal --uri=http://example.mcdev --root=/home/vagrant/docroot/web list test';
        $this->assertEquals($expected, $command);
    }

}
