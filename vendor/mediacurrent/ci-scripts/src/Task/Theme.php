<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;

class Theme extends \Mediacurrent\CiScripts\Task\Base
{
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Npm\Tasks;
    use \Mediacurrent\CiScripts\Task\loadTasks;

    protected $exitCode = 0;
    protected $pathToTheme;

    public function __construct()
    {
        $this->startTimer();
        parent::__construct();
    }

    public function npmRunBuild()
    {

        $command = 'npm run build';
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function npmRunCompile()
    {

        $command = 'npm run compile';
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function nvmInstall()
    {

        $command = 'nvm install';
        $result = $this->collectionBuilder()->taskExec("/bin/bash -l -c '$command'")
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function nvmUse()
    {

        $command = 'nvm use';
        $result = $this->collectionBuilder()->taskExec("/bin/bash -l -c '$command'")
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function npmInstall()
    {

        $result = $this->collectionBuilder()->taskNpmInstall()
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function npmRunStyleGuide()
    {

        $command = 'npm run styleguide';
        $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();
        return $this;
    }

    public function npmRunWatch()
    {

        $command = 'npm run watch';
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function themeDirectory($pathToTheme = null)
    {
        if (!$pathToTheme) {
            $drush = 'drush --uri=' . $this->configuration['vagrant_hostname'] . ' --root=' . $this->getWebRoot() . ' ';
            $drush_command = 'php-eval "return \Drupal::theme()->getActiveTheme()->getPath();"';
            $command = $drush . $drush_command;
            $result = shell_exec($command);
            $active_theme = str_replace(array( "'", "\n"), '', $result);
            $this->pathToTheme = $this->getWebRoot() . '/' . $active_theme;
        } else {
            $this->pathToTheme = $pathToTheme;
        }

        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        $this->stopTimer();
        return new Result(
            $this,
            $this->exitCode,
            'Theme',
            ['time' => $this->getExecutionTime()]
        );
    }
}
