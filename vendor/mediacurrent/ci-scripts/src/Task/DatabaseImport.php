<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Common\ResourceExistenceChecker;
use Robo\Common\Timer;
use Robo\Common\TaskIO;

class DatabaseImport extends \Mediacurrent\CiScripts\Task\Base
{
    use ResourceExistenceChecker;
    use \Mediacurrent\CiScripts\Task\loadTasks;

    protected $database_file;

    public function databaseFile($database_file = null) {
        $this->database_file = $database_file;

        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        $this->startTimer();

        $exit_code = 0;

        if(is_file($this->getProjectRoot() . '/' . $this->database_file)) {
            $database_file_path = $this->configuration['drupal_composer_install_dir'] . '/' . $this->database_file;

            $result = $this->collectionBuilder()->taskDrush()
                ->drushCommand('sqlc')
                ->drushOptions('< ' . $database_file_path)
                ->run();
            $exit_code = $result->getExitCode();
        }
        else {
            $this->printTaskError('Database file ' . $this->getProjectRoot() . '/' . $this->database_file . ' not found.');
            $exit_code = 1;
        }
        $this->stopTimer();
        return new Result(
            $this,
            $exit_code,
            'DatabaseImport',
            ['time' => $this->getExecutionTime()]
        );

    }
}
