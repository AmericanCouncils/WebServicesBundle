<?php

namespace AC\WebServicesBundle;

use JMS\Serializer\Context;

/**
 * Note that this does not extend the HttpFoundation Response for a reason, so there is some duplicate functionality.  This Response type allows
 * the bundle to implement custom response logic for all api requests - primarily for the purposes of serializing response data.
 *
 * @package ACWebServicesBundle
 * @author Evan Villemez
 */
class ServiceResponse
{
    protected $statusCode;

    protected $responseData;

    protected $responseHeaders;

    protected $templates = array();

    protected $templateKey = null;

    protected $serializationContext;

    public function __construct($data, $code = 200, $headers = array(), Context $serializationContext = null)
    {
        $this->responseData = $data;
        $this->statusCode = $code;
        $this->responseHeaders = $headers;
        $this->serializationContext = $serializationContext;
    }

    public static function create($data, $code = 200, $headers = array(), Context $serializationContext = null)
    {
        return new static($data, $code, $headers, $serializationContext);
    }

    public function getResponseData()
    {
        return $this->responseData;
    }

    public function setResponseData($data)
    {
        $this->responseData = $data;

        return $this;
    }

    public function getResponseCode()
    {
        return $this->statusCode;
    }

    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    public function setResponseHeaders(array $array)
    {
        $this->responseHeaders = $array;

        return $this;
    }

    public function setResponseHeader($key, $val)
    {
        $this->responseHeaders[$key] = $val;

        return $this;
    }

    public function setSerializationContext(Context $ctx)
    {
        $this->serializationContext = $ctx;

        return $this;
    }

    public function getSerializationContext()
    {
        return $this->serializationContext;
    }

    public function getTemplateForFormat($format)
    {
        return isset($this->templates[$format]) ?: false;
    }

    public function setTemplateForFormat($template, $formats)
    {
        foreach ((array) $formats as $format) {
            $this->templates[$format] = $template;
        }

        return $this;
    }

    public function hasTemplateForFormat($format)
    {
        return isset($this->templates[$format]);
    }

    public function setTemplateKey($key)
    {
        $this->templateKey = $key;

        return $this;
    }

    public function getTemplateKey()
    {
        return $this->templateKey;
    }
}
