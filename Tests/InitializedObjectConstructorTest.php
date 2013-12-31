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
    protected function createComplexRelationships()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->clive->setBestFriend($this->davis);
        $this->davis->setBestFriend($this->edgar);
        $this->edgar->setBestFriend($this->allen);
        $this->allen->setOtherFriends(array($this->clive, $this->davis));
        $this->barry->setOtherFriends(array($this->davis, $this->allen));
        $this->clive->setOtherFriends(array($this->edgar, $this->allen));
        $this->davis->setOtherFriends(array($this->allen, $this->barry, $this->clive));
        $this->davis->setOtherFriends(array($this->barry));
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
        $alphas = new Group();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($modifiedGroup);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));

    }
    public function testComplexUpdateVariation2()
    {
        $alphas = new Group();
        $betas = $this->createBetas();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));

    }
    public function testComplexUpdateVariation3()
    {
        $alphas = $this->createAlphas();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));

    }
    public function testComplexUpdateVariation4()
    {
        $alphas = new Group();
        $this->createComplexRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));
        $this->assertEquals("Allen", $this->edgar->getBestFriend()->getName());
        $this->assertEquals("Barry", $this->allen->getBestFriend()->getName());
    }
    public function testComplexUpdateVariation5()
    {
        $alphas = new Group();
        $betas = $this->createBetas();
        $this->createComplexRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));
        $this->assertEquals("Allen", $this->edgar->getBestFriend()->getName());
        $this->assertEquals("Barry", $this->allen->getBestFriend()->getName());

    }
    public function testComplexUpdateVariation6()
    {
        // $this->markTestSkipped();
        $alphas = $this->createAlphas();
        $this->createComplexRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));
        $this->assertEquals("Allen", $this->edgar->getBestFriend()->getName());
        $this->assertEquals("Barry", $this->allen->getBestFriend()->getName());
    }
    public function testComplexUpdateVariation7()
    {
        $alphas = $this->createAlphas();
        $betas = $this->createBetas();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));

    }

    public function testComplexUpdateVariation8()
    {
        // $this->markTestSkipped();
        $alphas = $this->createAlphas();
        $betas = $this->createBetas();
        $this->createComplexRelationships();
        $modifiedGroup = $this->deserializeWithNewData($alphas);
        $alphaMemberNames = $this->getMemberNames($alphas);
        $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));
        $this->assertEquals("Allen", $this->edgar->getBestFriend()->getName());
        $this->assertEquals("Barry", $this->allen->getBestFriend()->getName());
    }

    public function testComplexStructure()
    {
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



        // $alphas = new Group();
        // $alphas->setOwner($this->allen);
        // $alphas->setMembers(array(
        //        $this->allen,
        //        $this->barry,
        //        $this->clive
        //     )
        // );
        // $betas = new Group();
        // $betas->setOwner($this->clive);
        // $betas->setMembers(array(
        //        $this->barry,
        //        $this->clive,
        //        $this->davis
        //     )
        // );

        // // set up relationships (should survive unchanged)
        // $this->allen->setBestFriend($this->barry);
        // $this->barry->setBestFriend($this->clive);
        // $this->clive->setBestFriend($this->davis);
        // $this->davis->setBestFriend($this->edgar);
        // $this->edgar->setBestFriend($this->allen);
        // $this->allen->setOtherFriends(array($this->clive, $this->davis));
        // $this->barry->setOtherFriends(array($this->davis, $this->allen));
        // $this->clive->setOtherFriends(array($this->edgar, $this->allen));
        // $this->davis->setOtherFriends(array($this->allen, $this->barry, $this->clive));
        // $this->davis->setOtherFriends(array($this->barry));


        // $newData = array(
        //     'owner' => $this->edgar,
        //     'members' => array(
        //         $this->clive,
        //         $this->davis,
        //         $this->edgar
        //     )
        // );
        // $serializedData = $this->serializer->serialize($newData, "json");

        // $this->context->setAttribute('target', $alphas);
        // $this->context->setAttribute('updateNestedData', TRUE);
        // $modifiedGroup = $this->serializer->deserialize(
        //     $serializedData,
        //     'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Group',
        //     'json',
        //     $this->context
        // );
        // $this->assertEquals("Edgar", $modifiedGroup->getOwner()->getName());
        // $alphaMemberNames = array();
        // foreach ($modifiedGroup->getMembers() as $member) {
        //     $alphaMemberNames[] = $member->getName();
        // }
        // $this->assertEquals($alphaMemberNames, array("Clive", "Davis", "Edgar"));
        // $this->assertEquals("Allen", $this->edgar->getBestFriend()->getName());
        // $this->assertEquals("Barry", $this->allen->getBestFriend()->getName());
