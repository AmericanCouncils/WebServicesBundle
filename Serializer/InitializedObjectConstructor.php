<?php

namespace AC\WebServicesBundle\Serializer;

use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Construction\UnserializeObjectConstructor;

/**
 * Object constructor that allows deserialization into already constructed
 * objects passed through the deserialization context
 */
class InitializedObjectConstructor implements ObjectConstructorInterface
{
    private $fallbackConstructor;

    /**
     * Constructor.
     *
     * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
     */
    public function __construct(ObjectConstructorInterface $fallbackConstructor = null)
    {
        if (!$fallbackConstructor) {
            $fallbackConstructor = new UnserializeObjectConstructor();
        }

        $this->fallbackConstructor = $fallbackConstructor;
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
        $updateNestedData = false;

        if ($context->attributes->containsKey("targetStack")) {
            $targetStack = $context->attributes->get('targetStack')->get();
            $top = $targetStack->top();
            $lastDepth = $top['depth'];
            $currentDepth = $context->getDepth();
            if ($currentDepth < $lastDepth) {
                $targetStack->pop();
            }

        }

        if ($context->attributes->containsKey('updateNestedData')) {
            $updateNestedData = (bool) $context->attributes->get('updateNestedData')->get();
        }

        $metaDataStack = $context->getMetadataStack();

        if ($context->getDepth() == 1 && $context->attributes->containsKey('target')) {

            if (!$context->attributes->containsKey("targetStack")) {
                $context->attributes->set('targetStack', new \SplStack());
            }

            $target = array();
            $target["classMetadata"] = $metaDataStack[0];
            $target["depth"] = 1;
            $target["object"] = $context->attributes->get('target')->get();

            $targetStack = $context->attributes->get('targetStack')->get();

            $targetStack->push($target);

            return $target['object'];
        }

        if ($context->getDepth() > 1 && $updateNestedData === true) {
            $propertyMetadata = $metaDataStack[count($metaDataStack) - 2];
            $targetStack = $context->attributes->get('targetStack')->get();

            $top = $targetStack->top();
            $instance = $propertyMetadata->reflection->getValue($top['object']);

            if (is_null($instance) || is_array($instance) || $instance instanceof \Traversable) {
                return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
            }

            $target = array();
            $target["classMetadata"] = $metaDataStack->top();
            $target["depth"] = $context->getDepth();
            $target["object"] = $instance;

            $targetStack->push($target);

            return $target['object'];
        }

        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }
}
