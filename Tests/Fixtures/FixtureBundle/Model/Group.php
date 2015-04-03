<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class Group
{
    public function __construct($id = null, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @JMS\Type("integer")
     * @JMS\ReadOnly
     * @JMS\SerializedName("id")
     * @Assert\Type("integer")
     **/
    protected $id;
    public function getId() { return $this->id; }

    /**
     * @JMS\Type("string")
     * @JMS\SerializedName("name")
     * @Assert\Type("string")
     * @Assert\Length(max=4)
     **/
    protected $name;
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    /**
     * @JMS\Type("AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person")
     * @JMS\SerializedName("owner")
     * @Assert\Valid
     **/
    protected $owner;
    public function setOwner($owner) {$this->owner = $owner;}
    public function getOwner() {return $this->owner;}

    /**
     * @JMS\Type("array<AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person>")
     * @JMS\SerializedName("members")
     * @Assert\Valid(traverse=true)
     **/
    protected $members;
    public function setMembers($members) {$this->members = $members;}
    public function getMembers() {return $this->members;}

}
