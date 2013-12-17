<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\Negotiator;
use Symfony\Component\HttpFoundation\Request;

use AC\WebServicesBundle\TestCase;

class ApiNegotiationTest extends TestCase
{
    public function testExample()
    {
        $this->getContainer();
    }

    public function testNegotiateRequestFormat()
    {
        $negotiator = new Negotiator(array(
            'application/json' => 'json',
            'application/yaml' => 'yml',
            'text/yaml' => 'yml',
            'application/xml' => 'xml'
        ));

        $req = Request::create('GET', '/foo');

        $req->headers->set('Content-Type', 'application/json');
        $this->assertSame('json', $negotiator->negotiateRequestFormat($req));
        $req->headers->set('Content-Type', 'application/yaml');
        $this->assertSame('yml', $negotiator->negotiateRequestFormat($req));
        $req->headers->set('Content-Type', 'text/yaml');
        $this->assertSame('yml', $negotiator->negotiateRequestFormat($req));
        $req->headers->set('Content-Type', 'application/xml');
        $this->assertSame('xml', $negotiator->negotiateRequestFormat($req));

        $req->headers->set('Content-Type', 'text/csv');
        $this->assertFalse($negotiator->negotiateRequestFormat($req));

        $negotiator->setInputFormatForType('text/csv', 'csv');
        $this->assertSame('csv', $negotiator->negotiateRequestFormat($req));
    }

    public function testNegotiateResponseFormat()
    {
        $negotiator = Negotiator::create()->setFormatPriorities(array('json','xml','yml','html'));
        $req = Request::create('GET', '/foo');

        //accept yaml header first priority

        //accept xml header highest priority

        //accept html header highest priority
    }

    public function testNegotiateResponseLanguage()
    {
        
    }

    public function testNegotiateResponseCharset()
    {
        
    }
}
