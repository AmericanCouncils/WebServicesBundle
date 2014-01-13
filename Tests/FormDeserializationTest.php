<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

class FormDeserializationTest extends TestCase
{

    public function testDeserializeFormData()
    {
        $serializer = $this->getContainer()->get('serializer');

        $data = array(
            'name' => 'Evan',
            'age' => 34,
            'bestFriend' => array(
                'name' => 'John',
                'age' => 27
            ),
            'otherFriends' => array(
                array(
                    'name' => 'Foobert',
                    'age' => 42
                ),
                array(
                    'name' => 'Barbara',
                    'age' => 86
                )
            )
        );

        $person = $serializer->deserialize($data, 'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person', 'form');

        $this->assertSame('Evan', $person->getName());
        $this->assertSame(34, $person->getAge());
        $this->assertSame('John', $person->getBestFriend()->getName());
        $this->assertSame(27, $person->getBestFriend()->getAge());
        $other = $person->getOtherFriends();
        $this->assertSame('Foobert', $other[0]->getName());
        $this->assertSame(42, $other[0]->getAge());
        $this->assertSame('Barbara', $other[1]->getName());
        $this->assertSame(86, $other[1]->getAge());
    }

    public function testApiDecodeFormSubmission()
    {
        $data = array(
            'name' => 'Evan',
            'age' => 34,
            'bestFriend' => array(
                'name' => 'John',
                'age' => 27
            ),
            'otherFriends' => array(
                array(
                    'name' => 'Foobert',
                    'age' => 42
                ),
                array(
                    'name' => 'Barbara',
                    'age' => 86
                )
            )
        );

        $res = $this->callApi('POST', '/api/negotiation/person', $data);

        $this->assertSame(200, $res->getStatusCode());
        $json = json_decode($res->getContent(), true);

        $this->assertEquals($data, $json['person']);
    }
}
