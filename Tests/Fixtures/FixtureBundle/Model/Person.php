<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Person
{
    public function __construct($name, $age, $id = null)
    {
        $this->name = $name;
        $this->age = $age;
        $this->id = $id;
    }

    /**
     * @JMS\Type("integer")
     * @JMS\ReadOnly
     * @JMS\SerializedName("id")
     * @Assert\Type("integer")
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
     * @JMS\Groups({"overview"})
     * @Assert\Regex(pattern="/\d/", match=false, message="Your name cannot contain a number")
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\Length(min=4)
     **/
    protected $name;
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    /**
     * @JMS\Type("integer")
     * @Assert\Type("integer")
     **/
    public $age;
    public function getAge() { return $this->age; }
    public function setAge($age) { $this->age = $age; }

    /**
     * @JMS\Type("AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person")
     * @JMS\SerializedName("bestFriend")
     * @Assert\Valid
     **/
    protected $bestFriend;
    public function setBestFriend($bestFriend) {$this->bestFriend = $bestFriend;}
    public function getBestFriend() {return $this->bestFriend;}

    /**
     * @JMS\Type("array<AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person>")
     * @JMS\SerializedName("otherFriends")
     * @Assert\Valid(traverse=true)
     **/
    protected $otherFriends;
    public function setOtherFriends($otherFriends) {$this->otherFriends = $otherFriends;}
    public function getOtherFriends() {return $this->otherFriends;}

    /**
     * @JMS\Type("array<AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Groups>")
     **/
    protected $groups;
}
