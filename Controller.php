<?php

namespace AC\WebServicesBundle;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use AC\WebServicesBundle\Serializer\DeserializationContext;
use AC\WebServicesBundle\Exception\ValidationException;
use JMS\Serializer\Context;

class Controller extends BaseController
{
    /**
     * Convenience method for decoding incoming API data.  The data format is determined via
     * a negotiation service, and then deserialized.
     *
     * @return mixed
     **/
    protected function decodeRequest($class, Context $ctx = null)
    {
        $request = $this->container->get('request');
        $serializerFormat = $this->container->get('ac_web_services.negotiator')->negotiateRequestFormat($request);

        return $this->deserialize($request->getContent(), $class, $serializerFormat, $ctx);
    }

    /**
     * Convenience deserialization method that takes into account issues that should be considered
     * validation errors, such as invalid field names and/or setting read-only data.
     *
     * @throws ValidationException
     **/
    protected function deserialize($data, $class, $format, Context $ctx = null)
    {
        if (!$ctx) {
            $ctx = DeserializationContext::create();
        }

        $obj = $this->container->get('serializer')->deserialize($data, $class, $format, $ctx);

        if ($ctx instanceof DeserializationContext) {
            if ($errors = $ctx->getValidationErrors()) {
                throw new ValidationException($errors);
            }
        }

        return $obj;
    }

    /**
     * Convenience validation method that will automatically throw custom ValidationExceptions
     * when validation fails.
     *
     * @throws ValidationException
     **/
    protected function validate($obj)
    {
        $errors = $this->container->get('validator')->validate($obj);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
