<?php

namespace AC\WebServicesBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AC\WebServicesBundle\ServiceResponse;

/**
 * A listener that monitors input/output for all API requests and provides generic API behavior.
 *
 * @author Evan Villemez
 */
class WebServiceSubscriber implements EventSubscriberInterface
{
    const API_REQUEST = 'ac.webservice.request';

    const API_EXCEPTION = 'ac.webservice.exception';

    const API_RESPONSE = 'ac.webservice.response';

    const API_TERMINATE = 'ac.webservice.terminate';

    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var Symfony\Component\EventDispatcher\EventDispatcherInterface
     **/
    protected $dispatcher;

    /**
     * Determined by config.
     *
     * @var array
     **/
    protected $formatHeaders;

    /**
     * Map of path regex matches to API behavior configuration.
     *
     * @var array
     **/
    protected $pathConfig;

    /**
     * Initially set by matching a path, extra values injected when processing matching requests.
     *
     * @var array
     **/
    private $currentPathConfig;

    /**
     * Whether or not the event listeners should execute
     *
     * @var boolean
     **/
    private $enabled = false;

    /**
     * Whether or not the event listeners have been registered
     *
     * @var boolean
     **/
    private $subscribed = false;

    /**
     * Constructor.
     *
     * @param ContainerInterface       $container
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $formatHeaders
     * @param array                    $pathConfig
     */
    public function __construct(ContainerInterface $container, EventDispatcherInterface $dispatcher, $formatHeaders = array(), $pathConfig = array())
    {
        $this->container = $container;
        $this->formatHeaders = $formatHeaders;
        $this->pathConfig = $pathConfig;
    }

    /**
     * This subscriber fires its own API request life-cycle events during the normal kernel events, allowing
     * other code to differentiate between regular requests and "api" requests.
     *
     * Note that the KernelEvents::REQUEST listeners are registered separately via configuration.  The remaining
     * listeners are registered at runtime if the incoming request matches any paths configured.
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', 255),
            KernelEvents::RESPONSE => array('onKernelResponse', 255),
            KernelEvents::VIEW => array('onKernelView', 255),
            KernelEvents::TERMINATE => array('onKernelTerminate', 255),
        );
    }

    /**
     * Listens early in the `kernel.request` cycle for incoming requests and checks for a matching path.  If a
     * match is found, the rest of the kernel listeners are registered and the relevant path config is stored
     * for use in those listeners.
     *
     **/
    public function onKernelEarlyRequest(GetResponseEvent $e)
    {
        $request = $e->getRequest();

        foreach ($this->pathConfig as $regex => $config) {
            if (preg_match($regex, $request->getPathInfo())) {
                $this->enabled = true;

                if (!$this->subscribed) {
                    $this->dispatcher->addSubscriber($this);
                    $this->subscribed = true;
                }

                //set other relevant values for this request
                $config['suppress_codes'] = ($config['allow_code_suppression']) ? $request->query->get('_suppress_codes', false) : false;
                $this->currentPathConfig = $config;

                return;
            }
        }

        $this->enabled = false;
        $this->currentPathConfig = null;
    }

    /**
     * Fires at the end of the `kernel.request` cycle - so listeners should receive a request that has already been
     * resolved to a controller.
     *
     * Will throw exceptions if the response format to use for the serializer could not be resolved.
     *
     **/
    public function onKernelLateRequest(GetResponseEvent $e)
    {
        if (!$this->enabled) return;

        $req = $e->getRequest();
        $this->currentPathConfig['response_format'] = $this->negotiateResponseFormat($req);
        $this->checkForJsonp($req);

        if (!isset($this->formatHeaders[$this->currentPathConfig['response_format']])) {
            throw new HttpException(415);
        }

        $this->dispatcher->dispatch(self::API_REQUEST, $e);
    }

