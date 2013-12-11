<?php

namespace AC\WebServicesBundle\Serializer;

use JMS\Serializer\DeserializationContext as BaseDeserializationContext;

/**
 * Adds convenience methods for extra deserialization functionality provided by the bundle.
 **/
class DeserializationContext extends BaseDeserializationContext
{
    public function setTarget($target)
    {
        $this->attributes->set('target', $target);

        return $this;
    }

    public function getTarget()
    {
        return $this->attributes->get('target');
    }

    public function getGroups()
    {
        return $this->attributes->get('groups');
    }

}
