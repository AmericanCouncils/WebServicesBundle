<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

class FormatNegotiationsTest extends TestCase
{
    public function testNegotiateIncomingDataFormat()
    {
        //send json, expect json
        $res = '';

        //send yaml, expect json
    }

    public function testNegotiateResponseFormat()
    {
        //accept yaml header first priority

        //accept xml header highest priority

        //accept html header highest priority
    }
}
