<?php

namespace DMore\ChromeDriverTests;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\DriverException;

/**
 * Note that the majority of driver test coverage is provided via minkphp/driver-testsuite.
 *
 * Consider building on coverage there first!
 */
class ChromeDriverBasicsTest extends ChromeDriverTestBase
{
    /**
     * Validate test content is matched.
     *
     * @throws DriverException
     */
    public function testVisitDataUrl()
    {
        $html = <<<'HTML'
<html>
<body>
sample text
</body>
</html>
HTML;
        $url = "data:text/html;charset=utf-8,{$html}";
        $this->driver->visit($url);
        $this->assertStringContainsString("sample text", $this->driver->getContent());
    }

    /**
     * Validate exception when xpath object not found.
     *
     * @throws DriverException
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function testNotMatchingFormInput()
    {
        $this->expectException(ElementNotFoundException::class);
        $this->expectExceptionMessage('Tag matching xpath "//input[./@name="input2"]" not found.');

        $html = <<<'HTML'
<html>
<body>
<form id="test">
    <input name="input1" value="foo">
    <input type="submit">
</form>
</body>
</html>
HTML;

        $url = "data:text/html;charset=utf-8,{$html}";
        $this->driver->visit($url);
        $this->driver->setValue('//input[./@name="input2"]', 'bar');
    }

    /**
     * Validate we can populate text fields with a variety of characters.
     *
     * @dataProvider sampleTextProvider
     */
    public function testSetValueSampleText($input)
    {
        $xpath = '//input[./@name="input1"]';

        $html = <<<'HTML'
<html>
<body>
<form id="test">
    <input name="input1" value="foo">
    <input type="submit">
</form>
</body>
</html>
HTML;

        $url = "data:text/html;charset=utf-8,{$html}";
        $this->driver->visit($url);
        $this->driver->setValue($xpath, $input);
        $this->assertSame($input, $this->driver->getValue($xpath));
    }

    /**
     * Data provider for text cases.
     *
     * Should contain input strings which validate behaviour beyond simple latin text.
     */
    public function sampleTextProvider()
    {
        return [
            ['Strawberries are nice'],
            ['He pai nga rōpere'],
            ['الفراولة لطيفة'],
            ['Kloß sind schön'], // #105
            ['いちごはいいです'],
            ['sýr je pěkný'],
        ];
    }

    /**
     * Validate getConsoleMessages() retrieves console messages.
     */
    public function testConsoleMessages()
    {
        $bodyContent = 'some text here';
        $html = <<<HTML
<html>
<body id="content">{$bodyContent}</body>
</html>
HTML;
        $url = "data:text/html;charset=utf-8,{$html}";
        $this->driver->visit($url);
        $this->driver->evaluateScript("console.log(document.getElementById('content').innerText)");
        $messages = $this->driver->getConsoleMessages();
        $this->assertSame($bodyContent, $messages[0]['text']);
    }
}
