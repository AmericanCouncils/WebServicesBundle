<?php

namespace AC\WebServicesBundle\Fixture;

use \Faker;

abstract class CachedFixture
{
    private $faker;
    private $currentModel;
    private $currentObject;
    private $currentGenerateIndex;
    private $currentGenerateCount;
    private $generated;

    abstract public function loadInto($container);
    abstract protected function getFixtureObjectManager();

    abstract protected function fixture();

    public function __construct()
    {
        $this->faker = Faker\Factory::create();
    }

    protected function fake()
    {
        return $this->faker;
    }

    protected function reallyNull()
    {
        return new NullStandIn;
    }

    protected function curObject()
    {
        return $this->currentObject;
    }

    protected function idx()
    {
        return $this->currentGenerateIndex;
    }

    protected function remaining()
    {
        return $this->currentGenerateCount - $this->currentGenerateIndex;
    }

    protected function fetchCorresponding($model)
    {
        return $this->retrieveFromGenerated($model, function($fixture, $objects) {
            if (count($objects) > $fixture->currentGenerateCount) {
                $curModel = $fixture->currentModel;
                throw new \Exception(
                    "You're not generating enough $curModel to associate with every $model"
                );
            }
            $idx = $fixture->idx() % count($objects);
            return $objects[$idx];
        });
    }

    protected function fetchRandom($model)
    {
        return $this->retrieveFromGenerated($model, function($fixture, $objects) {
            return $fixture->fake()->randomElement($objects);
        });
    }

    private function retrieveFromGenerated($model, $fn)
    {
        $objMan = $this->getFixtureObjectManager();
        $repo = $objMan->getRepository($model);
        $clsName = $repo->getClassName();
        if (!isset($this->generated[$clsName])) {
            throw new \Exception("You haven't generated any $model yet in this fixture");
        }

        $objects = $this->generated[$clsName];
        $obj = call_user_func(\Closure::bind($fn, $this), $this, $objects);

        $objMan->refresh($obj);
        return $obj;
    }

    protected function generate($n, $model, $fields)
    {
        $this->currentModel = $model;
        $objMan = $this->getFixtureObjectManager();
        $repo = $objMan->getRepository($model);

        $this->currentGenerateCount = $n;
        for ($i = 0; $i < $n; ++$i) {
            $clsName = $repo->getClassName();
            $this->currentObject = new $clsName;
            $this->currentGenerateIndex = $i;
            foreach ($fields as $key => $field) {
                if (!is_callable($field)) {
                    throw new \Exception(
                        "Can't use a non-function for fixture $clsName field $key"
                    );
                }
                mt_srand(hexdec(substr(md5("$clsName-$key-$i"), 0, 8)));
                $value = call_user_func($field, $this);
                if (is_null($value)) {
                    throw new \Exception(
                        "Got null for $clsName $key, maybe you forgot 'return' or 'reallyNull()'"
                    );
                } elseif ($value instanceof NullStandIn) {
                    $value = null;
                }
                call_user_func([$this->currentObject, "set" . ucfirst($key)], $value);
            }
            $objMan->persist($this->currentObject);
            $objMan->flush();
            while ($clsName !== FALSE) {
                $this->generated[$clsName][] = $this->currentObject;
                $clsName = get_parent_class($clsName);
            }
            $this->currentObject = null;
            $this->currentGenerateIndex = null;
        }
        $this->currentGenerateCount = null;
        $this->currentModel = null;
    }

    protected function withLoadingMessage($msg, $func)
    {
        $clsName = get_called_class();
        print "\n$clsName: " . ucfirst($msg) . ", please wait...";
        ob_flush();
        $func();
        print " OK!\n";
        ob_flush();
    }

    protected function execFixture()
    {
        $this->withLoadingMessage("building fixture template",
            function () {
                $this->currentModel = null;
                $this->currentObject = null;
                $this->currentGenerateIndex = null;
                $this->currentGenerateCount = null;
                $this->generated = [];
                $this->fixture();
            }
        );
    }
}
