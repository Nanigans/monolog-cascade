<?php

namespace Cascade\Tests;

use Cascade\Util;

class UtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing snake case to camel case function
     * @dataProvider camelizeProvider
     */
    public function testCamelize($propertyName, $expected)
    {
        $this->assertEquals($expected, Util::camelize($propertyName));
    }

    public function camelizeProvider()
    {
        return array(
        array('some_thing', 'someThing'),
        array('Some_Thing', 'someThing'),
        array('with_three_words', 'withThreeWords'),
        array('Upper_lower', 'upperLower'),
        );
    }
}
