<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Group;
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

    protected function createAlphas()
    {
        $alphas = new Group();
        $alphas->setOwner($this->allen);
        $alphas->setMembers(array(
               $this->allen,
               $this->barry,
               $this->clive
            )
        );
        return $alphas;
    }

    protected function createBetas()
    {
        $betas = new Group();
        $betas->setOwner($this->clive);
        $betas->setMembers(array(
               $this->barry,
               $this->clive,
               $this->davis
            )
        );
        return $betas;
    }
    protected function createCircularRelationships()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->clive->setBestFriend($this->davis);
        $this->davis->setBestFriend($this->edgar);
        $this->edgar->setBestFriend($this->allen);
        // $this->allen->setOtherFriends(array($this->clive, $this->davis));
        // $this->barry->setOtherFriends(array($this->davis, $this->allen));
        // $this->clive->setOtherFriends(array($this->edgar, $this->allen));
        // $this->davis->setOtherFriends(array($this->allen, $this->barry, $this->clive));
        // $this->davis->setOtherFriends(array($this->barry));
    }

    protected function createNonCircularRelationships()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->clive->setBestFriend($this->davis);
        $this->davis->setBestFriend($this->edgar);
    }
    protected function deserializeWithNewData($alphas)
    {
        $newData = array(
            'owner' => $this->edgar,
            'members' => array(
                $this->clive,
                $this->davis,
                $this->edgar
            )
        );
        $serializedData = $this->serializer->serialize($newData, "json");

        $this->context->setAttribute('target', $alphas);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modifiedGroup = $this->serializer->deserialize(
            $serializedData,
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Group',
            'json',
            $this->context
        );

        return $modifiedGroup;
    }
    protected function getMemberNames($group)
    {
        $memberNames = array();
        foreach ($group->getMembers() as $member) {
            $memberNames[] = $member->getName();
        }
        return $memberNames;
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
        $decoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));

        $this->assertEquals(27, $decoded->age);
        $this->assertEquals('John', $decoded->name);
    }

    public function testUpdateLevelOneNesting()
    {
        $this->markTestSkipped();
        $this->allen->setBestFriend($this->barry);
        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
        $newData = array(
            'bestFriend' => $this->davis
        );
        $serializedData = $this->serializer->serialize($newData, "json");

        $modifiedPerson = $this->serializer->deserialize(
            $serializedData,
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        // var_dump($this->allen);
        var_dump($this->barry);
        var_dump($this->davis);
        $this->assertNotEquals($this->barry, $this->davis);
        $this->assertEquals($this->davis, $modifiedPerson->getBestFriend());
    }

    public function testUpdateLevelTwoNesting()
    {
        $this->markTestSkipped();

        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->assertEquals($this->clive,
            $this->allen->getBestFriend()->getBestFriend());
        $newData = array(
            'bestFriend' => $this->davis
        );
        $serializedData = $this->serializer->serialize($newData, "json");
        $this->context->setAttribute('target', $this->barry);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modifiedPerson = $this->serializer->deserialize(
            $serializedData,
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        $decoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));
        var_dump($this->clive);
        var_dump($this->davis);
        $this->assertEquals($this->davis,
            $this->allen->getBestFriend()->getBestFriend());

    }
    public function testUpdateReverseNesting()
    {
        $this->markTestSkipped();

        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->assertEquals("Barry",
            $this->allen->getBestFriend()->getName());
        $this->assertEquals("Clive",
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
        $this->assertEquals($this->clive,
            $this->allen->getBestFriend());
        $this->assertEquals($this->barry,
            $this->allen->getBestFriend()->getBestFriend());
    }

    public function testUpdateLevelOneComplexNesting()
    {
        $this->markTestSkipped();

        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);

        $this->assertEquals("Barry",
            $this->allen->getBestFriend()->getName());
        $this->assertEquals("Clive",
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

        $this->assertEquals($this->davis,
            $this->allen->getBestFriend());
        $this->assertEquals($this->edgar,
            $this->allen->getBestFriend()->getBestFriend());
    }

    public function testArrayUpdate()
    {
        $this->markTestSkipped();

        $this->allen->setOtherFriends(array($this->barry, $this->clive));

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

        $this->assertEquals($this->allen->getOtherFriends(),
            array($this->davis, $this->edgar));
    }
// test best friend and other friends
// work in group somehow
// use both classes, one containing array


// So, interesting combinations:

// ### testComplexUpdateVariation1
// Serialize / update
// ### testComplexUpdateVariation2
// Preassign Betas
// Serialize / update
// ### testComplexUpdateVariation3
// Preassign Alphas
// Serialize / update
// ### testComplexUpdateVariation4
// Create relationships
// Serialize / update
// ### testComplexUpdateVariation5
// Preassign Betas
// Create relationships
// Serialize / update
// ### testComplexUpdateVariation6
// Preassign Alphas
// Create relationships
// Serialize / update
// ### testComplexUpdateVariation7
// Preassign Alphas
// Preassign Betas
// Serialize / update
// ### testComplexUpdateVariation8
// Preassign Alphas
// Preassign Betas
// Create relationships
// Serialize / update
    public function testComplexUpdateVariation1()
    {
        $this->markTestSkipped();
        $alphas = new Group();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($modifiedGroup);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());

    }
    public function testComplexUpdateVariation2()
    {
        $this->markTestSkipped();
        $alphas = new Group();
        $betas = $this->createBetas();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());

    }
    public function testComplexUpdateVariation3()
    {
        $this->markTestSkipped();
        $alphas = $this->createAlphas();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());

    }
    public function testComplexUpdateVariation4()
    {
        $this->markTestSkipped();
        $alphas = new Group();
        $this->createCircularRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
        $this->assertEquals($this->allen, $this->edgar->getBestFriend());
        $this->assertEquals($this->barry, $this->allen->getBestFriend());
    }
    public function testComplexUpdateVariation5()
    {
        $this->markTestSkipped();
        $alphas = new Group();
        $betas = $this->createBetas();
        $this->createCircularRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
        $this->assertEquals($this->allen, $this->edgar->getBestFriend());
        $this->assertEquals($this->barry, $this->allen->getBestFriend());

    }
    public function testComplexUpdateVariation6()
    {
        // $this->markTestSkipped();
        $alphas = $this->createAlphas();
        $this->createCircularRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
        $this->assertEquals($this->allen, $this->edgar->getBestFriend());
        $this->assertEquals($this->barry, $this->allen->getBestFriend());
    }
    public function testComplexUpdateVariation7()
    {
        $this->markTestSkipped();
        $alphas = $this->createAlphas();
        $betas = $this->createBetas();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
    }

    public function testComplexUpdateVariation8()
    {
        $this->markTestSkipped();

        $alphas = $this->createAlphas();
        $betas = $this->createBetas();
        $this->createCircularRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
        $this->assertEquals($this->allen, $this->edgar->getBestFriend());
        $this->assertEquals($this->barry, $this->allen->getBestFriend());
    }

    public function testComplexUpdateVariation9()
    {
        $this->markTestSkipped();

        $alphas = $this->createAlphas();
        $this->createNonCircularRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
        $this->assertEquals($this->barry, $this->allen->getBestFriend());
    }

    public function testComplexUpdateVariation10()
    {
        $this->markTestSkipped();

        $alphas = $this->createAlphas();
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->clive->setBestFriend($this->davis);
        $this->davis->setBestFriend($this->edgar);
        // $this->edgar->setBestFriend($this->allen);
        $this->allen->setOtherFriends(array($this->clive, $this->davis));
        $this->barry->setOtherFriends(array($this->davis, $this->allen));
        $this->clive->setOtherFriends(array($this->edgar, $this->allen));
        $this->davis->setOtherFriends(array($this->allen, $this->barry, $this->clive));
        $this->davis->setOtherFriends(array($this->barry));
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        // print_r($modifiedGroup->getOwner());
        $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));

        $this->assertEquals($this->allen->getBestFriend(),$this->barry);
        $this->assertEquals($this->barry->getBestFriend(),$this->clive);
        $this->assertEquals($this->clive->getBestFriend(),$this->davis);
        $this->assertEquals($this->davis->getBestFriend(),$this->edgar);

        // $this->assertEquals("Allen", $this->edgar->getBestFriend()->getName());
    }

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
