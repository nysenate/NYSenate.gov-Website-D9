<?php
namespace tests\Behat\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension;
use DMore\ChromeExtension\Behat\ServiceContainer\Driver\ChromeFactory;
use PHPUnit\Framework\TestCase;

class ChromeExtensionTest extends TestCase
{
    /** @var ChromeExtension */
    private $extension;

    public function setUp() : void
    {
        $this->extension = new ChromeExtension();
    }

    public function testConfigKey()
    {
        $this->assertSame('chrome', $this->extension->getConfigKey());
    }

    public function testItRegistersMinkDriver()
    {
        $mink_extension = $this->createMock(MinkExtension::class);
        $mink_extension->expects($this->once())->method('getConfigKey')->will($this->returnValue('mink'));
        $extension_manager = new ExtensionManager([$mink_extension]);

        $mink_extension->expects($this->once())->method('registerDriverFactory')
            ->with($this->callback(function ($factory) {
                return $factory instanceof ChromeFactory;
            }));
        $this->extension->initialize($extension_manager);
    }
}
