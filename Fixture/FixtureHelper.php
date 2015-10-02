<?php

namespace AC\WebServicesBundle\Fixture;

class FixtureHelper
{
    private $fixture;
    private $model;
    private $obj;
    private $numberToGen;
    private $index;
    private $helperCache;

    public function __construct($fixture, $model, $obj, $n, $i, &$helperCache)
    {
        $this->fixture = $fixture;
        $this->model = $model;
        $this->obj = $obj;
        $this->numberToGen = $n;
        $this->index = $i;
        $this->helperCache = &$helperCache;
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

    # FIXME Do we even need this method in FixtureHelper?
    public function build($n, $model)
    {
        return $this->fixture->build($n, $model);
    }

    # FIXME Do we even need this method in FixtureHelper?
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

    public function fetchRandom($model, $uniqueKey = null, $filter = null)
    {
        return $this->fixture->getObject($model, function($objects) use ($model, $uniqueKey, $filter) {
            if (!is_null($filter) || !is_null($uniqueKey)) {
                $objects = array_filter($objects, function ($obj) use ($model, $uniqueKey, $filter) {
                    if (!is_null($uniqueKey)) {
                        if (isset($this->helperCache["ukUsage"][$model][$uniqueKey][$obj->getId()])) {
                            return false;
                        }
                    }
                    if (!is_null($filter)) {
                        if (call_user_func($filter, $obj) != true) {
                            return false;
                        }
                    }
                    return true;
                });
            }
            if (count($objects) == 0) { throw new \LogicException("No suitable $model available"); }
            $obj = $this->fake()->randomElement($objects);
            if (!is_null($uniqueKey)) {
                $this->helperCache["ukUsage"][$model][$uniqueKey][$obj->getId()] = true;
            }
            return $obj;
        });
    }
}
