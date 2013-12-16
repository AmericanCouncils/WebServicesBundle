<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

/**
 * These tests check various response formats are returned as expected.
 **/
class RequestInputTest extends TestCase
{
    public function testSimpleIncomingData()
    {
        $this->markTestSkipped();
        $data = array(
            'age' => 27
        );

        $res = $this->callApi('PUT', '/api/people/simple/1.json', array(), array(), array('CONTENT_TYPE' => 'application/json'), json_encode($data));
        $returned = json_decode($res->getContent(), true);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame(27, $returned['person']['age']);
        $this->assertSame('John', $returned['person']['name']);
    }

    public function testComplexIncomingData()
    {
        $this->markTestSkipped();

        //TODO... think about how this should really behave - it may need to be configurable depending on the situation, in
        //which case there needs to be a separate set of unit tests for covering those scenarios
    }
}
