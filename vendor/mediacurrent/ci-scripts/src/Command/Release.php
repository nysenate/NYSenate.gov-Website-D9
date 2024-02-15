<?php

namespace Mediacurrent\CiScripts\Command;

use Robo\Result;
use Symfony\Component\Console\Input\InputOption;

trait Release
{

    /**
     * Release Build command.
     *
     * release:build runs the following -
     *
     *  create build directory
     *  checkout project repository
     *  checkout release repository
     *  sync files from poject to release as needed
     *  modify docroot directory as needed
     *  composer install
     *  commit changes to release repository
     *
     * deploy_host: Acquia
     *
     * Requires the following variables be set
     * for the project in config/config.yml:
     *
     * Acquia -
     *
     * project_repo: git@bitbucket.org:mediacurrent/mis_example.git
     * project_drupal_root: web
     * release_repo: development10@svn.devcloud.hosting.acquia.com:development.git
     * release_drupal_root: docroot
     * deploy_host: Acquia
     *
     * Pantheon -
     *
     * project_repo: git@bitbucket.org:mediacurrent/mis_example.git
     * release_repo: ssh://codeserver.dev.xxx@codeserver.dev.xxx.drush.in:2222/~/repository.git
     * deploy_host: Pantheon
     *
     * Hosts not deploying via git -
     *
     * release_repo: git@bitbucket.org:mediacurrent/drupal-project.git
     * deploy_host: generic or Blackmesh, etc
     *
     * @param string $deploy_host Host for Deployment ( Acquia, Pantheon)
     * @param string $build_branch Branch to build
     * @param string $release_tag Optional label for release
     *
     * @option $release_branch Specify release repository branch.
     * @option string $project_repo Specify source repository for release
     * @option string $release_repo Specify repository for release
     * @option $composer_install Runs composer install for committing artifacts.
     * @option $theme_build Runs default theme build task.  Specify path to theme.
     * @option $gitignore Do not commit these files or directories.
     * @option $no_nvm If this flag is present, use npm directly without running nvm install/use.
     * @option $theme_styleguide If this flag is present, also execute "npm run styleguide".
     * @option $clean Remove the build directory prior to building.
     *
     */
    public function releaseBuild(
        $deploy_host = null,
        $build_branch = 'develop',
        $release_tag = null,
        $opts = [
            'release_branch' => InputOption::VALUE_REQUIRED,
            'project_repo' => null,
            'release_repo' => null,
            'composer_install' => TRUE,
            'theme_build' => [],
            'gitignore' => [],
            'no_nvm' => FALSE,
            'theme_styleguide' => FALSE,
            'clean' => FALSE,
        ]
    ) {

        if (!$deploy_host && !empty($this->configuration['deploy_host'])) {
            $deploy_host = $this->configuration['deploy_host'];
        }

        if (empty($this->configuration['release_repo'])
            || !$deploy_host) {
            $this->yell('Configuration variables missing.  Consult help output.', 40, 'red');
            return Result::cancelled();
        }

        $release_branch = $build_branch;
        if (!empty($opts['release_branch'])) {
            $release_branch = $opts['release_branch'];
        }

        $project_repo = null;
        if (!empty($opts['project_repo'])) {
            $project_repo = $opts['project_repo'];
        }

        $release_repo = null;
        if (!empty($opts['release_repo'])) {
            $release_repo = $opts['release_repo'];
        }

        switch (strtolower($deploy_host)) {
            case 'acquia':
            case 'git':
            case 'pantheon':
                $this->taskReleaseBuild()
                    ->releaseBuildDirectories($opts)
                    ->releaseGitCheckoutProject($build_branch, $release_tag, $project_repo)
                    ->releaseGitCheckoutRelease($release_branch, $release_tag, $release_repo)
                    ->releaseSyncProject()
                    ->releaseSyncDocroot()
                    ->releaseSetDocroot()
                    ->releaseComposerInstall($opts)
                    ->releaseCleanupModuleVcs()
                    ->releaseThemeBuild($opts['theme_build'], $opts)
                    ->releaseCommit($release_tag, $opts['gitignore']);
                break;

            default:
                $this->taskReleaseBuild()
                    ->releaseBuildDirectories($opts)
                    ->releaseGitCheckoutRelease($build_branch, $release_tag, $release_repo)
                    ->releaseComposerInstall()
                    ->releaseThemeBuild($opts['theme_build'], $opts);
                break;
        }

        $this->taskReleaseBuild()
            ->run();
    }

    /**
     * Release Deploy Command.
     *
     * release deploy runs the following -
     *
     *  pushes deploy release to remote
     *
     * Requires the following variables be set
     * for the project in config/config.yml:
     *
     * Acquia ( git, Pantheon)-
     *
     * project_repo: git@bitbucket.org:mediacurrent/mis_example.git
     * release_repo: development10@svn.devcloud.hosting.acquia.com:development.git
     * deploy_host: Acquia
     *
     * Rsync -
     *
     * release_repo: git@bitbucket.org:mediacurrent/drupal-project.git
     * release_host_user: username
     * dev_release_host: server.example.com
     * release_deploy_dest: "/var/www/htdocs"
     *
     * @param string $deploy_host Host for Deployment ( Acquia, Pantheon, rsync)
     * @param string $build_branch Branch or environment to deploy
     * @param string $release_tag Optional label for release
     * @param array $opts
     *
     * @option $yes Deploy immediately without confirmation
     *
     */
    public function releaseDeploy(
        $deploy_host = null,
        $build_branch = 'develop',
        $release_tag = null,
        $opts = ['yes|y' => false]
    ) {
        if ($opts['yes'] || $this->confirm("Deploy release now. Are you sure?")) {
            if (!$deploy_host && !empty($this->configuration['deploy_host'])) {
                $deploy_host = $this->configuration['deploy_host'];
            }

            switch (strtolower($deploy_host)) {
                case 'acquia':
                case 'git':
                case 'pantheon':
                    $this->taskReleaseDeploy()
                        ->releaseDeployGit($build_branch, $release_tag)
                        ->run();
                    break;

                case 'blackmesh':
                case 'rsync':
                    $this->taskReleaseDeploy()
                        ->releaseDeployRsync($build_branch, $release_tag)
                        ->run();
                    break;

                default:
                    break;
            }
        }
    }
}
