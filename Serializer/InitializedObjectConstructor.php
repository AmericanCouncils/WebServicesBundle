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

        // check if we have gone back up, pop the stack if so
        if($context->attributes->containsKey("targetStack")) {
            $targetStack = $context->attributes->get('targetStack')->get();
            $lastDepth = $targetStack->top()['depth'];
            $currentDepth = $context->getDepth();
            if($currentDepth < $lastDepth) {
                $targetStack->pop();
            }

        }

        if($context->attributes->containsKey('updateNestedData')) {
            $updateNestedData = $context->attributes->get('updateNestedData')->get();
        }

        $metaDataStack = $context->getMetadataStack();

        if($context->getDepth() == 1 && $context->attributes->containsKey('target')) {


            if(!$context->attributes->containsKey("targetStack")) {
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

            if(is_null($instance)) {
                return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
            }

            $target = array();
            $target["classMetadata"] = $metaDataStack->top();
            $target["depth"] = $context->getDepth();
            $target["object"] = $instance;

            $targetStack->push($target);

            print_r("\n" . '===' . "\n" . "Depth: " . $context->getDepth());
            print("\n");
            print_r("targetStack top object: ");
            print_r($targetStack->top()['object']);

            return $target['object'];
        }

        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }

    private function doNothing()
    {
        True;
    }
}
            // $serializedName = $propertyMetadata->serializedName;
            // $targetName = $propertyMetadata->reflection->name;
            // $targetParent = $targetStack[count($targetStack) - 1];



    // protected function updateTargetStack($target)
    // {
    //     return null;
    //     // $lastDepth = $context->attributes->get('lastDepth')->get();
    //     // $currentDepth = $context->getDepth();
    //     // $targetStack = $context->attritbutes->get('targetStack')->get();
    //     // if ($lastDepth > $currentDepth) {
    //     //     // we have moved up the graph, pop the stack
    //     //     // $targetStack->pop();
    //     //     $this->doNothing();

    //     // } elseif ($lastDepth < $currentDepth) {
    //     //     // we have moved down the graph, push the stack
    //     //     $this->doNothing();
    //     //     // $targetStack->push(
    //     //         // How do I know what the new thing is?
    //     //     // );
    //     // } elseif ($lastDepth === $currentDepth) {
    //     //     $this->doNothing();
    //     //     // we have moved along the graph. Should this be possible?
    //     // } else {
    //     //     $this->doNothing();
    //     //     // something else has happened. This is probably an error.
    //     // }

    // }


            // var_dump($value);
            // // print_r($targetStack->top()['object']);

            // $targetObjectName = $propertyMetadata->reflection->name;
            // print_r($targetObjectName);



            // $parentProperties = $targetStack[0]['classMetadata']->reflection->getProperties();
            // print_r($parentProperties[3]->setValue('something'));



            // $target["object"] = $this->getTargetObject($targetStack);

            // return $this->updateTargetStack($target);

            // print_r($targetStack);
            // $target = $targetParent->$targetName;
            // $target = $targetParent->

            // $target = $context->attributes->get('target')->get();
            // $target = $targetStack[count($targetStack) - 1]->$serializedName;
            // print_r($target);
            // print("\n");
            // print_r("targetName: ");
            // print_r($targetName);

            // print("\n");
            // print_r("targetParent: ");
            // print_r($targetParent);

            // print("\n");
            // print_r("Class: ");
            // print_r($propertyMetadata->class);

            // print("\n");
            // print_r("SerializedName: ");
            // print_r($propertyMetadata->serializedName);

            // print("\n");
            // print_r("Data: ");
            // print_r($data);
        // if ($context->attributes->containsKey('target') && $context->getDepth() === 1) {
        // if ($context->attributes->containsKey('target')) {
        //     return $context->attributes->get('target')->get();
        // }
