<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;

class ReleaseBuild extends \Mediacurrent\CiScripts\Task\Base
{

    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Composer\Tasks;
    use \Robo\Task\File\Tasks;
    use \Robo\Task\FileSystem\Tasks;
    use \Robo\Task\Remote\Tasks;
    use \Robo\Task\Vcs\Tasks;

    private $build_path;
    private $commit_msg;
    private $pathToTheme;
    private $project_docroot;
    private $project_drupal_root;
    private $project_repo_dest;
    private $release_docroot;
    private $release_drupal_root;
    private $release_repo_dest;

    public function __construct()
    {
        $this->startTimer();

        parent::__construct();

        if (empty($this->configuration['build_branch'])) {
            $this->configuration['build_branch'] = 'develop';
        }
        if (empty($this->configuration['build_directory'])) {
            $this->configuration['build_directory'] = 'build';
        }
        $this->build_path = $this->getProjectRoot() . '/' . $this->configuration['build_directory'];
        $this->project_repo_dest = $this->build_path . '/project_repo';
        $this->release_repo_dest = $this->build_path . '/release_repo';
        $this->project_drupal_root = 'web';
        if (!empty($this->configuration['project_drupal_root'])) {
             $this->project_drupal_root = $this->configuration['project_drupal_root'];
        }
        $this->project_docroot = $this->project_repo_dest . '/' . $this->project_drupal_root;
        $this->release_drupal_root = 'web';
        if (!empty($this->configuration['release_drupal_root'])) {
             $this->release_drupal_root = $this->configuration['release_drupal_root'];
        }
        $this->release_docroot = $this->release_repo_dest . '/' . $this->release_drupal_root;
    }

    public function releaseHost($deploy_host = null)
    {

        if (!$deploy_host && !empty($this->configuration['deploy_host'])) {
            $deploy_host = $this->configuration['deploy_host'];
        }

        if (!$this->configuration['project_repo']
            || !$this->configuration['release_repo']
            || !$deploy_host) {
            return $this;
        }

        switch (strtolower($deploy_host)) {
            case 'acquia':
                $this->release_drupal_root = 'docroot';
                $this->release_docroot = $this->release_repo_dest . '/' . $this->release_drupal_root;
                $this->releaseBuildDirectories()
                    ->releaseGitCheckout()
                    ->releaseSyncProject()
                    ->releaseSyncDocroot()
                    ->releaseSetDocroot()
                    ->releaseComposerInstall()
                    ->releaseCleanupModuleVcs()
                    ->releaseCommit();
                break;

            default:
                break;
        }

        return $this;
    }

    public function releaseBuildDirectories($opts = [])
    {

        if(!empty($opts['clean'])) {
            $this->collectionBuilder()->taskDeleteDir($this->build_path)
                ->run();
        }

        $this->collectionBuilder()->taskFilesystemStack()
           ->mkdir($this->build_path)
           ->run();

        $this->collectionBuilder()->taskFilesystemStack()
           ->mkdir($this->project_repo_dest)
           ->run();

        $this->collectionBuilder()->taskFilesystemStack()
           ->mkdir($this->release_repo_dest)
           ->run();

        return $this;
    }

    public function releaseGitCheckout($build_branch = null, $release_tag = null)
    {
        $this->releaseGitCheckoutProject($build_branch, $release_tag);
        $this->releaseGitCheckoutRelease($build_branch);

        return $this;
    }

