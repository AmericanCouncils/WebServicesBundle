<?php

namespace AC\WebServicesBundle\Serializer;

use JMS\Serializer\Exception\RuntimeException;

class FormDeserializationVisitor extends GenericDeserializationVisitor
{
    protected function decode($str)
    {
        $data = array();
        parse_str($str, $data);

        return $data;
    }
}
