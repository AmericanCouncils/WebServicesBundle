<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle;

use AC\WebServicesBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use AC\WebServicesBundle\ServiceResponse;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\BundleException;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Group;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use AC\WebServicesBundle\Exception\ServiceException;
use AC\WebServicesBundle\Exception\ValidationException;

/**
 * These controller routes are called by various tests to ensure any API responds as expected based
 * on how it is configured.
 **/
class Controllers extends Controller
{
    /**
     * @Route("/no-api")
     **/
    public function nonApiRouteAction()
    {
        return new Response('hello world');
    }

    /**
     * @Route("/api/override/success")
     **/
    public function apiOverrideSuccessAction()
    {
        return new ServiceResponse(array('person' => new Person('John', 86)));
    }

    /**
     * @Route("/api/override/fail")
     **/
    public function apiOverrideFailAction()
    {
        throw new BundleException();
    }

    /**
     * @Route("/api/success.{_format}", defaults={"_format" = "json"})
     **/
    public function apiSuccessAction()
    {
        return new ServiceResponse(array('person' => new Person('John', 86)));
    }

    /**
     * @Route("/api/fail")
     **/
    public function apiFailAction()
    {
        throw new \LogicException();
    }

    /**
     * @Route("/api/fail/exception-map")
     **/
    public function apiFailExceptionMapAction()
    {
        throw new BundleException();
    }

    /**
     * @Route("/api/templates/people.{_format}", defaults={"_format" = "json"})
     **/
    public function apiGetPeopleWithTemplates()
    {
        return ServiceResponse::create(array('people' => array(
            new Person('John', 86),
            new Person('Juan', 68)
        )), 200)
            ->setTemplateForFormat('FixtureBundle::people.html.twig', array('html','xhtml'))
            ->setTemplateForFormat('FixtureBundle::people.csv.twig', 'csv')
        ;
    }

    /**
     * @Route("/api/serializer/people.{_format}", defaults={"_format" = "json"})
     **/
    public function apiGetSerializedPeople()
    {
        return ServiceResponse::create(array('people' => array(
            new Person('John', 86),
            new Person('Juan', 68)
        )));
    }

    /**
     * @Route("/api/serializer/people/context.{_format}", defaults={"_format" = "json"})
     **/
    public function apiGetSerializedPeopleWithContext()
    {
        $serializerContext = SerializationContext::create()->setGroups(array('overview'));

        return ServiceResponse::create(array('people' => array(
            new Person('John', 86),
            new Person('Juan', 68)
        )), 200, array(), $serializerContext);
    }

    /**
     * For test purposes, only use JSON for this route.
     *
     * @Route("/api/people/simple/{id}.{_format}", defaults={"_format" = "json"})
     * @Method({"POST", "PUT"})
     **/
    public function apiSimpleModifyPerson(Request $request)
    {
        $existingPerson = new Person('John', 86);

        $serializer = $this->container->get('serializer');

        $context = DeserializationContext::create();
        $context->setAttribute('target', $existingPerson);

        $modifiedPerson = $serializer->deserialize(
            $request->getContent(),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $context
        );

        return new ServiceResponse(array('person' => $modifiedPerson));
    }

    /**
     * @Route("/api/negotiation/person")
     * @Method({"POST", "PUT"})
     */
    public function apiDecodeIncomingPerson(Request $req)
    {
        $data = $this->decodeRequest('AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person');

        return new ServiceResponse(array('person' => $data));
    }

    /**
     * @Route("/api/service-exception")
     * @Method("GET")
     */
    public function apiServiceException()
    {
        throw new ServiceException(422, ['foo' => 'bar']);
    }

    /**
     * @Route("/api/validation-exception")
     * @Method("GET")
     */
    public function apiValidationException()
    {
        $group = new Group('foo', 'blahblahblah');
        $group->setOwner(new Person('John', 3));
        $group->setMembers([
            new Person('Foobert', 1),
            new Person("33", 2),
            new Person('Barbara', 3)
        ]);

        $this->validate($group);
    }
}
