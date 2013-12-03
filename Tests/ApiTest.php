<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

class ApiTest extends TestCase
{
    public function testCallApi()
    {
        $expected = 'hello world';
        $actual = $this->callApi('GET', '/no-api')->getContent();
        $this->assertSame($expected, $actual);
    }
}
