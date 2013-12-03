<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

class ConfigurableFeaturesTest extends TestCase
{
    public function testCallApi()
    {
        $expected = 'hello world';
        $actual = $this->callApi('GET', '/no-api')->getContent();
        $this->assertSame($expected, $actual);
    }

    public function testIncludeResponseData()
    {

    }

    public function testChangeResponseFormat()
    {
        //try in query _format
        //try in path{.format}
    }

    public function testSuppressResponseCodes()
    {

    }

    public function testIncludeExceptionData()
    {

    }

    public function testJsonpResponse()
    {

    }

    public function testAdditionalHeaders()
    {

    }

    public function testResponseFormatHeaders()
    {

    }

    public function testExceptionMap()
    {

    }

}
