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
        $this->markTestSkipped();
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
        $this->markTestSkipped();
        $this->allen->setBestFriend($this->barry);
        $this->context->setAttribute('target', $this->allen);
        $davisData = $this->serializer->serialize($this->davis,"json");
        $newData = array(
            // 'age' => 108,
            'bestFriend' => json_decode($davisData, TRUE)
        );
        // var_dump($newData);
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        // var_dump($modifiedPerson);
        $encoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));
        // var_dump($encoded);
        $this->assertSame('Davis', $encoded->bestFriend->name);

    }

    public function testMultipleUpdate()
    {
        $this->markTestSkipped();
        $this->allen->setOtherFriends(array($this->barry, $this->clive));
        $this->barry->setBestFriend($this->edgar);
        $this->context->setAttribute('target', $this->allen);
        $newData = array(
            'name' => 'Bazil',
            'otherFriends' => array(
               array(
                    "name" => "Bart",
                    "age"=> 11,
                    "bestFriend" => array(
                        'name' => 'Foobert'
                    )
                ),
               array(
                    "name" => "Chester",
                    "age"=> 11
                )
            )
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
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

    public function testComplexStructure()
    {
        // $this->markTestSkipped();
        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
        $this->context->setAttribute('targetStack', new \SplStack());
        $this->context->attributes->get('targetStack')->get()->push($this->allen);
        var_dump($this->context->attributes->get('targetStack')->get());

        $newData = array(
            'bestFriend' => array(
                'name' => "Alyosha",
                'age' => 10,
                'bestFriend' => array(
                    'name' => "Boris",
                    'age' => 11,
                    'bestFriend' => array(
                        'name' => 'Dimitri',
                        'age' => 12
                    )
                )
            )
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );

    }
}
