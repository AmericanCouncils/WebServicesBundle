<?php

namespace AC\WebServicesBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Base class for all service exceptions.  They can provide a data structure
 * that will be intercepted and serialized by the serializer.
 */
class ServiceException extends HttpException
{
    private $data;

    public function __construct($code, $data, $message = null)
    {
        $this->data = $data;

        parent::__construct($code, $message);
    }

    public function getData()
    {
        return $this->data;
    }
}
