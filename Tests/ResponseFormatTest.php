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

    }

    public function testGetJsonp()
    {

    }

    public function testGetXml()
    {

    }

    public function testGetYaml()
    {

    }

    /**
     * Ensures that you can change the response format both explicitly in
     * the query string, but also in the path, if the routing is configured
     * to recognize it.
     *
     **/
    public function testChangeResponseFormat()
    {
        //try in query _format
        //try in path{._format}
    }

    public function testGetTemplate()
    {

    }

    public function testGetJsonWithSerializationContext()
    {
        //groups
    }

}
