<?php

namespace AC\WebServicesBundle\Fixture;

class FixtureHelper
{
    private $fixture;
    private $model;
    private $obj;
    private $numberToGen;
    private $index;

    public function __construct($fixture, $model, $obj, $n, $i)
    {
        $this->fixture = $fixture;
        $this->model = $model;
        $this->obj = $obj;
        $this->numberToGen = $n;
        $this->index = $i;
    }

    # Helper functions for fixture descriptions

    public function fake()
    {
        return $this->fixture->getFaker();
    }

    public function reallyNull()
    {
        return new NullStandIn;
    }

    public function curObject()
    {
        return $this->obj;
    }

    public function idx()
    {
        return $this->index;
    }

    public function remaining()
    {
        return $this->numberToGen - $this->index;
    }

    public function build($n, $model)
    {
        return $this->fixture->build($n, $model);
    }

    public function buildOne($model)
    {
        $r = $this->build(1, $model);
        return $r[0];
    }

    public function fetchCorresponding($model)
    {
        return $this->fixture->getObject($model, function($objects) use ($model) {
            if (count($objects) > $this->numberToGen) {
                throw new \LogicException(
                    "You're not generating enough $this->model to associate with every $model"
                );
            }
            $i = $this->index % count($objects);
            return $objects[$i];
        });
    }

    public function fetchRandom($model)
    {
        return $this->fixture->getObject($model, function($objects) {
            return $this->fake()->randomElement($objects);
        });
    }
}
