<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
        $updateNestedData = FALSE;

        if ($context->attributes->containsKey("targetStack")) {
            $targetStack = $context->attributes->get('targetStack')->get();
            $lastDepth = $targetStack->top()['depth'];
            $currentDepth = $context->getDepth();
            if ($currentDepth < $lastDepth) {
                $targetStack->pop();
            }

        }

        if ($context->attributes->containsKey('updateNestedData')) {
            $updateNestedData = $context->attributes->get('updateNestedData')->get();
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

        if ($context->getDepth() > 1 && $updateNestedData === TRUE) {
            $propertyMetadata = $metaDataStack[count($metaDataStack) - 2];
            $targetStack = $context->attributes->get('targetStack')->get();

            $instance = $propertyMetadata->reflection->getValue($targetStack->top()['object']);

            if (is_null($instance) || is_array($instance)) {
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

    private function doNothing()
    {
        True;
    }
}
