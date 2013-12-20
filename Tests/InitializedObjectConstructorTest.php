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
        $decoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));

        $this->assertSame(27, $decoded->age);
        $this->assertSame('John', $decoded->name);
    }

    public function testUpdateLevelOneNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
        $davisData = $this->serializer->serialize($this->davis,"json");
        $newData = array(
            'bestFriend' => json_decode($davisData, TRUE)
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        $decoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));
        $this->assertSame('Davis', $decoded->bestFriend->name);
        $this->assertSame(15, $decoded->bestFriend->age);

    }

    public function testUpdateLevelTwoNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->assertSame("Clive",
            $this->allen->getBestFriend()->getBestFriend()->getName());
        $davisData = $this->serializer->serialize($this->davis,"json");
        $newData = array(
            'bestFriend' => json_decode($davisData, TRUE)
        );
        $this->context->setAttribute('target', $this->barry);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        $decoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));
        $this->assertSame("Davis",
            $this->allen->getBestFriend()->getBestFriend()->getName());

    }
    public function testUpdateReverseNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->assertSame("Barry",
            $this->allen->getBestFriend()->getName());
        $this->assertSame("Clive",
            $this->allen->getBestFriend()->getBestFriend()->getName());
        $this->clive->setBestFriend($this->barry);
        $cliveData = $this->serializer->serialize($this->clive,"json");
        $newData = array(
            'bestFriend' => json_decode($cliveData, TRUE)
        );
        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        $this->assertSame("Clive",
            $this->allen->getBestFriend()->getName());
        $this->assertSame("Barry",
            $this->allen->getBestFriend()->getBestFriend()->getName());
    }

    public function testUpdateLevelOneComplexNesting()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);

        $this->assertSame("Barry",
            $this->allen->getBestFriend()->getName());
        $this->assertSame("Clive",
            $this->allen->getBestFriend()->getBestFriend()->getName());

        $this->davis->setBestFriend($this->edgar);
        $davisData = $this->serializer->serialize($this->davis,"json");
        $newData = array(
            'bestFriend' => json_decode($davisData, TRUE)
        );
        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );

        $this->assertSame("Davis",
            $this->allen->getBestFriend()->getName());
        $this->assertSame("Edgar",
            $this->allen->getBestFriend()->getBestFriend()->getName());
    }

    public function testArrayUpdate()
    {
        $this->allen->setOtherFriends(array($this->barry, $this->clive));

        // $otherFriendsData = array(
        //     $this->davis,
        //     $this->edgar
        // );

        $newData = array(
            'otherFriends' => array(
                $this->davis,
                $this->edgar
            )
        );

        $serializedData = $this->serializer->serialize($newData, "json");

        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modifiedPerson = $this->serializer->deserialize(
            $serializedData,
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        var_dump($modifiedPerson);
    }
// test best friend and other friends
// work in group somehow
// use both classes, one containing array
    public function testComplexStructure()
    {
        $this->markTestSkipped();
        $this->context->setAttribute('target', $this->allen);
        $this->allen->setBestFriend($this->edgar);
        $this->context->setAttribute('updateNestedData', TRUE);

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

    public function testMultipleUpdate()
    {
        $this->markTestSkipped();
        $this->allen->setOtherFriends(array($this->barry, $this->clive));
        $this->barry->setBestFriend($this->edgar);
        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
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
}