    /**
     * Called if an exception was thrown at any point.
     */
    public function onApiException(GetResponseForExceptionEvent $e)
    {
        if (!$this->enabled) return;

        $cfg = $this->currentPathConfig;

        //notify of generic API error, return early if a response gets set
        $this->dispatcher->dispatch(self::API_EXCEPTION, $e);
        if ($e->getResponse()) {
            return;
        }

        //handle exception body format
        $exception = $e->getException();
        $exceptionClass = get_class($exception);

        //TODO: take into account ValidationException

        //preserve specific http exception codes and messages, otherwise it's 500
        $realHttpErrorCode = $outgoingHttpStatusCode = 500;
        $errorMessage = "Internal Server Error";
        if ($exception instanceof HttpException) {
            $realHttpErrorCode = $outgoingHttpStatusCode = $exception->getStatusCode();
            $errorMessage = ($exception->getMessage()) ? $exception->getMessage() : Response::$statusTexts[$realHttpErrorCode];
        } elseif (isset($cfg['http_exception_map'][$exceptionClass])) {
            //check exception map for overrides
            $map = $cfg['http_exception_map'];
            $realHttpErrorCode = $outgoingHttpStatusCode = $map[$exceptionClass]['code'];
            $errorMessage =
                (isset($map[$exceptionClass]['message']))
                ? $map[$exceptionClass]['message']
                : Response::$statusTexts[$realHttpErrorCode];
        }

        //set generic error data
        $errorData = array(
            'response' => array(
                'code' => $realHttpErrorCode,
                'message' => $errorMessage,
            )
        );

        //inject exception data if we're in dev mode and enabled
        if ($this->includeDevExceptions && in_array($this->container->get('kernel')->getEnvironment(), array('dev','test'))) {
            $errorData['exception'] = array(
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("#", $exception->getTraceAsString()),
            );
        }

        //serialize error content into requested format, if format is not supported by the serializer, do json
        $format = isset($cfg['response_format']) ? $cfg['response_format'] : $cfg['default_response_format'];
        $format = (in_array($format, array('json','xml','yml'))) ? $format : 'json';
        $content = $this->container->get('serializer')->serialize($errorData, $format);

        //check for code suppression
        if ($cfg['suppress_response_codes']) {
            $outgoingHttpStatusCode = 200;
        }

        $headers = array_merge($cfg['additional_headers'], $this->formatHeaders[$format]);

        //set response
        $e->setResponse(new Response($content, $outgoingHttpStatusCode, $headers));
    }

    /**
     * Called when a response object has been resolved.
     */
    public function onApiResponse(FilterResponseEvent $e)
    {
        if (!$this->enabled) return;

        //if supression is active, always return 200 no matter what
        if ($this->currentPathConfig['suppress_response_codes']) {
            $response = $e->getResponse()->setStatusCode(200);
        }

        $this->dispatcher->dispatch(self::API_RESPONSE, $e);
    }

    /**
     * Called when a controller does not return a response object.  Checks specifically for data structures to be serialized.
     */
    public function onApiView(GetResponseForControllerResultEvent $e)
    {
        if (!$this->enabled) return;

        $request = $e->getRequest();
        $result = $e->getControllerResult();
        $cfg = $this->currentPathConfig;

        //should we handle this return at all?
        if (!$result instanceof ServiceResponse && !is_array($result) && !is_object($result)) {
            return;
        }

        //set defaults
        $responseCode = 200;
        $headers = array();
        $template = false;
        $data = $result;
        $serializationContext = null;

        //check specifically for service response
        if ($result instanceof ServiceResponse) {
            $responseCode = $result->getResponseCode();
            $headers = $result->getResponseHeaders();
            $data = $result->getResponseData();
            $template = $result->getTemplate();
            $serializationContext = $result->getSerializationContext();
        }

        $outgoingStatusCode = $cfg['suppress_response_codes'] ? 200 : $responseCode;

        //inject response data?
        if ($cfg['include_response_data'] && is_array($data) && !isset($data['response'])) {
            $data['response'] = array(
                'code' => $responseCode,
                'message' => Response::$statusTexts[$responseCode],
            );
        }

        //render content accordingly
        if ($template) {
            $content = $this->container->get('templating')->render($template, $data);
        } else {
            //load serializer, encode response structure into requested format
            $content = $this->container->get('serializer')->serialize($data, $cfg['response_format'], $serializationContext);

            //if JSONP, use _callback param
            if ($cfg['is_jsonp']) {
                $content = sprintf("%s(%s);", $cfg['jsonp_callback'], $content);
            }
        }

        //merge headers
        $headers = array_merge($headers, array_merge($cfg['additional_headers'], $this->formatHeaders[$format]));

        //set the final response
        $e->setResponse(new Response($content, $outgoingStatusCode, $headers));
    }

    /**
     * Called after a response has already been sent.
     */
    public function onApiTerminate(PostResponseEvent $e)
    {
        if (!$this->enabled) return;

        $this->dispatcher->dispatch(self::API_TERMINATE, $e);
    }

    protected function checkForJsonp(Request $request)
    {
        //check for jsonp, make sure it's valid
        if ('jsonp' === $this->responseFormat) {
            if (!$this->currentPathConfig['allow_jsonp']) {
                throw new HttpException(415, '[jsonp] is not a supported format.');
            }

            //ensure jsonp callback is specified
            if (!$this->currentPathConfig['jsonp_callback'] = $request->query->get('_callback', false)) {
                throw new HttpException(400, "The [_callback] parameter is required for JSONP responses.");
            }

            if ("GET" !== $request->getMethod()) {
                throw new HttpException(400, "JSONP can only be used with GET requests.");
            }

            $this->currentPathConfig['response_format'] = 'json';
            $this->currentPathConfig['is_jsonp'] = true;
        } else {
            $this->currentPathConfig['is_jsonp'] = false;
        }
    }

    protected function negotiateResponseFormat(Request $request)
    {
        //TODO: eventual robust content negotiation here, for now just check request for explicit declaration
        //TODO: negotiate based on accept headers
        $responseFormat = strtolower($request->get('_format', $this->defaultResponseFormat));

        return $responseFormat;
    }
}
