<?php

namespace AC\WebServicesBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * A listener that monitors for incoming API requests.  When detected, registers the RestWorkflowSubscriber to handle generic REST API functionality.
 *
 * TODO: Consider manually calling an `onApiEarlyRequest` and an `onApiLateRequest` method.
 */
class ApiBootstrapListener
{
    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    protected $paths;

    public function __construct(ContainerInterface $container, $paths = array())
    {
        $this->container = $container;
        $this->paths = $paths;
    }

    /**
     * Checks arrays of regex against requested route, and registers other listeners accordingly.
     *
     * @param GetResponseEvent $e
     */
    public function onKernelRequest(GetResponseEvent $e)
    {
        $request = $e->getRequest();

        foreach ($this->paths as $regex) {
            if (preg_match($regex, $request->getPathInfo())) {
                //build rest subscriber
                $subscriber = new RestServiceSubscriber(
                    $this->container,
                    $this->container->getParameter('ac_web_services.default_response_format'),
                    $this->container->getParameter('ac_web_services.include_response_data'),
                    $this->container->getParameter('ac_web_services.allow_code_suppression'),
                    $this->container->getParameter('ac_web_services.include_dev_exceptions'),
                    $this->container->getParameter('ac_web_services.exception_map')
                );

                //register subscriber with dispatcher
                $this->container->get('event_dispatcher')->addSubscriber($subscriber);

                $subscriber->onApiRequest($e);

                return;
            }
        }
    }
}
