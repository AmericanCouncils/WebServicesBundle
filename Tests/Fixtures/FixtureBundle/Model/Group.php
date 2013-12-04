<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model;

use JMS\Serializer\Annotation as JMS;

class Group
{
    /**
     * @JMS\Type("integer")
     * @JMS\ReadOnly
     **/
    protected $id;
    public function getId() { return $this->id; }

    /**
     * @JMS\Type("string")
     **/
    protected $name;
    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    /**
     * @JMS\Type("AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person")
     **/
    protected $owner;

    /**
     * @JMS\Type("array<AC\WebservicesBundle\Tests\Fixtures\FixtureBundle\Model\Person>")
     **/
    protected $members;
}
