<?php

namespace DMore\ChromeDriverTests;

use Behat\Mink\Tests\Driver\AbstractConfig;
use DMore\ChromeDriver\ChromeDriver;

class ChromeDriverConfig extends AbstractConfig
{
    public static function getInstance()
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver()
    {
        return new ChromeDriver($_SERVER['CHROME_URL'], null, $_SERVER['WEB_FIXTURES_HOST'], ['socketTimeout' => 1]);
    }

    /**
     * {@inheritdoc}
     */
    protected function supportsCss()
    {
        return true;
    }
}
