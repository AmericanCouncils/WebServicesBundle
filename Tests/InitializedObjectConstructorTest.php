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
    public function setUp()
    {
        $this->serializer = $this->getContainer()->get('serializer');
        $this->context = DeserializationContext::create();
        $this->allen = new Person("Allen", 10, 1);
        $this->barry = new Person("Barry", 11, 2);
        $this->clive = new Person("Clive", 12, 3);
        $this->davis = new Person("Davis", 15, 4);
        $this->edgar = new Person("Edgar", 11, 5);

    }
        
    public function testConstruct()
    {
        $existingPerson = new Person('John', 86);
        $this->context->setAttribute('target', $existingPerson);
        $newData = array(
            'age' => 27
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        $encoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));

        $this->assertSame(27, $encoded->age);
        $this->assertSame('John', $encoded->name);
    }

    public function testUpdateLevelOneNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->context->setAttribute('target', $this->allen);
        $newData = array(
            // 'age' => 108,
            'bestFriend' => $this->serializer->serialize($this->davis,
                "json"
            )
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        $encoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));
        // var_dump($modifiedPerson);
        var_dump($encoded);
        $this->assertSame('Davis', $encoded->bestFriend->name);

    }
    public function testUpdateLevelTwoNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
    }
    public function testUpdateLevelOneComplexNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
    }
    public function testUpdateReverseNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
    }
}
