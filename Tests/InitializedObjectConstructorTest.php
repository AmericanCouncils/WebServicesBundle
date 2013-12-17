<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person;
use JMS\Serializer\DeserializationContext;
// use AC\WebServicesBundle\Serializer\DeserializationContext;

/**
 **/
class InitializedObjectConstructorTest extends TestCase
{
    public function testConstruct()
    {
        $existingPerson = new Person('John', 86);
        $serializer = $this->getContainer()->get('serializer');
        $context = DeserializationContext::create();
        $context->setAttribute('target', $existingPerson);
        $newData = array(
            'age' => 27
        );
        $modifiedPerson = $serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $context
        );
        $encoded = json_decode($serializer->serialize($modifiedPerson, "json"));

        $this->assertSame(27, $encoded->age);
        $this->assertSame('John', $encoded->name);
    }

    public function testComplexIncomingData()
    {
        $this->markTestSkipped();

        //TODO... think about how this should really behave - it may need to be
        // configurable depending on the situation, in which case there needs
        // to be a separate set of unit tests for covering those scenarios
    }
}
