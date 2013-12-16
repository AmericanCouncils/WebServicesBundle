<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\Negotiator;
use Symfony\Component\HttpFoundation\Request;

class ApiNegotiationTest extends \PHPUnit_Framework_TestCase
{
    public function testNegotiateRequestFormat()
    {
        $negotiator = new Negotiator();
        $req = Request::create();
        $req->headers->set('Content-Type', '');
        //expect json
        //expect xml
        //false csv

        $negotiator = new Negotiator(array(
            'csv' => array('', '')
        ));
        //true csv

    }

    public function testNegotiateResponseFormat()
    {
        //accept yaml header first priority

        //accept xml header highest priority

        //accept html header highest priority
    }

    public function testNegotiateResponseLanguage()
    {
        
    }
}
