<?php

namespace Mediacurrent\CiScripts\Command;

trait Theme
{

    /**
     * Theme Build command.
     *
     * theme:build runs the following -
     *
     *  nvm use
     *  npm install
     *  npm run build
     *
     * @param string $pathToTheme Absolute path to the theme directory.
     *
     * @option $no_nvm If this flag is present, use npm directly without running nvm install/use.
     *
     */
    public function themeBuild($pathToTheme = null, $opts = ['no_nvm' => FALSE])
    {
        if (!$opts['no_nvm']) {
            $this->taskThemeBuild()
                ->themeDirectory($pathToTheme)
                ->nvmInstall()
                ->nvmUse()
                ->npmInstall()
                ->npmRunBuild()
                ->run();
        }
        else {
            $this->taskThemeBuild()
                ->themeDirectory($pathToTheme)
                ->npmInstall()
                ->npmRunBuild()
                ->run();
        }
    }

    /**
     * Theme Compile command.
     *
     * theme:compile runs the following -
     *
     *  nvm use
     *  npm install
     *  npm run compile
     *
     * @param string $pathToTheme Absolute path to the theme directory.
     *
     * @option $no_nvm If this flag is present, use npm directly without running nvm install/use.
     *
     */
    public function themeCompile($pathToTheme = null, $opts = ['no_nvm' => FALSE])
    {
        if (!$opts['no_nvm']) {
            $this->taskThemeStyleGuide()
                ->themeDirectory($pathToTheme)
                ->nvmInstall()
                ->nvmUse()
                ->npmInstall()
                ->npmRunCompile()
                ->run();
        }
        else {
            $this->taskThemeStyleGuide()
                ->themeDirectory($pathToTheme)
                ->npmInstall()
                ->npmRunCompile()
                ->run();
        }
    }

    /**
     * Theme Style Guide command.
     *
     * theme:style-guide runs the following -
     *
     *  nvm use
     *  npm install
     *  npm run styleguide
     *
     * @param string $pathToTheme Absolute path to the theme directory.
     *
     * @option $no_nvm If this flag is present, use npm directly without running nvm install/use.
     *
     */
    public function themeStyleGuide($pathToTheme = null, $opts = ['no_nvm' => FALSE])
    {
        if (!$opts['no_nvm']) {
            $this->taskThemeStyleGuide()
                ->themeDirectory($pathToTheme)
                ->nvmInstall()
                ->nvmUse()
                ->npmInstall()
                ->npmRunStyleGuide()
                ->run();
        }
        else {
            $this->taskThemeStyleGuide()
                ->themeDirectory($pathToTheme)
                ->npmInstall()
                ->npmRunStyleGuide()
                ->run();
        }
    }

    /**
     * Theme Watch command.
     *
     * theme:watch runs the following -
     *
     *  nvm use
     *  npm install
     *  npm run watch
     *
     * @param string $pathToTheme Absolute path to the theme directory.
     *
     * @option $no_nvm If this flag is present, use npm directly without running nvm install/use.
     *
     */
    public function themeWatch($pathToTheme = null, $opts = ['no_nvm' => FALSE])
    {
        if (!$opts['no_nvm']) {
            $this->taskThemeWatch()
                ->themeDirectory($pathToTheme)
                ->nvmInstall()
                ->nvmUse()
                ->npmInstall()
                ->npmRunWatch()
                ->run();
        }
        else {
            $this->taskThemeWatch()
                ->themeDirectory($pathToTheme)
                ->npmInstall()
                ->npmRunWatch()
                ->run();
        }
    }
}
