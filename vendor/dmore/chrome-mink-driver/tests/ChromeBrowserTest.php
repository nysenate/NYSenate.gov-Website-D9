<?php

namespace DMore\ChromeDriverTests;

use DMore\ChromeDriver\ChromeBrowser as Browser;
use DMore\ChromeDriver\HttpClient;
use PHPUnit\Framework\TestCase;

class ChromeBrowserTest extends TestCase
{
    public function testInformativeExceptionIfChromeConnectionFailed()
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

        $browser = new Browser('http://localhost:9222');
        $browser->setHttpClient($client);
        $browser->setHttpUri('http://localhost:9222');
        $browser->start();
    }
}
