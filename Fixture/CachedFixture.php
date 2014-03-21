<?php

namespace AC\WebServicesBundle\Fixture;

use \Faker;

# FIXME Ack, ORM specific!!! Make usage of this based on abstract method
use Doctrine\ORM\Mapping\ClassMetadataInfo;

abstract class CachedFixture
{
    private $faker;
    private $generated;
    private $descriptions;

    abstract protected function loadImpl($container);
    abstract protected function getFixtureObjectManager();
    abstract protected function getNamespaceAliases($objMan);

    abstract protected function fixture();

    public function __construct()
    {
        $this->faker = Faker\Factory::create();
    }

    public function loadInto($container)
    {
        $this->loadImpl($container);
        return $this->generated;
    }

    public function getFaker()
    {
        return $this->faker;
    }

    public function getObject($model, $selectorFn)
    {
        $objMan = $this->getFixtureObjectManager();
        if (!isset($this->generated[$model])) {
            throw new \LogicException("You haven't generated any $model yet in this fixture");
        }

        $objects = $this->generated[$model];
        $obj = call_user_func($selectorFn, $objects);

        $objMan->refresh($obj);
        return $obj;
    }

    protected function describe($model, $fields)
    {
        if (isset($this->descriptions[$model])) {
            throw new \LogicException("You have already set a description for $model");
        }
        if (!is_array($fields)) {
            throw new \InvalidArgumentException(
                "Description should be an array mapping field names to functions"
            );
        }
        $this->descriptions[$model] = $fields;
    }

    public function build($n, $model)
    {
        if (!isset($this->descriptions[$model])) {
            throw new \InvalidArgumentException(
                "No such model $model described"
            );
        }
        $objMan = $this->getFixtureObjectManager();
        $cls = $this->removeNamespaceAlias($objMan, $model);

        $objs = [];
        for ($i = 0; $i < $n; ++$i) {
            $obj = new $cls;
            $helper = new FixtureHelper($this, $model, $obj, $n, $i);

            foreach ($this->descriptions[$model] as $key => $field) {
                if (!is_callable($field)) {
                    throw new \LogicException(
                        "Can't use a non-function for fixture $model field $key"
                    );
                }
                mt_srand(hexdec(substr(md5("$cls-$key-$i"), 0, 8)));
                $value = call_user_func($field, $helper);
                if (is_null($value)) {
                    throw new \LogicException(
                        "Got null for $model $key, maybe you forgot 'return' or 'reallyNull()'"
                    );
                } elseif ($value instanceof NullStandIn) {
                    $value = null;
                }
                call_user_func([$obj, "set" . ucfirst($key)], $value);
            }

            $objs[] = $obj;
        }

        foreach ($objs as $obj) {
            $meta = $objMan->getClassMetadata(get_class($obj));
            foreach ($meta->associationMappings as $field => $assoc) {
                # FIXME: ORM specific!
                $mappedBy = $assoc['mappedBy'];
                if (is_null($mappedBy)) { continue; }
                $data = call_user_func([$obj, "get" . ucfirst($field)]);
                if (is_null($data)) { continue; }
                foreach ($data as $subObj) {
                    $mapping = call_user_func([$subObj, "get" . ucfirst($mappedBy)]);
                    if (is_null($mapping)) {
                        call_user_func([$subObj, "set" . ucfirst($mappedBy)], $obj);
                        $objMan->persist($subObj);
                    }
                }
            }

            $this->generated[$model][] = $obj;
            foreach ($this->getModelAncestors($objMan, $model) as $a) {
                $this->generated[$a][] = $obj;
            }

            $objMan->persist($obj);
        }

        $objMan->flush();
        return $objs;
    }

    protected function generate($n, $model, $fields = null)
    {
        if (!is_null($fields)) {
            $this->describe($model, $fields);
        }
        $r = $this->build($n, $model);
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
                $this->generated = [];
                $this->descriptions = [];
                $this->fixture();
            }
        );
    }

    private function removeNamespaceAlias($objMan, $cls)
    {
        if (strpos($cls, ':') !== false) {
            list($nsAlias, $shortCls) = explode(':', $cls);
            $aliases = $this->getNamespaceAliases($objMan);
            if (isset($aliases[$nsAlias])) {
                return $aliases[$nsAlias] . '\\' . $shortCls;
            } else {
                throw new \LogicException("Unknown model namespace $nsAlias");
            }
        }

        return $cls;
    }

    private function getModelAncestors($objMan, $cls)
    {
        $aliases = null;
        if (strpos($cls, ':') !== false) {
            $cls = $this->removeNamespaceAlias($objMan, $cls);
            $aliases = $this->getNamespaceAliases($objMan);
        }
        $r = [];

        while (true) {
            $cls = get_parent_class($cls);
            if ($cls === false) { break; }
            $name = $cls;
            if (!is_null($aliases)) {
                foreach ($aliases as $alias => $fullNs) {
                    $fullNs = trim($fullNs, '\\');
                    if (strpos($cls, $fullNs) === 0) {
                        $name = str_replace($fullNs . '\\', $alias . ':', $cls);
                        break;
                    }
                }
            }
            $r[] = $name;
        }

        return $r;
    }
}
