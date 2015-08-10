<?php

namespace AC\WebServicesBundle\Serializer;

use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Construction\UnserializeObjectConstructor;

/**
 * Object constructor that allows deserialization into already constructed
 * objects passed through the deserialization context.
 *
 * The general approach is that when called to construct a new object during
 * deserialization, the class will inspect the metadata stack JMS uses while
 * walking the object graph.  The deserialization context may contain a target object
 * to deserialize into at the root level.  At each level of the object graph, this
 * will check for an existing target object and return it if found.  Otherwise, it
 * falls back to using whatever the default object construction method is.
 */
class InitializedObjectConstructor implements ObjectConstructorInterface
{
    private $fallbackConstructor;
    private $defaultNestedCollectionComparisonField;
    private $defaultCollectionFieldComparisonMap;

    /**
     * Constructor.
     *
     * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
     */
    public function __construct(ObjectConstructorInterface $fallbackConstructor = null, $defaultNestedCollectionComparisonField = false, $defaultCollectionFieldComparisonMap = [])
    {
        if (!$fallbackConstructor) {
            $fallbackConstructor = new UnserializeObjectConstructor();
        }

        $this->fallbackConstructor = $fallbackConstructor;
        $this->defaultNestedCollectionComparisonField = $defaultNestedCollectionComparisonField;
        $this->defaultCollectionFieldComparisonMap = $defaultCollectionFieldComparisonMap;
    }

    /**
     * {@inheritdoc}
     */
    public function construct(
        VisitorInterface $visitor,
        ClassMetadata $metadata,
        $data,
        array $type,
        DeserializationContext $context
    )
    {
        // don't serialize into anything nested unless explicitly told to do so in
        // the deserialization context
        $updateNestedData = false;
        $updateNestedCollections = false;
        if ($context->attributes->containsKey('updateNestedData')) {
            $updateNestedData = (bool) $context->attributes->get('updateNestedData')->get();
        }
        if ($context->attributes->containsKey('updateNestedCollections')) {
            $updateNestedCollections = (bool) $context->attributes->get('updateNestedCollections')->get();
        }
        
        // We are at the root level, so if there's a target object
        // to serialize into, just retrieve and return it from the
        // deserialization context
        if ($context->getDepth() == 1 && $context->attributes->containsKey('target')) {
            return $context->attributes->get('target')->get();
        }
        
        // if we shouldn't update nested data at all, immediately return
        // to fallback constructor
        if ($context->getDepth() > 1 && !$updateNestedData) {
            return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        // If we're past the root object, and should be serializing into
        // a nested object, then retrieve and return that object using the
        // available metadata
        if ($context->getDepth() > 1 && $updateNestedData === true) {
            $metastack = $context->getMetadataStack();
            
            // get the metadata for the property of the current object
            // that contains the object instance we should retrieve
            $propertyMeta = $metastack[1];
            
            // use the metadata/reflection instance to retrieve existing target object
            // from the property current object
            $targetInstance = $propertyMeta->reflection->getValue($visitor->getCurrentObject());
            
            // if we got something and it's not null or array-like, return it
            if (!is_null($targetInstance) && !(is_array($targetInstance) || $targetInstance instanceof \Traversable)) {
                return $targetInstance;
            }

            // If the target object doesn't already exist, just use
            // the fallback constructor
            if (is_null($targetInstance)) {
                return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
            }
            
            // if the target was found, but is an array, or an array-like mechanism
            // then maybe scan the collection for an existing object if configured
            // to do so - otherwise use the fallback constructor
            if (is_array($targetInstance) || $targetInstance instanceof \Traversable) {

                // just return fallback if we shouldn't check for nested items
                if (!$updateNestedCollections) {
                    return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
                }
                
                // assemble config for how to deal with nested collections
                $nestedCollectionDefaultComparisonField = false;
                $nestedCollectionFieldComparisonMap = false;
                if ($context->attributes->containsKey('nestedCollectionDefaultComparisonField')) {
                    $nestedCollectionDefaultComparisonField = $context->attributes->get('nestedCollectionDefaultComparisonField')->get();
                }
                if ($context->attributes->containsKey('nestedCollectionFieldComparisonMap')) {
                    $nestedCollectionFieldComparisonMap = $context->attributes->get('nestedCollectionFieldComparisonMap')->get();
                }
                $nestedCollectionDefaultComparisonField =
                    $nestedCollectionDefaultComparisonField ? 
                    $nestedCollectionDefaultComparisonField : 
                    $this->defaultNestedCollectionComparisonField;
                $nestedCollectionFieldComparisonMap =
                    $nestedCollectionFieldComparisonMap ?
                    $nestedCollectionFieldComparisonMap :
                    $this->defaultCollectionFieldComparisonMap;

                // check for fieldname to use for this association, or fallback
                // to a default comparison field name
                $associationPath = $metastack[2]->name.'.'.$metastack[1]->name;
                $comparisonFieldName = 
                    isset($nestedCollectionFieldComparisonMap[$associationPath]) ?
                    $nestedCollectionFieldComparisonMap[$associationPath] :
                    $nestedCollectionDefaultComparisonField;

                // if there is a comparison field, and value exists in the submitted data, then
                // check for the corresponding matching value in the objects of the collection
                if ($comparisonFieldName && isset($data[$comparisonFieldName]) && $metadata->reflection->hasProperty($comparisonFieldName)) {
                    // use reflection to retrieve the comparison value
                    $comparisonProperty = $metadata->reflection->getProperty($comparisonFieldName);
                    $comparisonProperty->setAccessible(true);
                    
                    // iterate over collection and check for matching comparison field value
                    foreach ($targetInstance as $item) {
                        if ($data[$comparisonFieldName] == $comparisonProperty->getValue($item)) {
                            return $item;
                        }
                    }
                }
                
                // the corresponding item wasn't found, so use the fallback
                return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
            }
        }
        
        // always return fallback if there was no matching condition
        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }
}
