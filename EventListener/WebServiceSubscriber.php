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
use Psr\Log\LoggerInterface;
use AC\WebServicesBundle\ServiceResponse;
use AC\WebServicesBundle\Debug\ImprovedStackTrace;
use AC\WebServicesBundle\Exception\ServiceException;

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
     * @var Psr\Log\Loggerinterface
     */
    protected $logger;

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
     * Which formats the serializer should handle.
     *
     * @var array
     **/
    protected $serializableFormats;

    /**
     * Map of path regex matches to API behavior configuration.
     *
     * @var array
     **/
    protected $pathConfig;

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
     * @param ContainerInterface $container
     * @param array              $formatHeaders
     * @param array              $pathConfig
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger, $formatHeaders = array(), $pathConfig = array(), $serializableFormats = array())
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->formatHeaders = $formatHeaders;
        $this->pathConfig = $pathConfig;
        $this->serializableFormats = $serializableFormats;
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
     **/
    public function onKernelEarlyRequest(GetResponseEvent $e)
    {
        $request = $e->getRequest();

        foreach ($this->pathConfig as $regex => $config) {
            if (preg_match($regex, $request->getPathInfo())) {
                $this->enabled = true;

                if (!$this->subscribed) {
                    $e->getDispatcher()->addSubscriber($this);
                    $this->subscribed = true;
                }

                //set other relevant values for this request
                $config['suppress_response_codes'] = ($config['allow_code_suppression']) ? $request->query->get('_suppress_codes', false) : false;
                $config['http_response_format'] = $request->get('_format', false);
                $config['negotiated'] = false;

                //subsequent listeners will check the request attributes for relevant configuration
                $request->attributes->set('_ac_web_service', $config);

                return;
            }
        }

        $this->enabled = false;
    }

    /**
     * Fires at the end of the `kernel.request` cycle - so listeners should receive a request that has already been
     * resolved to a controller.
     *
     * Will throw exceptions if the response format is unknown.
     *
     **/
    public function onKernelLateRequest(GetResponseEvent $e)
    {
        if (!$this->enabled) return;

        $req = $e->getRequest();
        if (!$config = $req->attributes->get('_ac_web_service', false)) {
            return;
        }

        $config = $this->negotiateResponseFormat($req, $config);

        $req->attributes->set('_ac_web_service', $config);

        if (!isset($this->formatHeaders[$config['http_response_format']])) {
            throw new HttpException(415);
        }

        $e->getDispatcher()->dispatch(self::API_REQUEST, $e);
    }

    /**
     * Called if an exception was thrown at any point.
     */
    public function onKernelException(GetResponseForExceptionEvent $e)
    {
        if (!$this->enabled) return;

        $req = $e->getRequest();
        $cfg = $req->attributes->get('_ac_web_service');

        //notify of generic API error, return early if a response gets set
        $e->getDispatcher()->dispatch(self::API_EXCEPTION, $e);
        if ($e->getResponse()) {
            return;
        }

        //handle exception body format
        $exception = $e->getException();
        $exceptionClass = get_class($exception);

        //preserve specific http exception codes and messages, otherwise it's 500
        $realHttpErrorCode = $outgoingHttpStatusCode = 500;
        $errorMessage = "Internal Server Error";
        if ($exception instanceof ServiceException) {
            $errorData = $exception->getData();
        }
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
        if (!isset ($errorData)) {
            $errorData = [];
        }
        $errorData['response'] = [
            'code' => $realHttpErrorCode,
            'message' => $errorMessage,
        ];

        //inject exception data if configured to do so
        if ($cfg['include_exception_data']) {
            $errorData['exception'] = array(
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", ImprovedStackTrace::getTrace($exception)),
            );
        }

        //serialize error content into requested format, if format is not supported by the serializer, do json
        //TODO: consider support for exception template formats
        $format = isset($cfg['http_response_format']) ? $cfg['http_response_format'] : $cfg['default_response_format'];
        $format = (in_array($format, $this->serializableFormats)) ? $format : 'json';
        $content = $this->container->get('serializer')->serialize($errorData, $format);

        //check for code suppression
        if ($cfg['suppress_response_codes']) {
            $outgoingHttpStatusCode = 200;
        }

        //log the exception
        $logLevel = $realHttpErrorCode >= 500 ? 'error' : 'warning';
        $logMessage =
            "Caught exception (mapped to code $realHttpErrorCode), " .
            "response to client becomes HTTP $outgoingHttpStatusCode $errorMessage\n" .
            ImprovedStackTrace::getTrace($exception) . "\n---";
        $this->logger->log($logLevel, $logMessage);

        $headers = array_merge($cfg['additional_headers'], $this->formatHeaders[$format]);

        //set response
        $e->setResponse(new Response($content, $outgoingHttpStatusCode, $headers));
    }

    /**
     * Called when a response object has been resolved.
     */
    public function onKernelResponse(FilterResponseEvent $e)
    {

        if (!$this->enabled) return;

        $response = $e->getResponse();

        //if supression is active, always return 200 no matter what
        $config = $e->getRequest()->attributes->get('_ac_web_service');

        if (!$config['negotiated']) {
            $config = $this->negotiateResponseFormat($e->getRequest(), $config);
        }

        if ($config['suppress_response_codes']) {
            $response = $response->setStatusCode(200);
        }

        //if JSONP, wrap use _callback param
        if ($config['is_jsonp']) {
            $response->headers->set('Content-Type', $this->formatHeaders['jsonp']);
            $response->setContent(sprintf("%s(%s);", $config['jsonp_callback'], $response->getContent()));
        }

        $e->getDispatcher()->dispatch(self::API_RESPONSE, $e);
    }

    /**
     * Called when a controller does not return a response object.  Checks specifically for data structures to be serialized.
     */
    public function onKernelView(GetResponseForControllerResultEvent $e)
    {
        if (!$this->enabled) return;

        $request = $e->getRequest();
        $result = $e->getControllerResult();
        $cfg = $request->attributes->get('_ac_web_service');

        //should we handle this return at all?
        if (!$result instanceof ServiceResponse && !is_array($result) && !is_object($result)) {
            return;
        }

        //set defaults
        $responseCode = 200;
        $headers = array();
        $data = $result;
        $serializationContext = null;
        $template = false;
        $outgoingFormat = $cfg['default_response_format'];

        //check specifically for service response
        if ($result instanceof ServiceResponse) {
            $responseCode = $result->getResponseCode();
            $headers = $result->getResponseHeaders();
            $data = $result->getResponseData();
            $serializationContext = $result->getSerializationContext();
            $template = $result->getTemplateForFormat($cfg['http_response_format']);
            $templateKey = $result->getTemplateKey();
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
            if ($templateKey) {
                $data = array($templateKey => $data);
            }

            $outgoingFormat = $cfg['http_response_format'];
            $content = $this->container->get('templating')->render($template, $data);
        }
        //or serialize data if possible
        else if (in_array($cfg['serializer_format'], $this->serializableFormats)) {

            //load serializer, encode response structure into requested format
            $outgoingFormat = $cfg['serializer_format'];
            //var_dump(sprintf("Using [%s]", $outgoingFormat));
            $content = $this->container->get('serializer')->serialize($data, $cfg['serializer_format'], $serializationContext);
        } else {
            //the negotiated response format may not be a serializable one, if not, use the configured default format
            if (
                !in_array($cfg['http_response_format'], $this->serializableFormats)
                &&
                in_array($cfg['default_response_format'], $this->serializableFormats)
            ) {
                //var_dump(sprintf("Negotiated [%s], using [%s]", $cfg['http_response_format'], $cfg['default_response_format']));
                $outgoingFormat = $cfg['default_response_format'];
                $content = $this->container->get('serializer')->serialize($data, $cfg['default_response_format'], $serializationContext);
                if ($content === FALSE) {
                    throw new \DomainException("Couldn't serialize content, JSON error: " . json_last_error_msg());
                }
            }

            //otherwise we don't know what to do with this response data...
            else {
                throw new HttpException(500, 'Could not process response format ['.$cfg['http_response_format'].'].  It is not a known serialization format, and no template for the format was specified.');
            }
        }

        //merge headers
        $additionalHeaders = isset($cfg['additional_headers']) ? $cfg['additional_headers'] : array();
        $headers = array_merge($headers, array_merge($additionalHeaders, $this->formatHeaders[$outgoingFormat]));

        //set the final response
        $e->setResponse(new Response($content, $outgoingStatusCode, $headers));
    }

    /**
     * Called after a response has already been sent.
     */
    public function onKernelTerminate(PostResponseEvent $e)
    {
        if (!$this->enabled) return;

        $e->getDispatcher()->dispatch(self::API_TERMINATE, $e);
    }

    protected function negotiateResponseFormat(Request $request, array $config)
    {
        $responseFormat = strtolower($request->get('_format', false));

        if (!$responseFormat) {
            $responseFormat = $this->container->get('ac_web_services.negotiator')->negotiateResponseFormat($request);
        }

        $config['http_response_format'] = ($responseFormat) ? $responseFormat : $config['default_response_format'];
        $config['serializer_format'] = in_array($config['http_response_format'], $this->serializableFormats) ? $config['http_response_format'] : false;
        $config['negotiated'] = true;

        return $this->checkForJsonp($request, $config);
    }

    protected function checkForJsonp(Request $request, array $config)
    {
        //check for jsonp, make sure it's valid
        if ('jsonp' === $config['http_response_format']) {
            if (!$config['allow_jsonp']) {
                throw new HttpException(415, '[jsonp] is not a supported format.');
            }

            //ensure jsonp callback is specified
            if (!$config['jsonp_callback'] = $request->query->get('_callback', false)) {
                throw new HttpException(400, "The [_callback] parameter is required for JSONP responses.");
            }

            if ("GET" !== $request->getMethod()) {
                throw new HttpException(400, "JSONP can only be used with GET requests.");
            }

            $config['http_response_format'] = 'jsonp';
            $config['serializer_format'] = 'json';
            $config['is_jsonp'] = true;
        } else {
            $config['is_jsonp'] = false;
        }

        return $config;
    }
}
