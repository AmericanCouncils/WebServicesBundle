<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

/**
 * These tests check various response formats are returned as expected.
 **/
class ResponseFormatTest extends TestCase
{

    public function testGetJson()
    {
        $res = $this->callApi('GET', '/api/success?_format=json');
        $this->assertSame('application/json', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), '{"person":'));
    }

    public function testGetJsonp()
    {
        $res = $this->callApi('GET', '/api/success?_format=jsonp&_callback=myFunc');
        $this->assertSame('application/javascript', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), 'myFunc({"person":'));
    }

    public function testGetYaml()
    {
        $res = $this->callApi('GET', '/api/success?_format=yml');
        $this->assertSame('text/x-yaml; charset=UTF-8', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), 'person:'));
    }

    public function testGetXml()
    {
        $res = $this->callApi('GET', '/api/success?_format=xml');
        $this->assertSame('application/xml', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), '<?xml version="1.0" encoding="UTF-8"?>'));
    }

    /**
     * Ensures that you can change the response format both explicitly in
     * the query string, but also in the path, if the routing is configured
     * to recognize it.
     **/
    public function testSetFormatAsPathArgument()
    {
        $res = $this->callApi('GET', '/api/success');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success?_format=json');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success.json');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success.yml');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('text/x-yaml; charset=UTF-8', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success.xml');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/xml', $res->headers->get('Content-Type'));

        $res = $this->callApi('GET', '/api/success.jsonp?_callback=myFunc');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/javascript', $res->headers->get('Content-Type'));
    }

    public function testGetTemplate()
    {
        //expect json
        $res = $this->callApi('GET', '/api/templates/people.json');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), '{"people":'));

        //expect html
        $res = $this->callApi('GET', '/api/templates/people.html');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), '<!doctype html>'));

        //expect xhtml
        $res = $this->callApi('GET', '/api/templates/people.xhtml');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/xhtml+xml', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), '<!doctype html>'));

        //expect csv
        $res = $this->callApi('GET', '/api/templates/people.csv');
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', $res->headers->get('Content-Type'));
        $this->assertTrue(0 === strpos($res->getContent(), 'ID, Name, Age'));
    }

    public function testGetJsonWithSerializationContext()
    {
        $res = $this->callApi('GET', '/api/serializer/people.json');
        $this->assertSame(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        foreach ($data['people'] as $person) {
            $this->assertTrue(isset($person['name']));
            $this->assertTrue(isset($person['age']));
        }

        $res = $this->callApi('GET', '/api/serializer/people/context.json');
        $this->assertSame(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        foreach ($data['people'] as $person) {
            $this->assertTrue(isset($person['name']));
            $this->assertFalse(isset($person['age']));
        }
    }

    public function testGetJsonWithApiVersionConstraint()
    {
        //TODO: discuss dealing with API versions before implementing anything.
        $this->markTestSkipped();

        //possible ways for clients to specify a version:
        //* query param (_version)
        //* custom header (x-api-version)
        //* something else?

        //regardless of how it is set, the WebServiceSubscriber can handle setting it, so the developer
        //doesn't need to worry about it in the controller, unless there's a specific reason for needing to know
    }

}
