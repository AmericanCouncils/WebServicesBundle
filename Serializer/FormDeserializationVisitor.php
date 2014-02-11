<?php

namespace AC\WebServicesBundle\Serializer;

use JMS\Serializer\GenericDeserializationVisitor;

/**
 * So... it's called 'FormDeserializationVisitor', but really, it takes any old array, as $_POST data
 * is just that.
 */
class FormDeserializationVisitor extends GenericDeserializationVisitor
{
    protected function decode($data)
    {
        return $data;
    }
}
