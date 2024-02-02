<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class VagrantUpTest extends \PHPUnit\Framework\TestCase implements ContainerAwareInterface
{
    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
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

    public function testVagrantUpCommand()
    {
        $command = $this->taskVagrantUp('/usr/bin/vagrant')
            ->arg('name')
            ->provisionWith('x,y,z')
            ->destroyOnError()
            ->parallel()
            ->provider('PROVIDER')
            ->installProvider()
            ->getCommand();
        $expected = '/usr/bin/vagrant up name --provision-with x,y,z --destroy-on-error --parallel --provider PROVIDER --install-provider';
        $this->assertEquals($expected, $command);
    }
}
