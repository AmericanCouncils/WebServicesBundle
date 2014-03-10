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
        $res = $this->callApi('GET', '/api/success?_format=foo', array('expectedCode' => 415));
    }

    public function testInvalidJsonp()
    {
        //correct call
        $res = $this->callApi('GET', '/api/success?_format=jsonp&_callback=myFunc');
        $this->assertTrue(0 === strpos($res->getContent(), 'myFunc'));

        //no callback param (result will be json due to error, even though we requested jsonp)
        $body = $this->callJsonApi('GET', '/api/success?_format=jsonp', array(
            'expectedCode' => 400
        ));
        $this->assertSame(400, $body['response']['code']);
        $this->assertSame(
            'The [_callback] parameter is required for JSONP responses.',
            $body['response']['message']
        );

        //wrong method (result will be json due to error, even though we requested jsonp)
        $body = $this->callJsonApi('POST', '/api/success?_format=jsonp&_callback=myFunc', array(
            'expectedCode' => 400
        ));
        $this->assertSame(400, $body['response']['code']);
        $this->assertSame(
            'JSONP can only be used with GET requests.',
            $body['response']['message']
        );
    }

}
