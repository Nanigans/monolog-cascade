<?php
/**
 * This file is part of the Monolog Cascade package.
 *
 * (c) Raphael Antonmattei <rantonmattei@theorchard.com>
 * (c) The Orchard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cascade\Tests;

use Monolog\Logger;
use Monolog\Registry;
use Monolog\Handler\NullHandler;

use Cascade\Cascade;
use Cascade\Tests\Fixtures;

/**
 * Class CascadeTest
 *
 * @author Raphael Antonmattei <rantonmattei@theorchard.com>
 */
class CascadeTest extends \PHPUnit_Framework_TestCase
{
    public function teardown()
    {
        Registry::clear();
        parent::teardown();
    }

    public function testCreateLogger()
    {
        $logger = Cascade::createLogger('test');

        $this->assertTrue($logger instanceof Logger);
        $this->assertEquals('test', $logger->getName());
        $this->assertTrue(Registry::hasLogger('test'));
    }

    public function testCreateLoggerWithParent()
    {
        $parent = Cascade::createLogger('test');
        $child = Cascade::createLogger('child', array(), array(), $parent);
        $this->assertEquals($child->getParent(), $parent);
    }

    public function testCreateLoggerWithoutInheritance()
    {
        $parent = Cascade::createLogger('loggerA');
        $orphan = Cascade::createLogger('loggerA.child');
        $this->assertNotEquals($orphan->getParent(), $parent);
    }

    public function testGetLoggerWithoutInheritance()
    {
        $parent = Cascade::getLogger('loggerA');
        $orphan = Cascade::getLogger('loggerA.child');
        $this->assertNotEquals($orphan->getParent(), $parent);
    }

    public function testGetLoggerWithInheritance()
    {
        Cascade::setInheritOnUndefined(true);
        $parent = Cascade::getLogger('loggerA');
        $child = Cascade::getLogger('loggerA.child');
        $this->assertEquals($child->getParent(), $parent);
    }

    public function testGetLoggerWithInheritanceAndNonAlphanumericParentNames()
    {
        Cascade::setInheritOnUndefined(true);
        $parent = Cascade::getLogger('logger_Aé');
        $child = Cascade::getLogger('logger_Aé.child_A');
        $this->assertEquals($child->getParent(), $parent);
    }

    public function testGetLoggerWithInheritanceFalse()
    {
        Cascade::setInheritOnUndefined(false);
        $parent = Cascade::getLogger('loggerA');
        $orphan = Cascade::getLogger('loggerA.child');
        $this->assertNotEquals($orphan->getParent(), $parent);
    }

    public function testGetLoggerWithNestedInheritance()
    {
        Cascade::setInheritOnUndefined(true);
        $parent = Cascade::getLogger('loggerA');
        $grandchild = Cascade::getLogger('loggerA.child.grandchild');
        $this->assertEquals($grandchild->getParent(), $parent);
    }

    public function testGetLoggerWithRootInheritance()
    {
        Cascade::setInheritOnUndefined(true);
        $root = Cascade::getLogger('root');
        $grandchild = Cascade::getLogger('loggerA.child.grandchild');
        $this->assertEquals($grandchild->getParent(), $root);
    }

    public function testGetLoggerWithRootInheritanceHasNullHandler()
    {
        Cascade::setInheritOnUndefined(true);
        $root = Cascade::getLogger('root');
        $grandchild = Cascade::getLogger('loggerA.child.grandchild');
        $null_handler = new NullHandler();
        $grandchild_handlers = $grandchild->getHandlers();
        $this->assertEquals(count($grandchild_handlers), 1);
        $this->assertEquals($grandchild_handlers[0], $null_handler);
    }

    public function testGetLoggerWithNoInheritanceHasNoHandler()
    {
        Cascade::setInheritOnUndefined(false);
        $undefined_logger = Cascade::getLogger('SomeUndefinedLogger');
        $handlers = $undefined_logger->getHandlers();
        $this->assertEmpty($handlers);
    }

    public function testGetLoggerWithRootAndParent()
    {
        Cascade::setInheritOnUndefined(true);
        $root = Cascade::getLogger('root');
        $parent = Cascade::getLogger('loggerA.child');
        $grandchild = Cascade::getLogger('loggerA.child.grandchild');
        $this->assertEquals($grandchild->getParent(), $parent);
    }

    public function testGetLoggerWithNoInheritanceDoesNotCreateRoot()
    {
        Cascade::setInheritOnUndefined(false);
        $undefined_logger = Cascade::getLogger('ThisLoggerShouldNotInheritFromRoot');
        $root_logger = $undefined_logger->getParent();
        $this->assertNull($root_logger);
    }

    public function testRegistry()
    {
        // Creates the logger and push it to the registry
        $logger = Cascade::logger('test');

        // We should get the logger from the registry this time
        $logger2 = Cascade::logger('test');
        $this->assertSame($logger, $logger2);
    }

    public function testRegistryWithChildren()
    {
        $parent = Cascade::logger('loggerA');
        $child = Cascade::logger('loggerA.child');
        $child2 = Cascade::logger('loggerA.child');
        $this->assertSame($child, $child2);
    }

    public function testRegistryWithChildrenAndInheritance()
    {
        Cascade::setInheritOnUndefined(true);
        $parent = Cascade::logger('loggerA');
        $child = Cascade::logger('loggerA.child');
        $child2 = Cascade::logger('loggerA.child');
        $this->assertSame($child, $child2);
    }

    public function testRegistryWithRootInheritance()
    {
        Cascade::setInheritOnUndefined(true);
        $undefined_logger = Cascade::logger('UndefinedLogger');
        $default_root = $undefined_logger->getParent();
        $user_generated_root = Cascade::logger('root');
        $this->assertSame($default_root, $user_generated_root);
    }

    public function testResetConfiguration()
    {
        Cascade::logger('root');
        Cascade::logger('LoggerA');
        Cascade::logger('LoggerA.Child');
        $this->assertTrue(Registry::hasLogger('root'));
        $this->assertTrue(Registry::hasLogger('LoggerA'));
        $this->assertTrue(Registry::hasLogger('LoggerA.Child'));

        Cascade::resetConfiguration();
        $this->assertFalse(Registry::hasLogger('root'));
        $this->assertFalse(Registry::hasLogger('LoggerA'));
        $this->assertFalse(Registry::hasLogger('LoggerA.Child'));
    }

    public function testResetConfigurationWithConfigFile()
    {
        Cascade::fileConfig('./Fixtures/fixture_config.yml');
        $this->assertNotNull(Cascade::getConfig());

        Cascade::resetConfiguration();
        $this->assertNull(Cascade::getConfig());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegistryWithInvalidName()
    {
        $logger = Cascade::getLogger(null);
    }

    public function testFileConfig()
    {
        $options = Fixtures::getPhpArrayConfig();
        Cascade::fileConfig($options);
        $this->assertInstanceOf('Cascade\Config', Cascade::getConfig());
    }
}
