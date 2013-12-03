<?php

namespace AC\WebServicesBundle\Tests\Fixtures\FixtureBundle;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use AC\WebServicesBundle\ServiceResponse;

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
     * @Route("/api/defaults")
     **/
    public function apiDefaultsAction()
    {

    }

    /**
     * @Route("/api/overrides")
     **/
    public function apiOverridesAction()
    {

    }
}
