<?php

use AC\WebServicesBundle\TestCase;

class ServiceExceptionTest extends TestCase
{
    public function testServiceException()
    {
        $result = $this->callJsonApi('GET', '/api/service-exception', ['expectedCode' => 422]);
        $this->assertSame(422, $result['response']['code']);
        $this->assertSame('bar', $result['foo']);
    }

    public function testValidationException()
    {
        $result = $this->callJsonApi('GET', '/api/validation-exception', ['expectedCode' => 422]);
        $this->assertTrue(isset($result['errors']));
        $this->assertSame('members[1].name', $result['errors'][2]['path']);
        $this->assertTrue(2 === count($result['errors'][2]['messages']));
    }
}
