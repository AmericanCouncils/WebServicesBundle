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
        if (is_null($fallbackConstructor)) {
            $this->fallbackConstructor = new UnserializeObjectConstructor();
        }
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
        //var_dump($data);
        //var_dump($type);
        //var_dump($context->getDepth());
        // var_dump($metadata);
        //var_dump('DEPTH: '. $context->getDepth().' - Meta: '.print_r($metadata, true). ' - DATA: '. print_r($data, true));
///*
        if ($context->getDepth() !== 1) {
            $stack = $context->getMetadataStack();

            var_dump($data);
            var_dump("WTF!?");
            print_r($stack[count($stack) - 2]);
            exit('PWN3D');
        }
//*/
        // if ($context->attributes->containsKey('target') && $context->getDepth() === 1) {
        if ($context->attributes->containsKey('target')) {
            return $context->attributes->get('target')->get();
        }

        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }

}
