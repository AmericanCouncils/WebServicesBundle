<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

/**
 * These tests check various response formats are returned as expected.li
 **/
class RequestInputTest extends TestCase
{

    public function testSimpleIncomingData()
    {
        // $this->markTestSkipped();
        $data = array(
            'age' => 27
        );

        $returned = $this->callJsonApi('PUT', '/api/people/simple/1.json', array(
            'server' => array('CONTENT_TYPE' => 'application/json'),
            'content' => json_encode($data)
        ));

        $this->assertSame(27, $returned['person']['age']);
        $this->assertSame('John', $returned['person']['name']);
    }

    public function testComplexIncomingData()
    {
        $this->markTestSkipped();

        //TODO... think about how this should really behave - it may need to be
        // configurable depending on the situation, in which case there needs
        // to be a separate set of unit tests for covering those scenarios
    }
}
