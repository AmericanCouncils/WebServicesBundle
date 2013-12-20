<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model;

use JMS\Serializer\Annotation as JMS;

class Group
{
    /**
     * @JMS\Type("integer")
     * @JMS\ReadOnly
     * @JMS\SerializedName("id")
     **/
    protected $id;
    public function getId() { return $this->id; }

    /**
     * @JMS\Type("string")
     * @JMS\SerializedName("name")
     **/
    protected $name;
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    /**
     * @JMS\Type("AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person")
     * @JMS\SerializedName("owner")
     **/
    protected $owner;
    public function setOwner($owner) {$this->owner = $owner;}
    public function getOwner() {return $this->owner;}


    /**
     * @JMS\Type("array<AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person>")
     * @JMS\SerializedName("members")
     **/
    protected $members;
    public function setMembers($members) {$this->members = $members;}
    public function getMembers() {return $this->members;}

}
