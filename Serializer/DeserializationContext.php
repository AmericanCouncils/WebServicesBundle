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
        return $this->setAttribute('createValidationErrors', (bool) $bool);
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
        return $this->setAttribute('updateNestedData', (bool) $bool);
    }
    
    /**
     * Whether or not serializing into existing objects within a collection.  This
     * requires a comparision field for the association in question to check against
     *
     * @param boolean $bool
     */
    public function setSerializeNestedCollections($bool)
    {
        return $this->setAttribute('updateNestedCollections', (bool) $bool);
    }

    /**
     * Set a previously existing object to serialize data into, instead of creating a new object during serialization.
     *
     * @param mixed $target
     */
    public function setTarget($target)
    {
        return $this->setAttribute('target', $target);
    }
    
    /**
     * Set the default fieldname used for comparisons when serializing
     * into objects in a nested collection.
     *
     * @param string $fieldName
     */
    public function setCollectionComparisonField($fieldName)
    {
        return $this->setAttribute('nestedCollectionDefaultComparisonField', $fieldName);
    }
    
    /**
     * Set a map of associations to fieldnames to use for comparisons when
     * serializing into objects within a nested collection
     * 
     * @param string $map
     */
    public function setCollectionComparisonFieldMap($map)
    {
        return $this->setAttribute('nestedCollectionFieldComparisonMap', $map);
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
