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
        $data = json_decode($this->callApi('GET', '/api/override/success')->getContent(), true);
        $this->assertTrue(isset($data['person']));
        $this->assertFalse(isset($data['response']));

        $data = json_decode($this->callApi('GET', '/api/success')->getContent(), true);
        $this->assertTrue(isset($data['person']));
        $this->assertTrue(isset($data['response']));
        $this->assertSame(200, $data['response']['code']);
        $this->assertSame('OK', $data['response']['message']);
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

}
