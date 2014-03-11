<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

class FormatNegotiationTest extends TestCase
{
    public function testNegotiateRequestFormat()
    {
        //send json, expect json back
        $return = $this->callJsonApi('PUT', '/api/negotiation/person', array(
            'content' => array(
                'age' => 27,
                'name' => 'Foobert'
            )
        ));

        $this->assertSame(27, $return['person']['age']);
        $this->assertSame('Foobert', $return['person']['name']);

        //send yaml, expect json back
        $content =
<<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<entry>
    <name>Foobert</name>
    <age>27</age>
</entry>
EOT;
        $return = $this->callJsonApi('PUT', '/api/negotiation/person', array(
            'server' => array('CONTENT_TYPE' => 'application/xml'),
            'content' => $content
        ));

        $this->assertSame(27, $return['person']['age']);
        $this->assertSame('Foobert', $return['person']['name']);
    }

    public function testNegotiateResponseFormat()
    {
        //accept xml header highest priority
        $res = $this->callApi('GET', '/api/override/success', array("server" => array(
            'HTTP_ACCEPT' => 'application/xhtml+xml;q=0.9,application/xml,*/*;q=0.8'
        )));
        $this->assertSame('application/xml', $res->headers->get('Content-Type'));

        //accept yaml header first priority
        $res = $this->callApi('GET', '/api/override/success', array("server" => array(
            'HTTP_ACCEPT' => 'application/xhtml+xml;q=0.9,application/yaml,*/*;q=0.8'
        )));
        $this->assertSame('text/x-yaml; charset=UTF-8', $res->headers->get('Content-Type'));
    }
}
