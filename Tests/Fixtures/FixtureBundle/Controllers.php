<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use AC\WebServicesBundle\ServiceResponse;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\BundleException;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Group;

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
        return new ServiceResponse(array('person' => new Model\Person('John', 86)));
    }

    /**
     * @Route("/api/override/fail")
     **/
    public function apiOverrideFailAction()
    {
        throw new BundleException();
    }

    /**
     * @Route("/api/success")
     **/
    public function apiSuccessAction()
    {
        return new ServiceResponse(array('person' => new Model\Person('John', 86)));
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
}
