<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class VagrantSnapshotTest extends \PHPUnit\Framework\TestCase implements ContainerAwareInterface
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

    public function testVagrantSnapshotDeleteCommand()
    {
        $command = $this->taskVagrantSnapshot('/usr/bin/vagrant')
            ->delete()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant snapshot delete name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantSnapshotListCommand()
    {
        $command = $this->taskVagrantSnapshot('/usr/bin/vagrant')
            ->listSnapshots()
            ->getCommand();
        $expected = '/usr/bin/vagrant snapshot list';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantSnapshotPopCommand()
    {
        $command = $this->taskVagrantSnapshot('/usr/bin/vagrant')
            ->pop()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant snapshot pop name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantSnapshotPushCommand()
    {
        $command = $this->taskVagrantSnapshot('/usr/bin/vagrant')
            ->push()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant snapshot push name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantSnapshotRestoreCommand()
    {
        $command = $this->taskVagrantSnapshot('/usr/bin/vagrant')
            ->restore()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant snapshot restore name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantSnapshotSaveCommand()
    {
        $command = $this->taskVagrantSnapshot('/usr/bin/vagrant')
            ->save()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant snapshot save name';
        $this->assertEquals($expected, $command);
    }
}
