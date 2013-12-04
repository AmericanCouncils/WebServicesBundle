<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model;

use JMS\Serializer\Annotation as JMS;

class Person
{
    public function __construct($name, $age, $id = null)
    {
        $this->name = $name;
        $this->age = $age;
        $this->id = null;
    }

    /**
     * @JMS\Type("integer")
     * @JMS\ReadOnly
     **/
    protected $id;
    public function getId() { return $this->id; }
    public function setId($id)
    {
        if (!$this->id) {
            $this->id = $id;
        }

        return $this;
    }

    /**
     * @JMS\Type("string")
     **/
    protected $name;
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    /**
     * @JMS\Type("integer")
     **/
    protected $age;

    /**
     * @JMS\Type("AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person")
     **/
    protected $bestFriend;

    /**
     * @JMS\Type("array<AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person>")
     **/
    protected $otherFriends;

    /**
     * @JMS\Type("array<AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Groups>")
     **/
    protected $groups;
}
