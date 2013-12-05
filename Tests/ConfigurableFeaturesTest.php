<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

/**
 * These tests check that API behavior changes between sets of routes that configured differently.
 **/
class ConfigurableFeaturesTest extends TestCase
{
    public function testCallNonApiRoute()
    {
        $res = $this->callApi('GET', '/no-api');
        $this->assertSame(200, $res->getStatusCode());

        $expected = 'hello world';
        $actual = $res->getContent();
        $this->assertSame($expected, $actual);
    }

    public function testCallApiRoute()
    {
        $res = $this->callApi('GET', '/api/success');
        var_dump($res->getContent());
        $this->assertSame(200, $res->getStatusCode());

        $data = json_decode($res->getContent(), true);
        $this->assertTrue(isset($data['person']));
    }

    public function _testIncludeResponseData()
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

    public function _testSuppressResponseCodes()
    {
        $res = $this->callApi('GET','/api/override/fail?_suppress_codes=true');
        $body = json_decode($res->getContent(), true);
        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame(500, $body['response']['code']);


        $res = $this->callApi('GET','/api/fail?_suppress_codes=true');
        $body = json_decode($res->getContent(), true);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame(500, $body['response']['code']);
    }

    public function _testIncludeExceptionData()
    {
        $res = $this->callApi('GET','/api/override/fail');
        $body = json_decode($res->getContent(), true);
        $this->assertFalse(isset($body['exception']));
        $this->assertSame(500, $res->getStatusCode());


        $res = $this->callApi('GET','/api/fail');
        $body = json_decode($res->getContent(), true);
        $this->assertTrue(isset($body['exception']));
        $this->assertSame(500, $res->getStatusCode());
    }

    public function _testAllowJsonp()
    {

    }

    public function _testAdditionalHeaders()
    {

    }

    public function _testHttpExceptionMap()
    {

    }

    public function _testResponseFormatHeaders()
    {

    }

}
