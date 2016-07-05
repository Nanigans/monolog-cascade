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
        $parent = Cascade::getLogger('logger_A');
        $child = Cascade::getLogger('logger_A.child_A');
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


    public function testGetLoggerWithDefaultInheritance()
    {
        Cascade::setInheritOnUndefined(true);
        $default = Cascade::getLogger('default');
        $grandchild = Cascade::getLogger('loggerA.child.grandchild');
        $this->assertEquals($grandchild->getParent(), $default);
    }

    public function testGetLoggerWithDefaultAndParent()
    {
        Cascade::setInheritOnUndefined(true);
        $default = Cascade::getLogger('default');
        $parent = Cascade::getLogger('loggerA.child');
        $grandchild = Cascade::getLogger('loggerA.child.grandchild');
        $this->assertEquals($grandchild->getParent(), $parent);
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
