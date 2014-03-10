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

        $expected = 'hello world';
        $actual = $res->getContent();
        $this->assertSame($expected, $actual);
    }

    public function testCallApiRoute()
    {
        $data = $this->callJsonApi('GET', '/api/success');
        $this->assertTrue(isset($data['person']));
    }

    public function testIncludeResponseData()
    {
        $data = $this->callJsonApi('GET', '/api/override/success');
        $this->assertTrue(isset($data['person']));
        $this->assertFalse(isset($data['response']));

        $data = $this->callJsonApi('GET', '/api/success');
        $this->assertTrue(isset($data['person']));
        $this->assertTrue(isset($data['response']));
        $this->assertSame('OK', $data['response']['message']);
    }

    public function testSuppressResponseCodes()
    {
        //codes should NOT be suppressed
        $body = $this->callJsonApi(
            'GET','/api/override/fail?_suppress_codes=true',
            array('expectedCode' => 500)
        );
        $this->assertSame(500, $body['response']['code']);

        //codes should be suppressed
        $body = $this->callJsonApi('GET','/api/fail?_suppress_codes=true');
        $this->assertSame(500, $body['response']['code']);
    }

    public function testIncludeExceptionData()
    {
        $body = $this->callJsonApi('GET','/api/override/fail', array('expectedCode' => 500));
        $this->assertFalse(isset($body['exception']));

        $body = $this->callJsonApi('GET','/api/fail', array('expectedCode' => 500));
        $this->assertTrue(isset($body['exception']));
    }

    public function testAllowJsonp()
    {
        $res = $this->callApi(
            'GET','/api/override/success?_format=jsonp&_callback=myFunc',
            array('expectedCode' => 415)
        );

        $res = $this->callApi('GET','/api/success?_format=jsonp&_callback=myFunc');
        $this->assertSame(0, strpos($res->getContent(), 'myFunc'));
    }

    public function testAdditionalHeaders()
    {
        $res = $this->callApi('GET', '/api/override/success');
        $this->assertFalse($res->headers->get('x-custom-acwebservices', false));

        $res = $this->callApi('GET', '/api/success');
        $this->assertSame($res->headers->get('x-custom-acwebservices'), 'foo-bar-baz');
    }

    public function testHttpExceptionMap()
    {
        $this->callJsonApi('GET','/api/override/fail', array('expectedCode' => 500));
        $this->callJsonApi('GET','/api/fail', array('expectedCode' => 500));

        $body = $this->callJsonApi('GET','/api/fail/exception-map', array('expectedCode' => 403));
        $this->assertSame(403, $body['response']['code']);
        $this->assertSame('Custom error message', $body['response']['message']);
    }

    public function testResponseFormatHeaders()
    {
        $res = $this->callApi('GET', '/api/success?_format=json');
        $this->assertSame('application/json', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success?_format=jsonp&_callback=myFunc');
        $this->assertSame('application/javascript', $res->headers->get('Content-Type'));

        #this one overridden from the app config
        $res = $this->callApi('GET', '/api/success?_format=yml');
        $this->assertSame('text/x-yaml; charset=UTF-8', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success?_format=xml');
        $this->assertSame('application/xml', $res->headers->get('Content-Type'));
    }

}
