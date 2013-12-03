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

    protected $pathConfig;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->pathConfig = $container->getParameter('ac_web_services.paths');
    }

    /**
     * Checks arrays of regex against requested route, and registers other listeners accordingly.
     *
     * @param GetResponseEvent $e
     */
    public function onKernelRequest(GetResponseEvent $e)
    {
        $request = $e->getRequest();

        foreach ($this->pathConfig as $regex => $config) {
            if (preg_match($regex, $request->getPathInfo())) {
                //build rest subscriber
                $subscriber = new RestServiceSubscriber(
                    $this->container,
                    $config['default_response_format'],
                    $config['include_response_data'],
                    $config['allow_code_suppression'],
                    $config['include_exception_data'],
                    $config['http_exception_map'],
                    $config['allow_jsonp'],
                    $this->container->getParameter('ac_web_services.response_format_headers')
                );

                //register subscriber with dispatcher
                $this->container->get('event_dispatcher')->addSubscriber($subscriber);

                $subscriber->onApiRequest($e);

                return;
            }
        }
    }
}
