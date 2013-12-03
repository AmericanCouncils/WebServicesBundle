<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\Util\JmsDataTypeParser;

class JmsDataTypeParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $i = new JmsDataTypeParser();
        $this->assertTrue($i instanceof JmsDataTypeParser);
    }
}
