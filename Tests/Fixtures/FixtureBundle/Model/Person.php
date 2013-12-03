<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model;

use JMS\Serializer\Annotation as JMS;

class Person
{
    protected $id;
    public function getId() { return $this->id; }

    protected $name;

    protected $age;

    protected $bestFriend;

    protected $otherFriends;
}
