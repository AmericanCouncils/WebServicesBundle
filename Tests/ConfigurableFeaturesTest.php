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
        $this->assertSame(200, $res->getStatusCode());

        $data = json_decode($res->getContent(), true);
        $this->assertTrue(isset($data['person']));
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

    public function testSuppressResponseCodes()
    {
        //codes should NOT be suppressed
        $res = $this->callApi('GET','/api/override/fail?_suppress_codes=true');
        $body = json_decode($res->getContent(), true);
        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame(500, $body['response']['code']);

        //codes should be suppressed
        $res = $this->callApi('GET','/api/fail?_suppress_codes=true');
        $body = json_decode($res->getContent(), true);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame(500, $body['response']['code']);
    }

    public function testIncludeExceptionData()
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

    public function testAllowJsonp()
    {
        $res = $this->callApi('GET','/api/override/success?_format=jsonp&_callback=myFunc');
        $this->assertSame(415, $res->getStatusCode());

        $res = $this->callApi('GET','/api/success?_format=jsonp&_callback=myFunc');
        $this->assertSame(200, $res->getStatusCode());
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
        $res = $this->callApi('GET','/api/override/fail');
        $this->assertSame(500, $res->getStatusCode());

        $res = $this->callApi('GET','/api/fail');
        $this->assertSame(500, $res->getStatusCode());

        $res = $this->callApi('GET','/api/fail/exception-map');
        $this->assertSame(403, $res->getStatusCode());
        $body = json_decode($res->getContent(), true);
        $this->assertSame(403, $body['response']['code']);
        $this->assertSame('Custom error message', $body['response']['message']);
    }

    public function testResponseFormatHeaders()
    {
        $res = $this->callApi('GET', '/api/success?_format=json');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success?_format=jsonp&_callback=myFunc');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/javascript', $res->headers->get('Content-Type'));

        #this one overridden from the app config
        $res = $this->callApi('GET', '/api/success?_format=yml');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('text/x-yaml; charset=UTF-8', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success?_format=xml');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/xml', $res->headers->get('Content-Type'));
    }

}
