<?php

namespace DMore\ChromeDriverTests;

use DMore\ChromeDriver\ChromeDriver;
use PHPUnit\Framework\TestCase;

/**
 * Note that the majority of driver test coverage is provided via minkphp/driver-testsuite.
 *
 * Consider building on coverage there first!
 */
class ChromeDriverTestBase extends TestCase
{
    /**
     * @var ChromeDriver
     */
    protected $driver;

    /**
     * {inheritDoc}
     */
    protected function setUp(): void
    {
        $this->driver = $this->getDriver();
    }

    /**
     * @return ChromeDriver
     */
    private function getDriver(): ChromeDriver
    {
        $options = [
            'domWaitTimeout' => ChromeDriver::$domWaitTimeoutDefault,
            'socketTimeout' => ChromeDriver::$socketTimeoutDefault,
        ];
        return new ChromeDriver('http://localhost:9222', null, 'about:blank', $options);
    }
}
