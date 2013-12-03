<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

/**
 * These tests check that API behavior changes between sets of routes that configured differently.
 **/
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
        //try in path{._format}
    }

    public function testSuppressResponseCodes()
    {

    }

    public function testIncludeExceptionData()
    {

    }

    public function testAllowJsonp()
    {

    }

    public function testAdditionalHeaders()
    {

    }

    public function testHttpExceptionMap()
    {

    }

    public function testResponseFormatHeaders()
    {

    }

    public function testUnsupportedFormat()
    {

    }

    public function testBadJsonp()
    {

    }
}
