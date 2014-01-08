<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\Negotiator;
use Symfony\Component\HttpFoundation\Request;

use AC\WebServicesBundle\TestCase;

class ApiNegotiationTest extends TestCase
{
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
        $negotiator = Negotiator::create();
        $req = Request::create('GET', '/foo');

        //expect html based no priority
        $negotiator->setFormatPriorities(array('json','xml','yml','html'));
        $req->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        $this->assertSame('html', $negotiator->negotiateResponseFormat($req));

        //expect json
        $negotiator->setFormatPriorities(array('json','xml','yml'));
        $req->headers->set('Accept', 'application/xhtml+xml,application/json,application/xml;q=0.9,*/*;q=0.8');
        $this->assertSame('json', $negotiator->negotiateResponseFormat($req));

        //accept xml header first priority
        $negotiator->setFormatPriorities(array('xml','json','yml'));
        $req->headers->set('Accept', 'application/xhtml+xml,application/json;q=0.6,application/xml;q=0.9,*/*;q=0.8');
        $this->assertSame('xml', $negotiator->negotiateResponseFormat($req));
    }

    public function testNegotiateResponseLanguage()
    {
        $negotiator = Negotiator::create()->setLanguagePriorities(array('en','da','fr','ar'));
        $req = Request::create('GET', '/foo');
        $req->headers->set('Accept-Language', 'da, en-gb;q=0.8, en;q=0.7');

        $this->assertSame('da', $negotiator->negotiateResponseLanguage($req));
    }

    public function testNegotiateResponseEncoding()
    {
        $negotiator = Negotiator::create()->setEncodingPriorities(array('gzip','deflate'));
        $req = Request::create('GET', '/foo');
        $req->headers->set('Accept-Encoding', 'gzip,deflate,sdch');

        $this->assertSame('gzip', $negotiator->negotiateResponseEncoding($req));
    }

    public function testNegotiateResponseCharset()
    {
        $negotiator = Negotiator::create()->setCharsetPriorities(array('utf-8','koi-8','windows-1251'));
        $req = Request::create('GET', '/foo');
        $req->headers->set('Accept-Charset', 'ISO-8859-1, Big5;q=0.6,utf-8;q=0.7, *;q=0.5');

        $this->assertSame('utf-8', $negotiator->negotiateResponseCharset($req));
    }
}