    public function releaseGitCheckoutProject($build_branch = null, $release_tag = null, $project_repo = null)
    {

        if (!$build_branch) {
            $build_branch = $this->configuration['build_branch'];
        } else {
            $this->configuration['build_branch'] = $build_branch;
        }

        if (!$project_repo) {
            $project_repo = $this->configuration['project_repo'];
        }

        if (exec('ls -1 ' . $this->project_repo_dest . '/.git')) {
            chdir($this->project_repo_dest);
            $result = shell_exec("git ls-remote --get-url");
            $git_remote_url = str_replace("\n", '', $result);
            if ($project_repo !== $git_remote_url) {
                $this->printTaskError('Repository remote changed from ' . $git_remote_url . ' to ' . $project_repo . '. Delete build/project_repo directory and run release:build again.');
                exit(1);
            }

            $result = $this->collectionBuilder()->taskGitStack()
                ->dir($this->project_repo_dest)
                ->pull('origin', $build_branch)
                ->checkout($build_branch)
                ->run();
        } else {
            $result = $this->collectionBuilder()->taskGitStack()
                ->cloneRepo($project_repo, $this->project_repo_dest)
                ->dir($this->project_repo_dest)
                ->checkout($build_branch)
                ->run();
        }

        if (!$result->wasSuccessful()) {
            exit(1);
        }

        if ($release_tag) {
            $this->collectionBuilder()->taskExec('git fetch --tags')
                ->dir($this->project_repo_dest)
                ->run();

            $result = $this->collectionBuilder()->taskGitStack()
                ->dir($this->project_repo_dest)
                ->checkout($release_tag)
                ->run();
            if (!$result->wasSuccessful()) {
                exit(1);
            }
        }

        $gitlog_cmd = 'cd ' . $this->project_repo_dest . ' && git log --format=%B -n 1';
        $this->commit_msg = shell_exec($gitlog_cmd);
        $this->printTaskInfo("\ncommit message = " . $this->commit_msg);

        return $this;
    }

    public function releaseGitCheckoutRelease($build_branch = null, $release_tag = null, $release_repo = null)
    {

        if (!$release_repo) {
            $release_repo = $this->configuration['release_repo'];
        }

        if (!$build_branch) {
            $build_branch = $this->configuration['build_branch'];
        }

        if (exec('ls -1 ' . $this->release_repo_dest . '/.git')) {
            chdir($this->release_repo_dest);

            $result = shell_exec("git ls-remote --get-url");
            $git_remote_url = str_replace("\n", '', $result);
            if ($release_repo !== $git_remote_url) {
                $this->printTaskError('Repository remote changed from ' . $git_remote_url . ' to ' . $release_repo . '. Delete build/release_repo directory and run release:build again.');
                    exit(1);
            }

            $local_branch = exec('git branch | grep ' . $build_branch);
            $remote_branch = exec('git branch -a | grep origin/' . $build_branch);
            if ($local_branch || $remote_branch) {
                $result = $this->collectionBuilder()->taskGitStack()
                    ->dir($this->release_repo_dest)
                    ->checkout($build_branch)
                    ->run();
                if ($remote_branch) {
                    $result = $this->collectionBuilder()->taskGitStack()
                        ->dir($this->release_repo_dest)
                        ->pull('origin', $build_branch)
                        ->run();
                }
            } else {
                $result = $this->collectionBuilder()->taskExec('git checkout -b ' . $build_branch)
                    ->dir($this->release_repo_dest)
                    ->run();
            }
        } else {
            $result = $this->collectionBuilder()->taskGitStack()
                ->cloneRepo($release_repo, $this->release_repo_dest)
                ->run();

            if (!$result->wasSuccessful()) {
                exit(1);
            }

            chdir($this->release_repo_dest);
            if (exec('git branch -a | grep origin/' . $build_branch)) {
                $result = $this->collectionBuilder()->taskGitStack()
                    ->dir($this->release_repo_dest)
                    ->checkout($build_branch)
                    ->run();
            } else {
                $result = $this->collectionBuilder()->taskExec('git checkout -b ' . $build_branch)
                    ->dir($this->release_repo_dest)
                    ->run();
            }

        }
        if (!$result->wasSuccessful()) {
            exit(1);
        }

        if ($release_tag) {
            $result = $this->collectionBuilder()->taskExec('git fetch --tags')
                    ->dir($this->release_repo_dest)
                    ->run();

            if (exec('git tag | grep -F "' . $release_tag . '"')) {
                if( !empty($this->configuration['release_deploy_dest'])) {
                    $result = $this->collectionBuilder()->taskGitStack()
                        ->dir($this->release_repo_dest)
                        ->checkout($release_tag)
                        ->run();
                }
                else {
                    $this->printTaskError('Release repository tag ' . $release_tag . ' already exists.');
                    exit(1);
                }
            }
            if (!$result->wasSuccessful()) {
                exit(1);
            }
        }

        return $this;
    }

    public function releaseSetDocroot()
    {
        if ($this->project_drupal_root !== $this->release_drupal_root) {
            $this->collectionBuilder()->taskReplaceInFile($this->release_repo_dest . '/composer.json')
                ->from($this->project_drupal_root . '/')
                ->to($this->release_drupal_root . '/')
                ->run();

            $this->collectionBuilder()->taskReplaceInFile($this->release_repo_dest
                . '/scripts/composer/ScriptHandler.php')
                ->from('/' . $this->project_drupal_root)
                ->to('/' . $this->release_drupal_root)
                ->run();
        }
        return $this;
    }

