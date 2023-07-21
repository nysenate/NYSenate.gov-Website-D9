<?php

namespace DMore\ChromeDriverTests;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\DriverException;
use DMore\ChromeDriver\ChromeBrowser as Browser;
use DMore\ChromeDriver\ChromeDriver;
use DMore\ChromeDriver\HttpClient;
use PHPUnit\Framework\TestCase;
use DMore\ChromeDriverTests\ChromeDriverTestBase;

/**
 * Note that the majority of driver test coverage is provided via minkphp/driver-testsuite.
 *
 * Consider building on coverage there first!
 */
class ChromeDriverExceptionsTest extends ChromeDriverTestBase
{
    /**
     * Validate attachFile() throws exception if file is not found.
     */
    public function testAttachFileThrowsExceptionIfNoSuchFile()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage("ChromeDriver was unable to find file '/does-not-exist' to attach it.");

        $html = <<<'HTML'
<html>
<body>
<form>
    <input type="file" name="attach" />
    <input type="submit" value="Upload file" />
</form>
</body>
</html>
HTML;
        $url = "data:text/html;charset=utf-8,{$html}";
        $this->driver->visit($url);
        $this->driver->attachFile('//input[./@name="attach"]', '/does-not-exist');
    }
}
