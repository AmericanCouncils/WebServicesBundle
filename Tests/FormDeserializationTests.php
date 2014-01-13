<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;

class FormDeserializationTests extends TestCase
{
    
    public function testDeserializeFormData()
    {
        $serializer = $this->getContainer()->get('serializer');

        $incoming = 'name=Evan&age=34&bestFriend[name]=John&bestFriend[age]=27&otherFriends[][name]=Foobert&otherFriends[][age]=42&otherFriends[][name]=Barbara&otherFriends[][age]=86';

        $person = $serializer->deserialize($incoming, 'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person', 'form');

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

}