    public function releaseSyncProject()
    {
        $this->collectionBuilder()->taskRsync()
            ->fromPath($this->project_repo_dest . '/')
            ->toPath($this->release_repo_dest . '/')
            ->recursive()
            ->delete()
            ->exclude('vendor')
            ->exclude('bin')
            ->exclude($this->project_drupal_root)
            ->exclude('tests')
            ->exclude('.git')
            ->option('links')
            ->option('perms')
            ->run();

        return $this;
    }

    public function releaseSyncDocroot()
    {
        $this->collectionBuilder()->taskRsync()
            ->fromPath($this->project_docroot . '/')
            ->toPath($this->release_docroot . '/')
            ->recursive()
            ->delete()
            ->exclude('.git')
            ->option('links')
            ->option('perms')
            ->run();

        return $this;
    }

    public function releaseComposerInstall($opts = [])
    {
        if(empty($opts['composer_install']))
        {
            return $this;
        }

        $composer_cmd = 'composer install --no-ansi --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader';

        $result = $this->collectionBuilder()->taskExec($composer_cmd)
            ->dir($this->release_repo_dest)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }

        return $this;
    }

    public function releaseCleanupModuleVcs()
    {
        $shell_cmd = 'find . -type d | grep "\.git$" | xargs rm -rf';

        $this->collectionBuilder()->taskExec($shell_cmd)
            ->dir($this->release_docroot)
            ->run();

        $this->collectionBuilder()->taskExec($shell_cmd)
            ->dir($this->release_repo_dest . '/vendor')
            ->run();

        return $this;
    }

    public function releaseCommit($release_tag = null, $gitignore = [])
    {
        $dir = $this->release_repo_dest;
        $git_status = shell_exec('cd ' . $dir . ' && git -c core.filemode status');

        $this->printTaskInfo('git status = ' . $git_status);

        if (!strpos($git_status, 'nothing to commit, working directory clean')) {
            $this->collectionBuilder()->taskGitStack()
                ->dir($dir)
                ->add('-Af')
                ->run();

            foreach ($gitignore as $value) {
                $this->collectionBuilder()->taskExec('git reset -- ' . $value)
                    ->dir($this->release_docroot)
                    ->run();
            }

            $commit_msg = $this->configuration['build_branch'];
            if ($release_tag) {
                $commit_msg .= ' ' . $release_tag;
            }
            $commit_msg .= ' build at ' . date('c') . "\n";
            $commit_msg .= str_replace("'", "", $this->commit_msg);

            $this->collectionBuilder()->taskGitStack()
                ->dir($dir)
                ->commit($commit_msg)
                ->run();

            if ($release_tag) {
                $this->collectionBuilder()->taskGitStack()
                ->dir($dir)
                ->tag($release_tag)
                ->run();
            }

        }

        return $this;
    }

    public function releaseThemeBuild($themeDirs = [], $opts = [])
    {

        $noNvm = FALSE;
        $theme_styleguide = FALSE;

        if(!empty($opts['no_nvm'])) {
            $noNvm = $opts['no_nvm'];
        }

        if(!empty($opts['theme_styleguide'])) {
            $theme_styleguide = $opts['theme_styleguide'];
        }

        foreach ($themeDirs as $themeDir) {

            $this->pathToTheme = $this->release_docroot . '/' . $themeDir;
            $this->printTaskInfo('pathToTheme = ' . $this->pathToTheme);

            if (!$noNvm) {
                $this->collectionBuilder()->taskThemeBuild()
                    ->themeDirectory($this->pathToTheme)
                    ->nvmInstall()
                    ->nvmUse();
            }

            $this->collectionBuilder()->taskThemeBuild()
                ->themeDirectory($this->pathToTheme)
                ->npmInstall()
                ->npmRunBuild();

            if ($theme_styleguide) {
                $this->collectionBuilder()->taskThemeBuild()
                    ->themeDirectory($this->pathToTheme)
                    ->npmRunStyleGuide();
            }

            $this->collectionBuilder()->taskThemeBuild()
                ->run();
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
            0,
            'ReleaseBuild',
            ['time' => $this->getExecutionTime()]
        );
    }
}
