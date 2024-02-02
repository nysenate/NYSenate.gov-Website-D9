<?php

namespace DMore\ChromeDriverTests;

use DMore\ChromeDriver\ChromeBrowser;
use DMore\ChromeDriver\ChromeDriver;
use DMore\ChromeDriver\HttpClient;
use WebSocket\TimeoutException;

/**
 * Note that the majority of driver test coverage is provided via minkphp/driver-testsuite.
 *
 * Consider building on coverage there first!
 */
class ChromeDriverConnectionTest extends ChromeDriverTestBase
{
    /**
     * Unable to connect to nonsense ChromeDriver URL.
     */
    public function testRuntimeExceptionIfNotConnected()
    {
        $nonWorkingUrl = 'http://localhost:12345';
        $this->driver = new ChromeDriver($nonWorkingUrl, null, 'about:blank', []);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not fetch version information from http://localhost:12345/json/version.');
        $this->driver->visit('about:blank');
        // Content read is necessary to trigger timeout.
        $this->driver->getContent();
    }

    /**
     * JS confirm() will lead the browser to time out.
     */
    public function testTimeoutExceptionIfResponseBlocked()
    {
        // We don't want to wait the default 10s to time out.
        $options = [
            'socketTimeout' => 1,
        ];
        $this->driver = new ChromeDriver('http://localhost:9222', null, 'about:blank', $options);
        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Client read timeout');
        $script = "confirm('Is the browser blocked? (yes, it is)');";
        $this->driver->visit('about:blank');
        $this->driver->evaluateScript($script);
        // Content read is necessary to trigger timeout.
        $this->driver->getContent();
    }

    /**
     *
     */
    public function testRuntimeExceptionIfClientConnectionFails()
    {
        $client = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client->expects($this->any())
            ->method('get')
            ->willReturn('Error Happened!');

        $this->expectException(\RuntimeException::class);
        // Test that chromium response is included in exception message.
        $this->expectExceptionMessageMatches('/Error Happened!/');

        $browser = new ChromeBrowser('http://localhost:9222');
        $browser->setHttpClient($client);
        $browser->setHttpUri('http://localhost:9222');
        $browser->start();
    }
}
