<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

/**
 * These tests check invalid requests return the appropriate errors.
 **/
class BadRequestsTest extends TestCase
{
    public function testUnsupportedFormat()
    {
        $res = $this->callApi('GET', '/api/success?_format=foo');
        $this->assertSame(415, $res->getStatusCode());
    }

    public function testInvalidJsonp()
    {
        //correct call
        $res = $this->callApi('GET', '/api/success?_format=jsonp&_callback=myFunc');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue(0 === strpos($res->getContent(), 'myFunc'));

        //no callback param
        $res = $this->callApi('GET', '/api/success?_format=jsonp');
        $this->assertSame(400, $res->getStatusCode());
        $body = json_decode($res->getContent(), true);
        $this->assertSame(400, $body['response']['code']);
        $this->assertSame('The [_callback] parameter is required for JSONP responses.', $body['response']['message']);

        //wrong method
        $res = $this->callApi('POST', '/api/success?_format=jsonp&_callback=myFunc');
        $this->assertSame(400, $res->getStatusCode());
        $body = json_decode($res->getContent(), true);
        $this->assertSame(400, $body['response']['code']);
        $this->assertSame('JSONP can only be used with GET requests.', $body['response']['message']);
    }

}
