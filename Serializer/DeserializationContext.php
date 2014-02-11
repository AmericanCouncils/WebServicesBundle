<?php

namespace AC\WebServicesBundle\Serializer;

use JMS\Serializer\DeserializationContext as BaseDeserializationContext;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Adds convenience methods for extra deserialization functionality provided by the bundle.
 **/
class DeserializationContext extends BaseDeserializationContext
{
    private $validationErrors = array();

    public static function create()
    {
        return new self();
    }

    public function setCreateValidationErrors($bool)
    {
        $this->setAttribute('createValidationErrors', (bool) $bool);

        return $this;
    }

    public function addValidationError(ConstraintViolationInterface $error)
    {
        $this->validationErrors[] = $error;
    }

    public function createValidationErrorForCurrentNode()
    {
        //TODO: Use all the data from the context to create a ConstraintViolation
        //for the current node.  For example, use the metadata stack to determine
        //the current property path

        // possible error conditions include:
        // - setting ready only fields
        // - setting non-existing fields
        // - setting fields not in the current group

        throw new \RuntimeException('Not implemented.');
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * Set whether or not the serializer should serialize into nested properties of an object.  This only applies
     * if a target has been set via `setTarget()`.  Note that, objects inside arrays will never be serialized into,
     * arrays are simply overwritten.
     *
     * @param boolean $bool
     */
    public function setSerializeNested($bool)
    {
        $this->setAttribute('updateNestedData', (bool) $bool);

        return $this;
    }

    /**
     * Set a previously existing object to serialize data into, instead of creating a new object during serialization.
     *
     * @param mixed $target
     */
    public function setTarget($target)
    {
        $this->setAttribute('target', $target);

        return $this;
    }

    /**
     * Convenience method to get a target object, if set.
     *
     * @return mixed
     */
    public function getTarget()
    {
        return $this->attributes->get('target');
    }

    /**
     * Convenience method to get the serializer groups, if set.
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->attributes->get('groups');
    }

}
