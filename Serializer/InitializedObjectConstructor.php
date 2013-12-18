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

    // protected function pushTargetStack($context)
    // {

    // }

    // protected function popTargetStack($context)
    // {

    // }

    protected function updatetargetStack($context)
    {
        $lastDepth = $context->attributes->get('lastDepth')->get();
        $currentDepth = $context->getDepth();
        $targetStack = $context->attritbutes->get('targetStack')->get();
        if ($lastDepth > $currentDepth) {
            // we have moved up the graph, pop the stack
            // $targetStack->pop();
            $this->doNothing();

        } elseif ($lastDepth < $currentDepth) {
            // we have moved down the graph, push the stack
            $this->doNothing();
            // $targetStack->push(
                // How do I know what the new thing is?
            // );
        } elseif ($lastDepth === $currentDepth) {
            $this->doNothing();
            // we have moved along the graph. Should this be possible?
        } else {
            $this->doNothing();
            // something else has happened. This is probably an error.
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
        $updateNestedData = FALSE;
        if($context->attributes->containsKey('updateNestedData')) {
            $updateNestedData = $context->attributes->get('updateNestedData')->get();
        }


        if($context->getDepth() == 1 && $context->attributes->containsKey('target')) {
            $context->setAttribute('targetStack', new \SplStack());
            $target = $context->attributes->get('target')->get();
            $context->attributes->get('targetStack')->get()->push($target);
            return $target;
        }

        if ($context->getDepth() > 1 && $updateNestedData === TRUE) {
            $stack = $context->getMetadataStack();
            $propertyMetadata = $stack[count($stack) - 2];


            print_r("\n" . '===' . "\n" . "Depth: " . $context->getDepth());

            print("\n");
            print_r("Class: ");
            print_r($propertyMetadata->class);

            print("\n");
            print_r("SerializedName: ");
            print_r($propertyMetadata->serializedName);

            print("\n");
            print_r("Data: ");
            print_r($data);
        }

        // if ($context->attributes->containsKey('target') && $context->getDepth() === 1) {
        // if ($context->attributes->containsKey('target')) {
        //     return $context->attributes->get('target')->get();
        // }

        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }

    private function doNothing()
    {
        True;
    }
}


        //var_dump($data);
        //var_dump($type);
        //var_dump($context->getDepth());
        // var_dump($metadata);
        //var_dump('DEPTH: '. $context->getDepth().' - Meta: '.print_r($metadata, true). ' - DATA: '. print_r($data, true));

        // if ($context->getDepth() !== 1) {
        //     $stack = $context->getMetadataStack();

        //     var_dump($data);
        //     var_dump("WTF!?");
            // print_r($stack[count($stack) - 2]);
        //     exit('PWN3D');
        // }


            // print("\n");
            // print_r("TargetStack: ");
            // print_r($targetStack);
        // if($context->getDepth() === 3) {
        //     throw new \Exception("Get that stack!", 1);

        // }

            // print("\n");
            // print_r("PropertyMetadata: ");
            // print_r($propertyMetadata);

            // print("\n");
            // print_r("Type: ");
            // print_r($propertyMetadata->type);
