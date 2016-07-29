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
namespace Cascade\Tests\Config\Loader\ClassLoader;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Registry;

use Cascade\Config\Loader\ClassLoader\LoggerLoader;

/**
 * Class LoggerLoaderTest
 *
 * @author Raphael Antonmattei <rantonmattei@theorchard.com>
 */
class LoggerLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tear down function
     */
    public function tearDown()
    {
        parent::tearDown();
        Registry::clear();
    }

    public function testConstructor()
    {
        $loader = new LoggerLoader('testLogger');

        $this->assertTrue(Registry::hasLogger('testLogger'));
    }

    public function testResolveHandlers()
    {
        $options = array(
            'handlers' => array('test_handler_1', 'test_handler_2')
        );
        $handlers = array(
            'test_handler_1' => new TestHandler(),
            'test_handler_2' => new TestHandler()
        );
        $loader = new LoggerLoader('testLogger', $options, $handlers);

        $this->assertEquals(
            array_values($handlers),
            $loader->resolveHandlers($options, $handlers)
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testResolveHandlersWithMismatch()
    {
        $options = array(
            'handlers' => array('unexisting_handler', 'test_handler_2')
        );
        $handlers = array(
            'test_handler_1' => new TestHandler(),
            'test_handler_2' => new TestHandler()
        );
        $loader = new LoggerLoader('testLogger', $options, $handlers);

        // This should throw an InvalidArgumentException
        $loader->resolveHandlers($options, $handlers);
    }

    public function testResolveProcessors()
    {
        $dummyClosure = function () {
            // Empty function
        };
        $options = array(
            'processors' => array('test_processor_1', 'test_processor_2')
        );
        $processors = array(
            'test_processor_1' => $dummyClosure,
            'test_processor_2' => $dummyClosure
        );

        $loader = new LoggerLoader('testLogger', $options, array(), $processors);

        $this->assertEquals(
            array_values($processors),
            $loader->resolveProcessors($options, $processors)
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testResolveProcessorsWithMismatch()
    {
        $dummyClosure = function () {
            // Empty function
        };
        $options = array(
            'processors' => array('unexisting_processor', 'test_processor_2')
        );
        $processors = array(
            'test_processor_1' => $dummyClosure,
            'test_processor_2' => $dummyClosure
        );

        $loader = new LoggerLoader('testLogger', $options, array(), $processors);

        // This should throw an InvalidArgumentException
        $loader->resolveProcessors($options, $processors);
    }

    public function testResolveParent()
    {
        $options = array(
            'inherit' => true
        );
        $loggerA = new Logger('loggerA');
        $loggerB = new Logger('loggerB');
        $instantiatedLoggers = array(
            'loggerA' => $loggerA,
            'loggerB' => $loggerB
        );

        $loader = new LoggerLoader('loggerA.child', $options, array(), array(), $instantiatedLoggers);
        $logger = $loader->load();

        $this->assertTrue($logger instanceof Logger);
        $this->assertEquals($logger->getParent(), $loggerA);
    }

    public function testResolveParentWithRoot()
    {
        $options = array(
            'inherit' => true
        );
        $root = new Logger('root');

        $instantiatedLoggers = array(
            'root' => $root
        );

        $loader = new LoggerLoader('childA', $options, array(), array(), $instantiatedLoggers);
        $logger = $loader->load();

        $this->assertEquals($logger->getParent(), $root);
    }

    public function testResolveParentWithRootAndChild()
    {
        $options = array(
            'inherit' => true
        );
        $root = new Logger('root');

        $instantiatedLoggers = array(
            'root' => $root
        );

        $loader = new LoggerLoader('loggerA.child', $options, array(), array(), $instantiatedLoggers);
        $loggerAChild = $loader->load();

        $this->assertEquals($loggerAChild->getParent(), $root);
    }

    public function testResolveParentWithDefaultAndGrandChild()
    {
        $options = array(
            'inherit' => true
        );
        $root = new Logger('root');

        $instantiatedLoggers = array(
            'root' => $root
        );

        $loader = new LoggerLoader('loggerA.child.grandchild', $options, array(), array(), $instantiatedLoggers);
        $loggerAGrandChild = $loader->load();

        $this->assertEquals($loggerAGrandChild->getParent(), $root);
    }

    public function testResolveParentWhenInheritIsFalse()
    {
        $options = array(
            'inherit' => false
        );
        $loggerA = new Logger('loggerA');
        $loggerB = new Logger('loggerB');
        $instantiatedLoggers = array(
            'loggerA' => $loggerA,
            'loggerB' => $loggerB
        );

        $loader = new LoggerLoader('loggerA.child', $options, array(), array(), $instantiatedLoggers);
        $logger = $loader->load();

        $this->assertTrue($logger instanceof Logger);
        $this->assertEquals($logger->getParent(), null);
    }

    public function testResolveParentWithNestedParents()
    {
        $options = array(
            'inherit' => true
        );

        $loggerA = new Logger('loggerA');
        $instantiatedLoggers = array(
            'loggerA' => $loggerA
        );

        $loader = new LoggerLoader('loggerA.child', $options, array(), array(), $instantiatedLoggers);
        $loggerAChild = $loader->load();
        $instantiatedLoggers['loggerA.child'] = $loggerAChild;

        $loader = new LoggerLoader('loggerA.child.grandchild', $options, array(), array(), $instantiatedLoggers);
        $loggerAGrandChild = $loader->load();

        $this->assertEquals($loggerAGrandChild->getParent(), $loggerAChild);
        $this->assertEquals($loggerAChild->getParent(), $loggerA);
    }

    public function testResolveParentWithSingleChildInheritance()
    {
        $optionsA = array(
            'inherit' => true
        );

        $optionsB = array(
            'inherit' => false
        );

        $loggerA = new Logger('loggerA');
        $instantiatedLoggers = array(
            'loggerA' => $loggerA
        );

        $loader = new LoggerLoader('loggerA.child', $optionsA, array(), array(), $instantiatedLoggers);
        $loggerAChild = $loader->load();
        $instantiatedLoggers['loggerA.child'] = $loggerAChild;

        $loader = new LoggerLoader('loggerA.child.grandchild', $optionsB, array(), array(), $instantiatedLoggers);
        $loggerAGrandChild = $loader->load();

        $this->assertEquals($loggerAGrandChild->getParent(), null);
        $this->assertEquals($loggerAChild->getParent(), $loggerA);
    }

    public function testResolveParentWithSingleGrandChildInheritance()
    {
        $optionsA = array(
            'inherit' => true
        );

        $optionsB = array(
            'inherit' => false
        );

        $loggerA = new Logger('loggerA');
        $instantiatedLoggers = array(
            'loggerA' => $loggerA
        );

        $loader = new LoggerLoader('loggerA.child', $optionsB, array(), array(), $instantiatedLoggers);
        $loggerAChild = $loader->load();
        $instantiatedLoggers['loggerA.child'] = $loggerAChild;

        $loader = new LoggerLoader('loggerA.child.grandchild', $optionsA, array(), array(), $instantiatedLoggers);
        $loggerAGrandChild = $loader->load();

        $this->assertEquals($loggerAGrandChild->getParent(), $loggerAChild);
        $this->assertEquals($loggerAChild->getParent(), null);
    }

    public function testResolveParentWithIntergenerationalInheritance()
    {
        $options = array(
            'inherit' => true
        );

        $loggerA = new Logger('loggerA');
        $instantiatedLoggers = array(
            'loggerA' => $loggerA
        );

        $loader = new LoggerLoader('loggerA.child.grandchild', $options, array(), array(), $instantiatedLoggers);
        $loggerAGrandChild = $loader->load();

        $this->assertEquals($loggerAGrandChild->getParent(), $loggerA);
    }

    public function testLoad()
    {
        $options = array(
            'handlers' => array('test_handler_1', 'test_handler_2'),
            'processors' => array('test_processor_1', 'test_processor_2')
        );
        $handlers = array(
            'test_handler_1' => new TestHandler(),
            'test_handler_2' => new TestHandler()
        );
        $dummyClosure = function () {
            // Empty function
        };
        $processors = array(
            'test_processor_1' => $dummyClosure,
            'test_processor_2' => $dummyClosure
        );

        $loader = new LoggerLoader('testLogger', $options, $handlers, $processors);
        $logger = $loader->load();

        $this->assertTrue($logger instanceof Logger);
        $this->assertEquals(array_values($handlers), $logger->getHandlers());
        $this->assertEquals(array_values($processors), $logger->getProcessors());
    }
}
